<?php

declare(strict_types=1);

namespace app\adminapi\logic\wedding;

use app\common\logic\BaseLogic;
use app\common\model\wedding\ServiceOpenCity;
use app\common\service\ServiceRegionService;

class ServiceOpenCityLogic extends BaseLogic
{
    public static function add(array $params): void
    {
        $cityDetail = ServiceRegionService::getCityDetail((string)$params['city_code']);
        ServiceOpenCity::create([
            'province_code' => $cityDetail['province_code'],
            'province_name' => $cityDetail['province_name'],
            'city_code' => $cityDetail['city_code'],
            'city_name' => $cityDetail['city_name'],
            'sort' => (int)($params['sort'] ?? 0),
            'status' => (int)$params['status'],
            'create_time' => time(),
            'update_time' => time(),
        ]);
    }

    public static function edit(array $params): bool
    {
        try {
            $cityDetail = ServiceRegionService::getCityDetail((string)$params['city_code']);
            ServiceOpenCity::update([
                'id' => (int)$params['id'],
                'province_code' => $cityDetail['province_code'],
                'province_name' => $cityDetail['province_name'],
                'city_code' => $cityDetail['city_code'],
                'city_name' => $cityDetail['city_name'],
                'sort' => (int)($params['sort'] ?? 0),
                'status' => (int)$params['status'],
                'update_time' => time(),
            ]);
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    public static function delete(array $params): void
    {
        ServiceOpenCity::destroy((int)$params['id']);
    }

    public static function detail(array $params): array
    {
        return ServiceOpenCity::findOrEmpty((int)$params['id'])->toArray();
    }

    public static function updateStatus(array $params): bool
    {
        ServiceOpenCity::update([
            'id' => (int)$params['id'],
            'status' => (int)$params['status'],
            'update_time' => time(),
        ]);
        return true;
    }

    public static function getCityOptions(): array
    {
        return ServiceRegionService::getProvinceCityOptions();
    }
}
