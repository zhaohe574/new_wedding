<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\model\wedding\WeddingProfile;

class WeddingProfileService
{
    public static function getUserProfile(int $userId): array
    {
        $profile = WeddingProfile::where(['user_id' => $userId])
            ->whereNull('delete_time')
            ->findOrEmpty();

        return self::formatProfile($profile->isEmpty() ? [] : $profile->toArray());
    }

    public static function saveUserProfile(int $userId, array $params): array
    {
        $districtCode = trim((string)($params['district_code'] ?? ''));
        $districtDetail = $districtCode !== '' ? ServiceRegionService::getDistrictDetail($districtCode) : [];
        if ($districtCode !== '' && empty($districtDetail)) {
            throw new \RuntimeException('宴会地区不正确');
        }

        $stylePreference = $params['style_preference'] ?? [];
        if (is_array($stylePreference)) {
            $stylePreference = array_values(array_filter(array_map(function ($item) {
                return trim((string)$item);
            }, $stylePreference)));
            $stylePreference = json_encode($stylePreference, JSON_UNESCAPED_UNICODE);
        } else {
            $stylePreference = trim((string)$stylePreference);
        }

        $saveData = [
            'user_id' => $userId,
            'wedding_date' => trim((string)($params['wedding_date'] ?? '')) ?: null,
            'province_code' => $districtDetail['province_code'] ?? '',
            'province_name' => $districtDetail['province_name'] ?? '',
            'city_code' => $districtDetail['city_code'] ?? '',
            'city_name' => $districtDetail['city_name'] ?? '',
            'district_code' => $districtDetail['district_code'] ?? '',
            'district_name' => $districtDetail['district_name'] ?? '',
            'banquet_hotel' => trim((string)($params['banquet_hotel'] ?? '')),
            'table_count' => (int)($params['table_count'] ?? 0),
            'budget_min' => round((float)($params['budget_min'] ?? 0), 2),
            'budget_max' => round((float)($params['budget_max'] ?? 0), 2),
            'style_preference' => $stylePreference,
            'contact_name' => trim((string)($params['contact_name'] ?? '')),
            'contact_mobile' => trim((string)($params['contact_mobile'] ?? '')),
            'remark' => trim((string)($params['remark'] ?? '')),
            'update_time' => time(),
        ];

        $profile = WeddingProfile::where(['user_id' => $userId])
            ->whereNull('delete_time')
            ->findOrEmpty();
        if ($profile->isEmpty()) {
            $saveData['create_time'] = time();
            WeddingProfile::create($saveData);
        } else {
            $saveData['id'] = (int)$profile['id'];
            WeddingProfile::update($saveData);
        }

        return self::getUserProfile($userId);
    }

    public static function formatProfile(array $profile): array
    {
        if (empty($profile)) {
            return [
                'id' => 0,
                'user_id' => 0,
                'wedding_date' => '',
                'province_code' => '',
                'province_name' => '',
                'city_code' => '',
                'city_name' => '',
                'district_code' => '',
                'district_name' => '',
                'banquet_hotel' => '',
                'table_count' => 0,
                'budget_min' => 0,
                'budget_max' => 0,
                'style_preference' => [],
                'contact_name' => '',
                'contact_mobile' => '',
                'remark' => '',
            ];
        }

        $stylePreference = $profile['style_preference'] ?? '';
        if (is_string($stylePreference) && $stylePreference !== '') {
            $decoded = json_decode($stylePreference, true);
            $stylePreference = is_array($decoded) ? $decoded : [$stylePreference];
        }

        $profile['style_preference'] = is_array($stylePreference) ? $stylePreference : [];
        $profile['budget_min'] = round((float)($profile['budget_min'] ?? 0), 2);
        $profile['budget_max'] = round((float)($profile['budget_max'] ?? 0), 2);
        $profile['table_count'] = (int)($profile['table_count'] ?? 0);
        return $profile;
    }
}
