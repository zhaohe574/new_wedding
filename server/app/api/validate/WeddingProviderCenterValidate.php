<?php

declare(strict_types=1);

namespace app\api\validate;

use app\common\validate\BaseValidate;

class WeddingProviderCenterValidate extends BaseValidate
{
    protected $rule = [
        'page_no' => 'integer|gt:0',
        'page_size' => 'integer|between:1,20',
        'id' => 'require|integer|gt:0',
        'change_type' => 'in:profile,certificate,work,package',
        'payload' => 'require',
        'audit_status' => 'in:0,1,2',
    ];

    protected $message = [
        'page_no.integer' => '页码参数不正确',
        'page_no.gt' => '页码参数不正确',
        'page_size.integer' => '分页参数不正确',
        'page_size.between' => '分页参数不正确',
        'id.require' => '申请记录参数缺失',
        'id.integer' => '申请记录参数不正确',
        'id.gt' => '申请记录参数不正确',
        'change_type.in' => '变更类型不正确',
        'payload.require' => '变更内容不能为空',
        'audit_status.in' => '审核状态参数不正确',
    ];

    protected $scene = [
        'lists' => ['page_no', 'page_size', 'change_type', 'audit_status'],
        'detail' => ['id'],
    ];

    public function sceneSubmit()
    {
        return $this->only(['change_type', 'payload']);
    }
}
