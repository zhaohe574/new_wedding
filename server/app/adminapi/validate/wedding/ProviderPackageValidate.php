<?php

declare(strict_types=1);

namespace app\adminapi\validate\wedding;

use app\common\model\wedding\ProviderPackage;
use app\common\model\wedding\ServiceProvider;
use app\common\validate\BaseValidate;

class ProviderPackageValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|checkPackage',
        'provider_id' => 'require|integer|gt:0|checkProvider',
        'name' => 'require|length:1,80',
        'summary' => 'max:1000',
        'service_duration' => 'max:120',
        'status' => 'require|in:0,1',
        'sort' => 'integer|egt:0',
        'area_prices' => 'require|array|checkAreaPrices',
    ];

    protected $message = [
        'id.require' => '套餐 ID 不能为空',
        'provider_id.require' => '请选择服务人员',
        'provider_id.integer' => '服务人员不正确',
        'provider_id.gt' => '服务人员不正确',
        'name.require' => '请输入套餐名称',
        'name.length' => '套餐名称长度须在 1-80 位字符',
        'summary.max' => '套餐简介长度不能超过 1000 个字符',
        'service_duration.max' => '服务时长长度不能超过 120 个字符',
        'status.require' => '请选择状态',
        'status.in' => '套餐状态不正确',
        'sort.integer' => '排序值不正确',
        'sort.egt' => '排序值不正确',
        'area_prices.require' => '请至少配置一条地区价格',
        'area_prices.array' => '地区价格格式不正确',
    ];

    public function sceneAdd()
    {
        return $this->remove('id', true);
    }

    public function sceneEdit()
    {
        return $this->only(['id', 'provider_id', 'name', 'summary', 'service_duration', 'status', 'sort', 'area_prices']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneStatus()
    {
        return $this->only(['id', 'status']);
    }

    public function checkPackage($value): bool|string
    {
        return ProviderPackage::findOrEmpty((int)$value)->isEmpty() ? '套餐不存在' : true;
    }

    public function checkProvider($value): bool|string
    {
        return ServiceProvider::findOrEmpty((int)$value)->isEmpty() ? '服务人员不存在' : true;
    }

    public function checkAreaPrices($value): bool|string
    {
        if (!is_array($value) || empty($value)) {
            return '请至少配置一条地区价格';
        }

        foreach ($value as $item) {
            if (!is_array($item)) {
                return '地区价格格式不正确';
            }
        }

        return true;
    }
}
