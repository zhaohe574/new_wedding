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

    public static function getStatusHintMap(): array
    {
        return [
            self::STATUS_AVAILABLE => '当前自然日可继续接单，无显式限制记录。',
            self::STATUS_LOCKED => '订单待确认阶段已锁档，需按订单流转自动释放。',
            self::STATUS_OCCUPIED => '订单已确认占用档期，需按履约或退款流转释放。',
            self::STATUS_UNAVAILABLE => '服务人员手动设为不可服务，可自行恢复。',
        ];
    }

    public static function getSourceMap(): array
    {
        return [
            self::SOURCE_MANUAL => '手动维护',
            WeddingOrderScheduleService::SOURCE_PENDING_ORDER => '订单锁档',
        ];
    }

    public static function getSourceDesc(string $sourceType): string
    {
        $sourceType = trim($sourceType);
        if ($sourceType === '') {
            return '-';
        }

        return self::getSourceMap()[$sourceType] ?? $sourceType;
    }

    public static function getSourceDisplay(string $sourceType, int $sourceId = 0): string
    {
        $sourceDesc = self::getSourceDesc($sourceType);
        if ($sourceDesc === '-') {
            return '-';
        }

        if ($sourceId > 0) {
            return $sourceDesc . ' #' . $sourceId;
        }

        return $sourceDesc;
    }

    public static function getMonthCalendar(int $providerId, string $month = ''): array
    {
        [$month, $startDate, $endDate, $totalDays] = self::parseMonthRange($month);

        $records = ProviderSchedule::where('provider_id', $providerId)
            ->where('service_date', '>=', $startDate)
            ->where('service_date', '<=', $endDate)
            ->whereNull('delete_time')
            ->select()
            ->toArray();

        $recordMap = [];
        foreach ($records as $record) {
            $recordMap[(string)$record['service_date']] = $record;
        }

        $summary = [
            self::STATUS_AVAILABLE => 0,
            self::STATUS_LOCKED => 0,
            self::STATUS_OCCUPIED => 0,
            self::STATUS_UNAVAILABLE => 0,
            'editable' => 0,
        ];
        $statusHints = self::getStatusHintMap();
        $days = [];

        for ($day = 1; $day <= $totalDays; $day++) {
            $serviceDate = sprintf('%s-%02d', $month, $day);
            $record = $recordMap[$serviceDate] ?? [];
            $status = (string)($record['status'] ?? self::STATUS_AVAILABLE);
            $sourceType = (string)($record['source_type'] ?? self::SOURCE_MANUAL);
            $sourceId = (int)($record['source_id'] ?? 0);
            $canEdit = in_array($status, [self::STATUS_AVAILABLE, self::STATUS_UNAVAILABLE], true);
            $weekdayIndex = (int)date('N', strtotime($serviceDate));

            $summary[$status] = (int)($summary[$status] ?? 0) + 1;
            if ($canEdit) {
                $summary['editable']++;
            }

            $days[] = [
                'service_date' => $serviceDate,
                'day' => $day,
                'weekday_index' => $weekdayIndex,
                'status' => $status,
                'status_desc' => self::getStatusMap()[$status] ?? '-',
                'status_hint' => $statusHints[$status] ?? '',
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'source_desc' => $canEdit
                    ? self::getSourceDesc((string)($record['source_type'] ?? ''))
                    : self::getSourceDesc($sourceType),
                'source_display' => $canEdit
                    ? '-'
                    : self::getSourceDisplay($sourceType, $sourceId),
                'remark' => trim((string)($record['remark'] ?? '')),
                'can_edit' => $canEdit ? 1 : 0,
                'is_today' => $serviceDate === date('Y-m-d') ? 1 : 0,
                'is_weekend' => $weekdayIndex >= 6 ? 1 : 0,
            ];
        }

        return [
            'month' => $month,
            'month_start' => $startDate,
            'month_end' => $endDate,
            'total_days' => $totalDays,
            'first_weekday_index' => (int)date('N', strtotime($startDate)),
            'summary' => $summary,
            'days' => $days,
        ];
    }

    public static function saveProviderDates(
        int $providerId,
        array $serviceDates,
        string $status = self::STATUS_UNAVAILABLE,
        string $remark = ''
    ): array {
        if (!in_array($status, [self::STATUS_AVAILABLE, self::STATUS_UNAVAILABLE], true)) {
            throw new \RuntimeException('服务人员仅可维护可预约或不可服务状态');
        }

        $serviceDates = self::normalizeServiceDates($serviceDates);
        $remark = trim($remark);
        $processed = [];

        Db::transaction(function () use ($providerId, $serviceDates, $status, $remark, &$processed) {
            foreach ($serviceDates as $serviceDate) {
                $record = ProviderSchedule::where([
                    'provider_id' => $providerId,
                    'service_date' => $serviceDate,
                ])->whereNull('delete_time')->lock(true)->findOrEmpty();

                $currentStatus = $record->isEmpty() ? self::STATUS_AVAILABLE : (string)$record['status'];
                if (!in_array($currentStatus, [self::STATUS_AVAILABLE, self::STATUS_UNAVAILABLE], true)) {
                    throw new \RuntimeException(sprintf(
                        '%s 当前为%s，仅支持只读查看，请等待订单流转自动释放',
                        $serviceDate,
                        self::getStatusMap()[$currentStatus] ?? $currentStatus
                    ));
                }

                if ($status === self::STATUS_AVAILABLE) {
                    if (!$record->isEmpty()) {
                        ProviderSchedule::destroy((int)$record['id']);
                    }
                } else {
                    $saveData = [
                        'provider_id' => $providerId,
                        'service_date' => $serviceDate,
                        'status' => self::STATUS_UNAVAILABLE,
                        'source_type' => self::SOURCE_MANUAL,
                        'source_id' => 0,
                        'remark' => $remark,
                        'update_time' => time(),
                    ];

                    if ($record->isEmpty()) {
                        $saveData['create_time'] = time();
                        ProviderSchedule::create($saveData);
                    } else {
                        $saveData['id'] = (int)$record['id'];
                        ProviderSchedule::update($saveData);
                    }
                }

                $processed[] = [
                    'service_date' => $serviceDate,
                    'status' => $status,
                    'status_desc' => self::getStatusMap()[$status] ?? '-',
                ];
            }
        });

        return [
            'count' => count($processed),
            'status' => $status,
            'status_desc' => self::getStatusMap()[$status] ?? '-',
            'service_dates' => array_column($processed, 'service_date'),
            'items' => $processed,
        ];
    }

    public static function deleteProviderDates(int $providerId, array $serviceDates): array
    {
        return self::saveProviderDates($providerId, $serviceDates, self::STATUS_AVAILABLE);
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

    public static function releaseOccupied(
        int $providerId,
        string $serviceDate,
        string $sourceType = '',
        int $sourceId = 0
    ): array {
        $record = self::getRecord($providerId, $serviceDate);
        if ($record->isEmpty()) {
            return [
                'provider_id' => $providerId,
                'service_date' => trim($serviceDate),
                'status' => self::STATUS_AVAILABLE,
            ];
        }

        if ((string)$record['status'] !== self::STATUS_OCCUPIED) {
            throw new \RuntimeException('当前档期不可释放占用');
        }
        if ($sourceType !== '' && (string)$record['source_type'] !== trim($sourceType)) {
            throw new \RuntimeException('档期来源不匹配，无法释放占用');
        }
        if ($sourceId > 0 && (int)$record['source_id'] !== $sourceId) {
            throw new \RuntimeException('档期来源业务不匹配，无法释放占用');
        }

        ProviderSchedule::destroy((int)$record['id']);

        return [
            'provider_id' => $providerId,
            'service_date' => trim($serviceDate),
            'status' => self::STATUS_AVAILABLE,
        ];
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

    private static function parseMonthRange(string $month = ''): array
    {
        $month = trim($month) !== '' ? trim($month) : date('Y-m');
        $monthDate = \DateTime::createFromFormat('Y-m', $month);
        if (!$monthDate || $monthDate->format('Y-m') !== $month) {
            throw new \RuntimeException('月份格式不正确');
        }

        $startDate = $monthDate->format('Y-m-01');
        $endDate = $monthDate->format('Y-m-t');
        $totalDays = (int)$monthDate->format('t');

        return [$month, $startDate, $endDate, $totalDays];
    }

    private static function normalizeServiceDates(array $serviceDates): array
    {
        $serviceDates = array_values(array_unique(array_filter(array_map(function ($item) {
            return trim((string)$item);
        }, $serviceDates))));

        if (empty($serviceDates)) {
            throw new \RuntimeException('请选择至少一个服务日期');
        }

        foreach ($serviceDates as $serviceDate) {
            $date = \DateTime::createFromFormat('Y-m-d', $serviceDate);
            if (!$date || $date->format('Y-m-d') !== $serviceDate) {
                throw new \RuntimeException('服务日期格式不正确');
            }
        }

        sort($serviceDates);
        return $serviceDates;
    }
}
