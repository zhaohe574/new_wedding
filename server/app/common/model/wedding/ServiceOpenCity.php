<?php

declare(strict_types=1);

namespace app\common\model\wedding;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class ServiceOpenCity extends BaseModel
{
    use SoftDelete;

    protected $name = 'service_open_city';

    protected $deleteTime = 'delete_time';

    public function getStatusDescAttr($value, $data): string
    {
        return (int)($data['status'] ?? 0) === 1 ? '开放中' : '已停用';
    }
}
