<?php

declare(strict_types=1);

namespace app\adminapi\logic\wedding;

use app\common\logic\BaseLogic;
use app\common\model\wedding\ProviderSchedule;
use app\common\model\wedding\ServiceProvider;
use app\common\service\ProviderScheduleService;

class ProviderScheduleLogic extends BaseLogic
{
    public static function add(array $params): void
    {
        ProviderScheduleService::syncStatus(
            (int)$params['provider_id'],
            (string)$params['service_date'],
            (string)$params['status'],
            ProviderScheduleService::SOURCE_MANUAL,
            0,
            (string)($params['remark'] ?? '')
        );
    }

    public static function edit(array $params): bool
    {
        try {
            $record = ProviderSchedule::findOrEmpty((int)$params['id']);
            if ($record->isEmpty()) {
                throw new \RuntimeException('档期记录不存在');
            }

            if ((int)$record['provider_id'] !== (int)$params['provider_id'] || (string)$record['service_date'] !== (string)$params['service_date']) {
                ProviderSchedule::destroy((int)$record['id']);
            }

            ProviderScheduleService::syncStatus(
                (int)$params['provider_id'],
                (string)$params['service_date'],
                (string)$params['status'],
                ProviderScheduleService::SOURCE_MANUAL,
                0,
                (string)($params['remark'] ?? '')
            );
            return true;
        } catch (\Throwable $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    public static function delete(array $params): void
    {
        ProviderSchedule::destroy((int)$params['id']);
    }

    public static function detail(array $params): array
    {
        return ProviderSchedule::findOrEmpty((int)$params['id'])->toArray();
    }

    public static function getProviderOptions(): array
    {
        return ServiceProvider::alias('provider')
            ->leftJoin('service_category category', 'category.id = provider.category_id')
            ->whereNull('provider.delete_time')
            ->field([
                'provider.id',
                'provider.name',
                'category.name' => 'category_name',
            ])
            ->order(['provider.sort' => 'desc', 'provider.id' => 'desc'])
            ->select()
            ->toArray();
    }
}
