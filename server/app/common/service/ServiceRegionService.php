<?php

declare(strict_types=1);

namespace app\common\service;

class ServiceRegionService
{
    private static array $cache = [];

    /**
     * 复用 uniapp 现有地区编码资源，避免在 V1 基线阶段维护两套编码口径。
     */
    private static function getAddressData(string $fileName): array
    {
        if (isset(self::$cache[$fileName])) {
            return self::$cache[$fileName];
        }

        $path = app()->getRootPath() . '../uniapp/src/uni_modules/vk-uview-ui/libs/address/' . $fileName;
        if (!is_file($path)) {
            return [];
        }

        $content = file_get_contents($path);
        $data = json_decode($content ?: '[]', true);
        self::$cache[$fileName] = is_array($data) ? $data : [];
        return self::$cache[$fileName];
    }

    public static function getProvinceCityOptions(): array
    {
        $provinces = self::getAddressData('provinces.json');
        $cities = self::getAddressData('citys.json');
        $options = [];

        foreach ($provinces as $provinceIndex => $province) {
            $children = [];
            foreach (($cities[$provinceIndex] ?? []) as $city) {
                $children[] = [
                    'label' => $city['name'],
                    'value' => (string)$city['code'],
                ];
            }

            $options[] = [
                'label' => $province['name'],
                'value' => (string)$province['code'],
                'children' => $children,
            ];
        }

        return $options;
    }

    public static function getCityDetail(string $cityCode): array
    {
        $cityCode = trim($cityCode);
        if ($cityCode === '') {
            return [];
        }

        $provinces = self::getAddressData('provinces.json');
        $cities = self::getAddressData('citys.json');

        foreach ($provinces as $provinceIndex => $province) {
            foreach (($cities[$provinceIndex] ?? []) as $city) {
                if ((string)$city['code'] !== $cityCode) {
                    continue;
                }

                return [
                    'province_code' => (string)$province['code'],
                    'province_name' => $province['name'],
                    'city_code' => (string)$city['code'],
                    'city_name' => $city['name'],
                ];
            }
        }

        return [];
    }

    public static function getDistrictDetail(string $districtCode): array
    {
        $districtCode = trim($districtCode);
        if ($districtCode === '') {
            return [];
        }

        $provinces = self::getAddressData('provinces.json');
        $cities = self::getAddressData('citys.json');
        $areas = self::getAddressData('areas.json');

        foreach ($provinces as $provinceIndex => $province) {
            foreach (($cities[$provinceIndex] ?? []) as $cityIndex => $city) {
                foreach (($areas[$provinceIndex][$cityIndex] ?? []) as $district) {
                    if ((string)$district['code'] !== $districtCode) {
                        continue;
                    }

                    return [
                        'province_code' => (string)$province['code'],
                        'province_name' => $province['name'],
                        'city_code' => (string)$city['code'],
                        'city_name' => $city['name'],
                        'district_code' => (string)$district['code'],
                        'district_name' => $district['name'],
                    ];
                }
            }
        }

        return [];
    }

    public static function getRegionDetailByPath(array $regionCodes): array
    {
        $regionCodes = array_values(array_filter(array_map('strval', $regionCodes)));
        if (empty($regionCodes)) {
            return [];
        }

        $provinces = self::getAddressData('provinces.json');
        $cities = self::getAddressData('citys.json');
        $areas = self::getAddressData('areas.json');

        foreach ($provinces as $provinceIndex => $province) {
            if ((string)$province['code'] !== ($regionCodes[0] ?? '')) {
                continue;
            }

            $detail = [
                'region_level' => 'province',
                'province_code' => (string)$province['code'],
                'province_name' => $province['name'],
                'city_code' => '',
                'city_name' => '',
                'district_code' => '',
                'district_name' => '',
                'region_code' => (string)$province['code'],
                'region_name' => $province['name'],
            ];

            if (!isset($regionCodes[1])) {
                return $detail;
            }

            foreach (($cities[$provinceIndex] ?? []) as $cityIndex => $city) {
                if ((string)$city['code'] !== $regionCodes[1]) {
                    continue;
                }

                $detail['region_level'] = 'city';
                $detail['city_code'] = (string)$city['code'];
                $detail['city_name'] = $city['name'];
                $detail['region_code'] = (string)$city['code'];
                $detail['region_name'] = $city['name'];

                if (!isset($regionCodes[2])) {
                    return $detail;
                }

                foreach (($areas[$provinceIndex][$cityIndex] ?? []) as $district) {
                    if ((string)$district['code'] !== $regionCodes[2]) {
                        continue;
                    }

                    $detail['region_level'] = 'district';
                    $detail['district_code'] = (string)$district['code'];
                    $detail['district_name'] = $district['name'];
                    $detail['region_code'] = (string)$district['code'];
                    $detail['region_name'] = $district['name'];
                    return $detail;
                }
            }
        }

        return [];
    }

    public static function getOpenRegionCascaderOptions(array $openCityCodes): array
    {
        $openCityCodes = array_values(array_unique(array_filter(array_map('strval', $openCityCodes))));
        if (empty($openCityCodes)) {
            return [];
        }

        $provinces = self::getAddressData('provinces.json');
        $cities = self::getAddressData('citys.json');
        $areas = self::getAddressData('areas.json');
        $options = [];

        foreach ($provinces as $provinceIndex => $province) {
            $cityChildren = [];
            foreach (($cities[$provinceIndex] ?? []) as $cityIndex => $city) {
                if (!in_array((string)$city['code'], $openCityCodes, true)) {
                    continue;
                }

                $districtChildren = [];
                foreach (($areas[$provinceIndex][$cityIndex] ?? []) as $district) {
                    $districtChildren[] = [
                        'label' => $district['name'],
                        'value' => (string)$district['code'],
                    ];
                }

                $cityChildren[] = [
                    'label' => $city['name'],
                    'value' => (string)$city['code'],
                    'children' => $districtChildren,
                ];
            }

            if (empty($cityChildren)) {
                continue;
            }

            $options[] = [
                'label' => $province['name'],
                'value' => (string)$province['code'],
                'children' => $cityChildren,
            ];
        }

        return $options;
    }

    public static function getOpenRegionTree(array $openCityCodes): array
    {
        $openCityCodes = array_values(array_unique(array_filter(array_map('strval', $openCityCodes))));
        if (empty($openCityCodes)) {
            return [];
        }

        $provinces = self::getAddressData('provinces.json');
        $cities = self::getAddressData('citys.json');
        $areas = self::getAddressData('areas.json');
        $tree = [];

        foreach ($provinces as $provinceIndex => $province) {
            $cityChildren = [];
            foreach (($cities[$provinceIndex] ?? []) as $cityIndex => $city) {
                if (!in_array((string)$city['code'], $openCityCodes, true)) {
                    continue;
                }

                $districtChildren = [];
                foreach (($areas[$provinceIndex][$cityIndex] ?? []) as $district) {
                    $districtChildren[] = [
                        'code' => (string)$district['code'],
                        'name' => $district['name'],
                        'level' => 'district',
                    ];
                }

                if (empty($districtChildren)) {
                    continue;
                }

                $cityChildren[] = [
                    'code' => (string)$city['code'],
                    'name' => $city['name'],
                    'level' => 'city',
                    'children' => $districtChildren,
                ];
            }

            if (empty($cityChildren)) {
                continue;
            }

            $tree[] = [
                'code' => (string)$province['code'],
                'name' => $province['name'],
                'level' => 'province',
                'children' => $cityChildren,
            ];
        }

        return $tree;
    }
}
