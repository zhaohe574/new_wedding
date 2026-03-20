<?php

declare(strict_types=1);

namespace app\adminapi\validate\wedding;

use app\common\model\wedding\ServiceOrder;
use app\common\validate\BaseValidate;

class ServiceOrderValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0|checkOrder',
        'order_id' => 'require|integer|gt:0|checkOrder',
        'order_status' => 'integer',
        'service_date_start' => 'dateFormat:Y-m-d',
        'service_date_end' => 'dateFormat:Y-m-d',
        'provider_id' => 'integer|gt:0',
        'user_id' => 'integer|gt:0',
        'audit_status' => 'require|in:1,2',
        'audit_remark' => 'max:500',
    ];

    protected $message = [
        'id.require' => '订单参数缺失',
        'id.integer' => '订单参数不正确',
        'id.gt' => '订单参数不正确',
        'order_id.require' => '订单参数缺失',
        'order_id.integer' => '订单参数不正确',
        'order_id.gt' => '订单参数不正确',
        'order_status.integer' => '订单状态参数不正确',
        'service_date_start.dateFormat' => '开始日期格式不正确',
        'service_date_end.dateFormat' => '结束日期格式不正确',
        'provider_id.integer' => '服务人员参数不正确',
        'provider_id.gt' => '服务人员参数不正确',
        'user_id.integer' => '用户参数不正确',
        'user_id.gt' => '用户参数不正确',
        'audit_status.require' => '审核状态不能为空',
        'audit_status.in' => '审核状态不正确',
        'audit_remark.max' => '审核说明最多500个字符',
    ];

    public function sceneLists()
    {
        return $this->only(['order_status', 'service_date_start', 'service_date_end', 'provider_id', 'user_id']);
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneOfflineVoucherAudit()
    {
        return $this->only(['order_id', 'audit_status', 'audit_remark']);
    }

    public function checkOrder($value): bool|string
    {
        return ServiceOrder::findOrEmpty((int)$value)->isEmpty() ? '订单不存在' : true;
    }
}

