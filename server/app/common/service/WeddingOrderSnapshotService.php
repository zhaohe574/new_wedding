<?php

declare(strict_types=1);

namespace app\common\service;

class WeddingOrderSnapshotService
{
    public static function buildPreviewSnapshot(array $context): array
    {
        return [
            'service_date' => (string)($context['service_date'] ?? ''),
            'region' => [
                'province_code' => (string)($context['region']['province_code'] ?? ''),
                'province_name' => (string)($context['region']['province_name'] ?? ''),
                'city_code' => (string)($context['region']['city_code'] ?? ''),
                'city_name' => (string)($context['region']['city_name'] ?? ''),
                'district_code' => (string)($context['region']['district_code'] ?? ''),
                'district_name' => (string)($context['region']['district_name'] ?? ''),
            ],
            'pricing' => [
                'price' => round((float)($context['pricing']['price'] ?? 0), 2),
                'price_match_level' => (string)($context['pricing']['matched_level'] ?? ''),
                'matched_region_code' => (string)($context['pricing']['matched_region_code'] ?? ''),
                'matched_region_name' => (string)($context['pricing']['matched_region_name'] ?? ''),
            ],
            'provider' => $context['provider'] ?? [],
            'package' => $context['package'] ?? [],
            'profile_summary' => $context['profile_summary'] ?? [],
            'template_summary' => [
                'template_id' => (int)($context['template']['template_id'] ?? 0),
                'template_name' => (string)($context['template']['template_name'] ?? ''),
                'pages' => $context['template']['summary_pages'] ?? [],
            ],
        ];
    }
}
