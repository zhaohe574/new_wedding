<?php

declare(strict_types=1);

namespace app\common\command;

use app\common\enum\PayEnum;
use app\common\enum\wedding\ServiceOrderEnum;
use app\common\model\wedding\ServiceOrder;
use app\common\service\ServiceOrderService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

class ServiceOrderTimeoutClose extends Command
{
    private const BATCH_SIZE = 100;

    protected function configure()
    {
        $this->setName('service_order_timeout_close')
            ->setDescription('婚庆订单超时自动关闭');
    }

    protected function execute(Input $input, Output $output)
    {
        $now = time();
        $stats = [
            'scanned' => 0,
            'closed' => 0,
            'skipped' => 0,
            'failed' => 0,
            'failed_order_ids' => [],
        ];

        $scenes = [
            [
                'status' => ServiceOrderEnum::WAIT_PROVIDER_CONFIRM,
                'expire_field' => 'provider_confirm_expire_time',
                'reason' => '服务人员确认超时自动关闭',
            ],
            [
                'status' => ServiceOrderEnum::WAIT_PAY,
                'expire_field' => 'pay_expire_time',
                'reason' => '待支付超时自动关闭',
                'extra_where' => [
                    ['pay_status', '=', PayEnum::UNPAID],
                ],
            ],
        ];

        foreach ($scenes as $scene) {
            $this->handleScene($scene, $now, $stats);
        }

        $failedOrderIds = empty($stats['failed_order_ids'])
            ? '-'
            : implode(',', array_values(array_unique($stats['failed_order_ids'])));

        $output->writeln('婚庆订单超时关闭执行完成');
        $output->writeln('scanned=' . $stats['scanned']);
        $output->writeln('closed=' . $stats['closed']);
        $output->writeln('skipped=' . $stats['skipped']);
        $output->writeln('failed=' . $stats['failed']);
        $output->writeln('failed_order_ids=' . $failedOrderIds);

        return self::SUCCESS;
    }

    private function handleScene(array $scene, int $now, array &$stats): void
    {
        $lastId = 0;

        while (true) {
            $orders = $this->getExpiredOrders($scene, $now, $lastId);
            if (empty($orders)) {
                break;
            }

            $stats['scanned'] += count($orders);
            foreach ($orders as $order) {
                $orderId = (int)$order['id'];
                $lastId = $orderId;

                try {
                    $result = ServiceOrderService::closeTimedOutOrderBySystem(
                        $orderId,
                        (int)$scene['status'],
                        $now,
                        (string)$scene['reason']
                    );

                    if (($result['result'] ?? '') === 'closed') {
                        $stats['closed']++;
                        continue;
                    }

                    $stats['skipped']++;
                } catch (\Throwable $exception) {
                    $stats['failed']++;
                    $stats['failed_order_ids'][] = $orderId;
                    Log::write('婚庆订单超时关闭失败，订单ID:' . $orderId . '，原因:' . $exception->getMessage());
                }
            }
        }
    }

    private function getExpiredOrders(array $scene, int $now, int $lastId): array
    {
        $query = ServiceOrder::where('id', '>', $lastId)
            ->where('order_status', (int)$scene['status'])
            ->where((string)$scene['expire_field'], '>', 0)
            ->where((string)$scene['expire_field'], '<=', $now)
            ->whereNull('delete_time');

        foreach ($scene['extra_where'] ?? [] as $where) {
            $query->where($where[0], $where[1], $where[2]);
        }

        return $query->field(['id'])
            ->order(['id' => 'asc'])
            ->limit(self::BATCH_SIZE)
            ->select()
            ->toArray();
    }
}
