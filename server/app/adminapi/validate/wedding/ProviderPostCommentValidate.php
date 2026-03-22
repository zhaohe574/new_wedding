<?php

declare(strict_types=1);

namespace app\adminapi\validate\wedding;

use app\common\validate\BaseValidate;

class ProviderPostCommentValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'audit_status' => 'require|in:1,2',
        'audit_remark' => 'max:500',
    ];

    protected $message = [
        'id.require' => '评论参数缺失',
        'id.integer' => '评论参数不正确',
        'id.gt' => '评论参数不正确',
        'audit_status.require' => '审核结果不能为空',
        'audit_status.in' => '审核结果不正确',
        'audit_remark.max' => '审核说明最多500个字符',
    ];

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneAudit()
    {
        return $this->only(['id', 'audit_status', 'audit_remark']);
    }
}
