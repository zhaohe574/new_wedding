<?php

declare(strict_types=1);

namespace app\common\model\wedding;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class ProviderPackage extends BaseModel
{
    use SoftDelete;

    protected $name = 'provider_package';

    protected $deleteTime = 'delete_time';

    public function getStatusDescAttr($value, $data): string
    {
        return (int)($data['status'] ?? 0) === 1 ? '启用' : '停用';
    }
}
