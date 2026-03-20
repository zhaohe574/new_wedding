<?php

declare(strict_types=1);

namespace app\common\service;

class WeddingOrderScheduleService
{
    public const SOURCE_PENDING_ORDER = 'pending_order';

    public static function assertReservable(int $providerId, string $serviceDate): void
    {
        $status = ProviderScheduleService::getEffectiveStatus($providerId, $serviceDate);
        if ($status !== ProviderScheduleService::STATUS_AVAILABLE) {
            throw new \RuntimeException('当前服务日期已不可预约，请重新选择');
        }
    }

    public static function lockForPendingOrder(int $providerId, string $serviceDate, int $sourceId = 0, string $remark = ''): array
    {
        return ProviderScheduleService::lock(
            $providerId,
            $serviceDate,
            self::SOURCE_PENDING_ORDER,
            $sourceId,
            $remark
        );
    }

    public static function releasePendingOrder(int $providerId, string $serviceDate): array
    {
        return ProviderScheduleService::releaseLocked($providerId, $serviceDate);
    }
}
