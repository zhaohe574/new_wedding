<?php

declare(strict_types=1);

namespace app\adminapi\validate\wedding;

use app\common\model\wedding\ProviderSchedule;
use app\common\model\wedding\ServiceProvider;
use app\common\validate\BaseValidate;

class ProviderScheduleValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|checkSchedule',
        'provider_id' => 'require|integer|gt:0|checkProvider',
        'service_date' => 'require|dateFormat:Y-m-d',
        'status' => 'require|in:available,locked,occupied,unavailable',
        'remark' => 'max:500',
    ];

    protected $message = [
        'id.require' => '档期 ID 不能为空',
        'provider_id.require' => '请选择服务人员',
        'provider_id.integer' => '服务人员不正确',
        'provider_id.gt' => '服务人员不正确',
        'service_date.require' => '请选择服务日期',
        'service_date.dateFormat' => '服务日期格式不正确',
        'status.require' => '请选择档期状态',
        'status.in' => '档期状态不正确',
        'remark.max' => '备注长度不能超过 500 个字符',
    ];

    public function sceneAdd()
    {
        return $this->remove('id', true);
    }

    public function sceneEdit()
    {
        return $this->only(['id', 'provider_id', 'service_date', 'status', 'remark']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function checkSchedule($value): bool|string
    {
        return ProviderSchedule::findOrEmpty((int)$value)->isEmpty() ? '档期记录不存在' : true;
    }

    public function checkProvider($value): bool|string
    {
        return ServiceProvider::findOrEmpty((int)$value)->isEmpty() ? '服务人员不存在' : true;
    }
}
