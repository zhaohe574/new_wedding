<?php

declare(strict_types=1);

namespace app\adminapi\logic\wedding;

use app\common\logic\BaseLogic;
use app\common\model\wedding\ServiceOrderReview;
use app\common\service\FileService;
use app\common\service\ServiceOrderService;

class ServiceOrderReviewLogic extends BaseLogic
{
    public static function detail(array $params): array
    {
        $review = ServiceOrderReview::alias('review')
            ->leftJoin('service_order order', 'order.id = review.order_id')
            ->leftJoin('user user', 'user.id = review.user_id')
            ->leftJoin('service_provider provider', 'provider.id = review.provider_id')
            ->where('review.id', (int)$params['id'])
            ->whereNull('review.delete_time')
            ->field([
                'review.*',
                'order.sn',
                'order.package_name',
                'user.nickname' => 'user_nickname',
                'user.mobile' => 'user_mobile',
                'provider.name' => 'provider_name',
            ])
            ->append(['audit_status_desc', 'audit_role_desc'])
            ->findOrEmpty();

        if ($review->isEmpty()) {
            return [];
        }

        $data = $review->toArray();
        if (!empty($data['images']) && is_array($data['images'])) {
            $data['images'] = array_values(array_map(function ($item) {
                return FileService::getFileUrl((string)$item);
            }, $data['images']));
        }

        return $data;
    }

    public static function audit(array $params, int $adminId): bool
    {
        try {
            ServiceOrderService::adminAuditReview(
                $adminId,
                (int)$params['id'],
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
