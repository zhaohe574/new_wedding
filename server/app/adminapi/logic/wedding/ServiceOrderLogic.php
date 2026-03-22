<?php

declare(strict_types=1);

namespace app\adminapi\logic\wedding;

use app\common\logic\BaseLogic;
use app\common\model\wedding\ServiceOrderChange;
use app\common\model\wedding\ServiceOrder;
use app\common\model\wedding\ServiceOrderOfflineVoucher;
use app\common\model\wedding\ServiceOrderReview;
use app\common\model\wedding\ServiceOrderSnapshot;
use app\common\service\FileService;
use app\common\service\ServiceOrderService;

class ServiceOrderLogic extends BaseLogic
{
    public static function detail(array $params): array
    {
        $order = ServiceOrder::alias('order')
            ->leftJoin('user user', 'user.id = order.user_id')
            ->leftJoin('service_provider provider', 'provider.id = order.provider_id')
            ->where('order.id', (int)$params['id'])
            ->whereNull('order.delete_time')
            ->field([
                'order.*',
                'user.nickname' => 'user_nickname',
                'user.mobile' => 'user_mobile',
                'provider.user_id' => 'provider_user_id',
            ])
            ->findOrEmpty();

        if ($order->isEmpty()) {
            return [];
        }

        $orderData = $order->toArray();
        ServiceOrderService::appendOrderDesc($orderData);

        $snapshot = ServiceOrderSnapshot::where(['order_id' => (int)$orderData['id']])
            ->whereNull('delete_time')
            ->findOrEmpty();
        $voucher = ServiceOrderOfflineVoucher::where(['order_id' => (int)$orderData['id']])
            ->whereNull('delete_time')
            ->findOrEmpty();
        $change = ServiceOrderChange::where(['order_id' => (int)$orderData['id']])
            ->whereNull('delete_time')
            ->order(['id' => 'desc'])
            ->findOrEmpty();
        $review = ServiceOrderReview::where(['order_id' => (int)$orderData['id']])
            ->whereNull('delete_time')
            ->findOrEmpty();

        $snapshotData = $snapshot->isEmpty() ? [] : $snapshot->toArray();
        $voucherData = $voucher->isEmpty() ? [] : $voucher->toArray();
        $changeData = $change->isEmpty() ? [] : $change->append(['status_desc', 'handle_role_desc'])->toArray();
        $reviewData = $review->isEmpty() ? [] : $review->append(['audit_status_desc', 'audit_role_desc'])->toArray();
        if (!empty($voucherData['voucher_images']) && is_array($voucherData['voucher_images'])) {
            $voucherData['voucher_images'] = array_values(array_map(function ($item) {
                return FileService::getFileUrl((string)$item);
            }, $voucherData['voucher_images']));
        }
        if (!empty($reviewData['images']) && is_array($reviewData['images'])) {
            $reviewData['images'] = array_values(array_map(function ($item) {
                return FileService::getFileUrl((string)$item);
            }, $reviewData['images']));
        }

        return [
            'order' => $orderData,
            'snapshot' => $snapshotData,
            'offline_voucher' => $voucherData,
            'latest_change' => $changeData,
            'review' => $reviewData,
        ];
    }

    public static function offlineVoucherAudit(array $params, int $adminId): bool
    {
        try {
            ServiceOrderService::adminAuditOfflineVoucher(
                $adminId,
                (int)$params['order_id'],
                (int)$params['audit_status'],
                trim((string)($params['audit_remark'] ?? ''))
            );
            return true;
        } catch (\Throwable $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
