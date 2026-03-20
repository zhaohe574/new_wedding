<?php

declare(strict_types=1);

namespace app\common\model\wedding;

use app\common\model\BaseModel;

class ServiceContentTemplate extends BaseModel
{
    protected $name = 'service_content_template';

    public function getStatusDescAttr($value, $data): string
    {
        return (int)($data['status'] ?? 0) === 1 ? '启用' : '停用';
    }
}
