<?php

declare(strict_types=1);

namespace app\adminapi\validate\wedding;

use app\common\model\wedding\ServiceOrder;
use app\common\model\wedding\ServiceOrderRefund;
use app\common\validate\BaseValidate;

class ServiceOrderRefundValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0|checkRefund',
        'order_id' => 'require|integer|gt:0|checkOrder',
        'status' => 'integer',
        'apply_source' => 'in:user,admin',
        'audit_status' => 'require|in:1,2',
        'apply_reason' => 'require|max:500',
        'handle_remark' => 'max:500',
    ];

    protected $message = [
        'id.require' => '退款申请参数缺失',
        'id.integer' => '退款申请参数不正确',
        'id.gt' => '退款申请参数不正确',
        'order_id.require' => '订单参数缺失',
        'order_id.integer' => '订单参数不正确',
        'order_id.gt' => '订单参数不正确',
        'status.integer' => '状态参数不正确',
        'apply_source.in' => '申请来源参数不正确',
        'audit_status.require' => '处理结果不能为空',
        'audit_status.in' => '处理结果不正确',
        'apply_reason.require' => '请填写退款原因',
        'apply_reason.max' => '退款原因最多500个字符',
        'handle_remark.max' => '处理备注最多500个字符',
    ];

    public function sceneLists()
    {
        return $this->only(['status', 'apply_source']);
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneHandle()
    {
        return $this->only(['id', 'audit_status', 'handle_remark']);
    }

    public function sceneManualRefund()
    {
        return $this->only(['order_id', 'apply_reason', 'handle_remark']);
    }

    public function checkRefund($value): bool|string
    {
        return ServiceOrderRefund::findOrEmpty((int)$value)->isEmpty() ? '退款申请不存在' : true;
    }

    public function checkOrder($value): bool|string
    {
        return ServiceOrder::findOrEmpty((int)$value)->isEmpty() ? '订单不存在' : true;
    }
}
