<?php

declare(strict_types=1);

namespace app\adminapi\validate\wedding;

use app\common\model\wedding\ServiceOrderChange;
use app\common\validate\BaseValidate;

class ServiceOrderChangeValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0|checkChange',
        'status' => 'integer',
        'audit_status' => 'require|in:1,2',
        'audit_remark' => 'max:500',
    ];

    protected $message = [
        'id.require' => '改期申请参数缺失',
        'id.integer' => '改期申请参数不正确',
        'id.gt' => '改期申请参数不正确',
        'status.integer' => '状态参数不正确',
        'audit_status.require' => '处理结果不能为空',
        'audit_status.in' => '处理结果不正确',
        'audit_remark.max' => '处理说明最多500个字符',
    ];

    public function sceneLists()
    {
        return $this->only(['status']);
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneHandle()
    {
        return $this->only(['id', 'audit_status', 'audit_remark']);
    }

    public function checkChange($value): bool|string
    {
        return ServiceOrderChange::findOrEmpty((int)$value)->isEmpty() ? '改期申请不存在' : true;
    }
}
