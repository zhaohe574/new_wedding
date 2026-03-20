<?php

declare(strict_types=1);

namespace app\api\validate;

use app\common\service\ServiceRegionService;
use app\common\validate\BaseValidate;

class WeddingProfileValidate extends BaseValidate
{
    protected $rule = [
        'wedding_date' => 'dateFormat:Y-m-d',
        'district_code' => 'checkDistrictCode',
        'banquet_hotel' => 'max:120',
        'table_count' => 'integer|egt:0',
        'budget_min' => 'float|egt:0',
        'budget_max' => 'float|egt:0|checkBudgetRange',
        'style_preference' => 'array|checkStylePreference',
        'contact_name' => 'max:60',
        'contact_mobile' => 'max:32',
        'remark' => 'max:1000',
    ];

    protected $message = [
        'wedding_date.dateFormat' => '婚礼日期格式不正确',
        'banquet_hotel.max' => '宴会场地长度不能超过 120 个字符',
        'table_count.integer' => '桌数规模格式不正确',
        'table_count.egt' => '桌数规模不能小于 0',
        'budget_min.float' => '预算下限格式不正确',
        'budget_min.egt' => '预算下限不能小于 0',
        'budget_max.float' => '预算上限格式不正确',
        'budget_max.egt' => '预算上限不能小于 0',
        'style_preference.array' => '风格偏好格式不正确',
        'contact_name.max' => '联系人长度不能超过 60 个字符',
        'contact_mobile.max' => '联系方式长度不能超过 32 个字符',
        'remark.max' => '备注长度不能超过 1000 个字符',
    ];

    public function checkDistrictCode($value): bool|string
    {
        $districtCode = trim((string)$value);
        if ($districtCode === '') {
            return true;
        }

        return empty(ServiceRegionService::getDistrictDetail($districtCode)) ? '宴会地区不正确' : true;
    }

    public function checkBudgetRange($value, $rule, $data): bool|string
    {
        $budgetMin = round((float)($data['budget_min'] ?? 0), 2);
        $budgetMax = round((float)$value, 2);
        if ($budgetMax > 0 && $budgetMax < $budgetMin) {
            return '预算上限不能小于预算下限';
        }

        return true;
    }

    public function checkStylePreference($value): bool|string
    {
        if (!is_array($value)) {
            return '风格偏好格式不正确';
        }

        foreach ($value as $item) {
            if (mb_strlen(trim((string)$item)) > 40) {
                return '风格偏好单项长度不能超过 40 个字符';
            }
        }

        return true;
    }
}
