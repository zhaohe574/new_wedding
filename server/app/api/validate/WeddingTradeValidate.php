<?php

declare(strict_types=1);

namespace app\api\validate;

use app\common\service\ServiceRegionService;
use app\common\validate\BaseValidate;

class WeddingTradeValidate extends BaseValidate
{
    protected $rule = [
        'category_id' => 'require|integer|gt:0',
        'provider_id' => 'require|integer|gt:0',
        'package_id' => 'require|integer|gt:0',
        'district_code' => 'require|checkDistrictCode',
        'service_date' => 'require|dateFormat:Y-m-d',
        'tag_ids' => 'checkTagIds',
        'keyword' => 'max:30',
        'price_sort' => 'checkPriceSort',
        'template_form_data' => 'array',
    ];

    protected $message = [
        'category_id.require' => '服务分类不能为空',
        'category_id.integer' => '服务分类参数不正确',
        'category_id.gt' => '服务分类参数不正确',
        'provider_id.require' => '服务人员不能为空',
        'provider_id.integer' => '服务人员参数不正确',
        'provider_id.gt' => '服务人员参数不正确',
        'package_id.require' => '服务套餐不能为空',
        'package_id.integer' => '服务套餐参数不正确',
        'package_id.gt' => '服务套餐参数不正确',
        'district_code.require' => '请选择服务区县',
        'service_date.require' => '请选择服务日期',
        'service_date.dateFormat' => '服务日期格式不正确',
        'keyword.max' => '搜索关键词不能超过30个字符',
        'template_form_data.array' => '服务内容填写结果格式不正确',
    ];

    protected $scene = [
        'providerLists' => ['category_id', 'district_code', 'service_date', 'tag_ids', 'keyword', 'price_sort'],
        'providerDetail' => ['provider_id', 'district_code', 'service_date'],
        'orderPreview' => ['provider_id', 'package_id', 'district_code', 'service_date', 'template_form_data'],
    ];

    public function checkDistrictCode($value): bool|string
    {
        $districtCode = trim((string)$value);
        if ($districtCode === '') {
            return '请选择服务区县';
        }

        return empty(ServiceRegionService::getDistrictDetail($districtCode)) ? '服务区县不正确' : true;
    }

    public function checkTagIds($value): bool|string
    {
        $tagIds = trim((string)$value);
        if ($tagIds === '') {
            return true;
        }

        foreach (explode(',', $tagIds) as $tagId) {
            if ((int)$tagId <= 0) {
                return '风格筛选参数不正确';
            }
        }

        return true;
    }

    public function checkPriceSort($value): bool|string
    {
        $priceSort = trim((string)$value);
        if ($priceSort === '') {
            return true;
        }

        return in_array($priceSort, ['asc', 'desc'], true) ? true : '价格排序参数不正确';
    }
}
