<?php

declare(strict_types=1);

namespace app\adminapi\logic\wedding;

use app\common\logic\BaseLogic;
use app\common\model\refund\RefundLog;
use app\common\model\refund\RefundRecord;
use app\common\model\wedding\ServiceOrder;
use app\common\model\wedding\ServiceOrderRefund;
use app\common\service\ServiceOrderService;

class ServiceOrderRefundLogic extends BaseLogic
{
    public static function detail(array $params): array
    {
        $refund = ServiceOrderRefund::alias('refund')
            ->leftJoin('service_order order', 'order.id = refund.order_id')
            ->leftJoin('user user', 'user.id = refund.user_id')
            ->leftJoin('service_provider provider', 'provider.id = refund.provider_id')
            ->where('refund.id', (int)$params['id'])
            ->whereNull('refund.delete_time')
            ->field([
                'refund.*',
                'order.sn',
                'order.order_status',
                'order.pay_status',
                'order.payment_type',
                'order.package_name',
                'order.service_date',
                'order.order_amount',
                'user.nickname' => 'user_nickname',
                'user.mobile' => 'user_mobile',
                'provider.name' => 'provider_name',
            ])
            ->append(['status_desc', 'apply_source_desc'])
            ->findOrEmpty();

        if ($refund->isEmpty()) {
            return [];
        }

        $refundData = $refund->toArray();
        $order = ServiceOrder::findOrEmpty((int)$refundData['order_id']);
        $orderData = $order->isEmpty() ? [] : ServiceOrderService::buildOrderDetail($order->toArray(), true);
        $refundRecord = RefundRecord::where('id', (int)($refundData['refund_record_id'] ?? 0))->findOrEmpty();
        $refundRecordData = $refundRecord->isEmpty()
            ? []
            : $refundRecord->append(['refund_type_text', 'refund_status_text', 'refund_way_text'])->toArray();
        $refundLogs = RefundLog::where('record_id', (int)($refundData['refund_record_id'] ?? 0))
            ->order(['id' => 'desc'])
            ->append(['handler', 'refund_status_text'])
            ->select()
            ->toArray();

        return [
            'refund' => $refundData,
            'order' => $orderData['order'] ?? [],
            'latest_change' => $orderData['latest_change'] ?? [],
            'latest_refund' => $orderData['latest_refund'] ?? [],
            'refund_record' => $refundRecordData,
            'refund_logs' => $refundLogs,
        ];
    }

    public static function handle(array $params, int $adminId): bool
    {
        try {
            ServiceOrderService::adminHandleRefund(
                $adminId,
                (int)$params['id'],
                (int)$params['audit_status'],
                trim((string)($params['handle_remark'] ?? ''))
            );
            return true;
        } catch (\Throwable $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    public static function manualRefund(array $params, int $adminId): bool
    {
        try {
            ServiceOrderService::adminManualRefund(
                $adminId,
                (int)$params['order_id'],
                trim((string)$params['apply_reason']),
                trim((string)($params['handle_remark'] ?? ''))
            );
            return true;
        } catch (\Throwable $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
