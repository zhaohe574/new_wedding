<?php

declare(strict_types=1);

namespace app\common\model\wedding;

use app\common\model\BaseModel;

class ProviderPackageAreaPrice extends BaseModel
{
    protected $name = 'provider_package_area_price';

    public function getPriceAttr($value): float
    {
        return round((float)$value, 2);
    }

    public function getStatusDescAttr($value, $data): string
    {
        return (int)($data['status'] ?? 0) === 1 ? '启用' : '停用';
    }

    public function getRegionLevelDescAttr($value, $data): string
    {
        $map = [
            'province' => '省级',
            'city' => '市级',
            'district' => '县区级',
        ];

        return $map[$data['region_level'] ?? ''] ?? '-';
    }
}
