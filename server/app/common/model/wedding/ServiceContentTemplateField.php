<?php

declare(strict_types=1);

namespace app\common\model\wedding;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class ServiceContentTemplateField extends BaseModel
{
    use SoftDelete;

    protected $name = 'service_content_template_field';

    protected $deleteTime = 'delete_time';

    public function getRequiredDescAttr($value, $data): string
    {
        return (int)($data['required'] ?? 0) === 1 ? '必填' : '选填';
    }
}
