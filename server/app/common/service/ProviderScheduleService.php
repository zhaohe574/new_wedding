<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\model\wedding\ProviderSchedule;
use think\facade\Db;

class ProviderScheduleService
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_LOCKED = 'locked';
    public const STATUS_OCCUPIED = 'occupied';
    public const STATUS_UNAVAILABLE = 'unavailable';

    public const SOURCE_MANUAL = 'manual';

    public static function getStatusMap(): array
    {
        return [
            self::STATUS_AVAILABLE => '可预约',
            self::STATUS_LOCKED => '已锁定',
            self::STATUS_OCCUPIED => '已占用',
            self::STATUS_UNAVAILABLE => '不可服务',
        ];
    }

    public static function getEffectiveStatus(int $providerId, string $serviceDate): string
    {
        $record = self::getRecord($providerId, $serviceDate);
        if ($record->isEmpty()) {
            return self::STATUS_AVAILABLE;
        }

        return (string)$record['status'];
    }

    public static function syncStatus(
        int $providerId,
        string $serviceDate,
        string $status,
        string $sourceType = self::SOURCE_MANUAL,
        int $sourceId = 0,
        string $remark = ''
    ): array {
        self::assertStatus($status);

        $serviceDate = trim($serviceDate);
        $record = self::getRecord($providerId, $serviceDate);
        $currentStatus = $record->isEmpty() ? self::STATUS_AVAILABLE : (string)$record['status'];

        if ($currentStatus === self::STATUS_OCCUPIED && $status !== self::STATUS_OCCUPIED) {
            throw new \RuntimeException('已占用档期不可直接改回其他状态');
        }

        if ($status === self::STATUS_AVAILABLE) {
            return self::markAvailable($providerId, $serviceDate);
        }

        Db::transaction(function () use ($record, $providerId, $serviceDate, $status, $sourceType, $sourceId, $remark) {
            $saveData = [
                'provider_id' => $providerId,
                'service_date' => $serviceDate,
                'status' => $status,
                'source_type' => trim($sourceType) !== '' ? $sourceType : self::SOURCE_MANUAL,
                'source_id' => $sourceId,
                'remark' => trim($remark),
                'update_time' => time(),
            ];

            if ($record->isEmpty()) {
                $saveData['create_time'] = time();
                ProviderSchedule::create($saveData);
                return;
            }

            $saveData['id'] = (int)$record['id'];
            ProviderSchedule::update($saveData);
        });

        return [
            'provider_id' => $providerId,
            'service_date' => $serviceDate,
            'status' => $status,
        ];
    }

    public static function lock(int $providerId, string $serviceDate, string $sourceType, int $sourceId = 0, string $remark = ''): array
    {
        $currentStatus = self::getEffectiveStatus($providerId, $serviceDate);
        if ($currentStatus !== self::STATUS_AVAILABLE) {
            throw new \RuntimeException('当前档期不可锁定');
        }

        return self::syncStatus($providerId, $serviceDate, self::STATUS_LOCKED, $sourceType, $sourceId, $remark);
    }

    public static function occupy(int $providerId, string $serviceDate, string $sourceType, int $sourceId = 0, string $remark = ''): array
    {
        $currentStatus = self::getEffectiveStatus($providerId, $serviceDate);
        if ($currentStatus !== self::STATUS_LOCKED) {
            throw new \RuntimeException('仅已锁定档期可转为已占用');
        }

        return self::syncStatus($providerId, $serviceDate, self::STATUS_OCCUPIED, $sourceType, $sourceId, $remark);
    }

    public static function releaseLocked(int $providerId, string $serviceDate): array
    {
        $currentStatus = self::getEffectiveStatus($providerId, $serviceDate);
        if ($currentStatus === self::STATUS_AVAILABLE) {
            return [
                'provider_id' => $providerId,
                'service_date' => $serviceDate,
                'status' => self::STATUS_AVAILABLE,
            ];
        }

        if ($currentStatus !== self::STATUS_LOCKED) {
            throw new \RuntimeException('当前档期不可释放');
        }

        return self::markAvailable($providerId, $serviceDate);
    }

    public static function markUnavailable(int $providerId, string $serviceDate, string $remark = ''): array
    {
        $currentStatus = self::getEffectiveStatus($providerId, $serviceDate);
        if (!in_array($currentStatus, [self::STATUS_AVAILABLE, self::STATUS_UNAVAILABLE], true)) {
            throw new \RuntimeException('当前档期不可直接设为不可服务');
        }

        return self::syncStatus($providerId, $serviceDate, self::STATUS_UNAVAILABLE, self::SOURCE_MANUAL, 0, $remark);
    }

    public static function markAvailable(int $providerId, string $serviceDate): array
    {
        $record = self::getRecord($providerId, $serviceDate);
        if (!$record->isEmpty()) {
            if ((string)$record['status'] === self::STATUS_OCCUPIED) {
                throw new \RuntimeException('已占用档期不可直接恢复可约');
            }
            ProviderSchedule::destroy((int)$record['id']);
        }

        return [
            'provider_id' => $providerId,
            'service_date' => $serviceDate,
            'status' => self::STATUS_AVAILABLE,
        ];
    }

    private static function getRecord(int $providerId, string $serviceDate): ProviderSchedule
    {
        return ProviderSchedule::where([
            'provider_id' => $providerId,
            'service_date' => trim($serviceDate),
        ])->whereNull('delete_time')->findOrEmpty();
    }

    private static function assertStatus(string $status): void
    {
        if (!array_key_exists($status, self::getStatusMap())) {
            throw new \RuntimeException('档期状态不正确');
        }
    }
}
