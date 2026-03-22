<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\model\wedding\ProviderSchedule;
use think\facade\Db;

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

    public static function movePendingOrder(int $providerId, string $oldServiceDate, string $newServiceDate, int $sourceId = 0, string $remark = ''): array
    {
        return self::moveScheduleStatus(
            $providerId,
            $oldServiceDate,
            $newServiceDate,
            ProviderScheduleService::STATUS_LOCKED,
            ProviderScheduleService::STATUS_LOCKED,
            $sourceId,
            $remark
        );
    }

    public static function moveOccupiedOrder(int $providerId, string $oldServiceDate, string $newServiceDate, int $sourceId = 0, string $remark = ''): array
    {
        return self::moveScheduleStatus(
            $providerId,
            $oldServiceDate,
            $newServiceDate,
            ProviderScheduleService::STATUS_OCCUPIED,
            ProviderScheduleService::STATUS_OCCUPIED,
            $sourceId,
            $remark
        );
    }

    private static function moveScheduleStatus(
        int $providerId,
        string $oldServiceDate,
        string $newServiceDate,
        string $oldExpectedStatus,
        string $newStatus,
        int $sourceId = 0,
        string $remark = ''
    ): array {
        $oldServiceDate = trim($oldServiceDate);
        $newServiceDate = trim($newServiceDate);

        if ($oldServiceDate === $newServiceDate) {
            throw new \RuntimeException('新旧服务日期不能相同');
        }

        return Db::transaction(function () use (
            $providerId,
            $oldServiceDate,
            $newServiceDate,
            $oldExpectedStatus,
            $newStatus,
            $sourceId,
            $remark
        ) {
            $oldRecord = ProviderSchedule::where([
                'provider_id' => $providerId,
                'service_date' => $oldServiceDate,
            ])->whereNull('delete_time')->lock(true)->findOrEmpty();

            if ($oldRecord->isEmpty() || (string)$oldRecord['status'] !== $oldExpectedStatus) {
                throw new \RuntimeException('原档期状态已变化，请刷新后重试');
            }

            $newRecord = ProviderSchedule::where([
                'provider_id' => $providerId,
                'service_date' => $newServiceDate,
            ])->whereNull('delete_time')->lock(true)->findOrEmpty();

            if (!$newRecord->isEmpty()) {
                throw new \RuntimeException('新档期已不可预约，请重新选择');
            }

            ProviderSchedule::create([
                'provider_id' => $providerId,
                'service_date' => $newServiceDate,
                'status' => $newStatus,
                'source_type' => self::SOURCE_PENDING_ORDER,
                'source_id' => $sourceId,
                'remark' => trim($remark),
                'create_time' => time(),
                'update_time' => time(),
            ]);

            ProviderSchedule::destroy((int)$oldRecord['id']);

            return [
                'provider_id' => $providerId,
                'old_service_date' => $oldServiceDate,
                'new_service_date' => $newServiceDate,
                'status' => $newStatus,
            ];
        });
    }
}
