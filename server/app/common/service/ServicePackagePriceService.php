<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\model\wedding\ProviderPackage;
use app\common\model\wedding\ProviderPackageAreaPrice;
use app\common\model\wedding\ServiceOpenCity;

class ServicePackagePriceService
{
    public static function getMatchedPriceByPackage(int $packageId, string $districtCode): array
    {
        $package = ProviderPackage::where(['id' => $packageId, 'status' => 1])
            ->whereNull('delete_time')
            ->findOrEmpty();
        if ($package->isEmpty()) {
            return [];
        }

        $districtDetail = ServiceRegionService::getDistrictDetail($districtCode);
        if (empty($districtDetail)) {
            return [];
        }

        $isOpenCity = ServiceOpenCity::where([
            'city_code' => $districtDetail['city_code'],
            'status' => 1,
        ])->whereNull('delete_time')->count() > 0;

        if (!$isOpenCity) {
            return [];
        }

        $candidates = [
            'district' => $districtDetail['district_code'],
            'city' => $districtDetail['city_code'],
            'province' => $districtDetail['province_code'],
        ];

        foreach ($candidates as $regionLevel => $regionCode) {
            $price = ProviderPackageAreaPrice::where([
                'package_id' => $packageId,
                'region_level' => $regionLevel,
                'region_code' => $regionCode,
                'status' => 1,
            ])->whereNull('delete_time')
                ->order(['sort' => 'desc', 'id' => 'desc'])
                ->findOrEmpty();

            if ($price->isEmpty()) {
                continue;
            }

            return [
                'package_id' => $packageId,
                'district_code' => $districtDetail['district_code'],
                'district_name' => $districtDetail['district_name'],
                'city_code' => $districtDetail['city_code'],
                'city_name' => $districtDetail['city_name'],
                'province_code' => $districtDetail['province_code'],
                'province_name' => $districtDetail['province_name'],
                'matched_level' => $price['region_level'],
                'matched_region_code' => $price['region_code'],
                'matched_region_name' => $price['region_name'],
                'price' => round((float)$price['price'], 2),
            ];
        }

        return [];
    }
}
