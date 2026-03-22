<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\model\wedding\ProviderPackageAreaPrice;
use app\common\model\wedding\ServiceOpenCity;

class ProviderPackageDataService
{
    public static function normalizeAreaPrices(array $areaPrices): array
    {
        if (empty($areaPrices)) {
            throw new \RuntimeException('请至少配置一条地区价格');
        }

        $normalized = [];
        $uniqueKeys = [];

        foreach ($areaPrices as $item) {
            $regionCodes = array_values(array_filter(array_map('strval', $item['region_codes'] ?? [])));
            $regionDetail = ServiceRegionService::getRegionDetailByPath($regionCodes);
            if (empty($regionDetail)) {
                throw new \RuntimeException('存在无效的地区价格配置');
            }

            $isOpen = match ($regionDetail['region_level']) {
                'province' => ServiceOpenCity::where([
                    'province_code' => $regionDetail['province_code'],
                    'status' => 1,
                ])->whereNull('delete_time')->count() > 0,
                default => ServiceOpenCity::where([
                    'city_code' => $regionDetail['city_code'],
                    'status' => 1,
                ])->whereNull('delete_time')->count() > 0,
            };

            if (!$isOpen) {
                throw new \RuntimeException('地区价格只能配置在已开放城市及其下属县区范围内');
            }

            $uniqueKey = $regionDetail['region_level'] . ':' . $regionDetail['region_code'];
            if (isset($uniqueKeys[$uniqueKey])) {
                throw new \RuntimeException('同一套餐同一层级同一地区不可重复配置');
            }

            $uniqueKeys[$uniqueKey] = true;
            $price = round((float)($item['price'] ?? 0), 2);
            if ($price <= 0) {
                throw new \RuntimeException('地区价格必须大于 0');
            }

            $normalized[] = [
                'region_level' => $regionDetail['region_level'],
                'region_code' => $regionDetail['region_code'],
                'region_name' => $regionDetail['region_name'],
                'province_code' => $regionDetail['province_code'],
                'city_code' => $regionDetail['city_code'],
                'district_code' => $regionDetail['district_code'],
                'price' => $price,
                'status' => (int)!empty($item['status']),
                'sort' => (int)($item['sort'] ?? 0),
            ];
        }

        return $normalized;
    }

    public static function getAreaPricesByPackageId(int $packageId): array
    {
        $areaPrices = ProviderPackageAreaPrice::where(['package_id' => $packageId])
            ->whereNull('delete_time')
            ->order(['sort' => 'desc', 'id' => 'asc'])
            ->select()
            ->toArray();

        foreach ($areaPrices as &$item) {
            $item['region_codes'] = self::buildRegionCodes($item);
        }

        return $areaPrices;
    }

    public static function buildRegionCodes(array $item): array
    {
        $regionCodes = [(string)($item['province_code'] ?? '')];
        if (($item['region_level'] ?? '') === 'city') {
            $regionCodes[] = (string)($item['city_code'] ?? '');
        }
        if (($item['region_level'] ?? '') === 'district') {
            $regionCodes[] = (string)($item['city_code'] ?? '');
            $regionCodes[] = (string)($item['district_code'] ?? '');
        }

        return array_values(array_filter($regionCodes));
    }
}
