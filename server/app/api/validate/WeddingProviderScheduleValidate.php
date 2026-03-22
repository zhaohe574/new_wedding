<?php

declare(strict_types=1);

namespace app\api\validate;

use app\common\validate\BaseValidate;

class WeddingProviderScheduleValidate extends BaseValidate
{
    protected $rule = [
        'month' => 'dateFormat:Y-m',
        'service_dates' => 'require|array|checkServiceDates',
        'status' => 'require|in:available,unavailable',
        'remark' => 'max:500',
    ];

    protected $message = [
        'month.dateFormat' => '月份格式不正确',
        'service_dates.require' => '请选择服务日期',
        'service_dates.array' => '服务日期格式不正确',
        'status.require' => '请选择档期状态',
        'status.in' => '仅支持维护可预约或不可服务',
        'remark.max' => '备注长度不能超过 500 个字符',
    ];

    protected $scene = [
        'month' => ['month'],
        'upsert' => ['service_dates', 'status', 'remark'],
        'delete' => ['service_dates'],
    ];

    public function checkServiceDates($value): bool|string
    {
        if (!is_array($value) || empty($value)) {
            return '请选择服务日期';
        }

        if (count($value) > 31) {
            return '单次最多维护 31 个日期';
        }

        foreach ($value as $item) {
            $serviceDate = trim((string)$item);
            $date = \DateTime::createFromFormat('Y-m-d', $serviceDate);
            if ($serviceDate === '' || !$date || $date->format('Y-m-d') !== $serviceDate) {
                return '服务日期格式不正确';
            }
        }

        return true;
    }
}
