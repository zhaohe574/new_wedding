<?php

declare(strict_types=1);

namespace app\common\model\wedding;

use app\common\enum\wedding\ServiceOrderChangeEnum;
use app\common\model\BaseModel;

class ServiceOrderChange extends BaseModel
{
    protected $name = 'service_order_change';

    public function getStatusDescAttr($value, $data): string
    {
        return ServiceOrderChangeEnum::getStatusDesc((int)($data['status'] ?? 0));
    }

    public function getHandleRoleDescAttr($value, $data): string
    {
        return ServiceOrderChangeEnum::getHandleRoleDesc((string)($data['handle_role'] ?? ''));
    }
}
