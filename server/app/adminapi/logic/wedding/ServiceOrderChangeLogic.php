<?php

declare(strict_types=1);

namespace app\adminapi\logic\wedding;

use app\common\logic\BaseLogic;
use app\common\model\wedding\ServiceOrderChange;
use app\common\service\ServiceOrderService;

class ServiceOrderChangeLogic extends BaseLogic
{
    public static function detail(array $params): array
    {
        return ServiceOrderChange::alias('change_record')
            ->leftJoin('service_order order', 'order.id = change_record.order_id')
            ->leftJoin('user user', 'user.id = change_record.user_id')
            ->leftJoin('service_provider provider', 'provider.id = change_record.provider_id')
            ->where('change_record.id', (int)$params['id'])
            ->whereNull('change_record.delete_time')
            ->field([
                'change_record.*',
                'order.sn',
                'order.order_status',
                'order.package_name',
                'user.nickname' => 'user_nickname',
                'user.mobile' => 'user_mobile',
                'provider.name' => 'provider_name',
            ])
            ->append(['status_desc', 'handle_role_desc'])
            ->findOrEmpty()
            ->toArray();
    }

    public static function handle(array $params, int $adminId): bool
    {
        try {
            ServiceOrderService::adminHandleReschedule(
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
