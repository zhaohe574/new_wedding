<?php

declare(strict_types=1);

namespace app\common\model\wedding;

use app\common\model\BaseModel;

class ProviderSchedule extends BaseModel
{
    protected $name = 'provider_schedule';

    public function getStatusDescAttr($value, $data): string
    {
        $map = [
            'available' => '可预约',
            'locked' => '已锁定',
            'occupied' => '已占用',
            'unavailable' => '不可服务',
        ];

        return $map[$data['status'] ?? ''] ?? '-';
    }
}
