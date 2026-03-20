<?php

declare(strict_types=1);

namespace app\adminapi\validate\wedding;

use app\common\model\wedding\WeddingProfile;
use app\common\validate\BaseValidate;

class WeddingProfileValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|checkProfile',
    ];

    protected $message = [
        'id.require' => '婚礼档案 ID 不能为空',
    ];

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function checkProfile($value): bool|string
    {
        return WeddingProfile::findOrEmpty((int)$value)->isEmpty() ? '婚礼档案不存在' : true;
    }
}
