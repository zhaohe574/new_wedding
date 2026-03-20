<?php

declare(strict_types=1);

namespace app\common\model\wedding;

use app\common\enum\PayEnum;
use app\common\enum\wedding\ServiceOrderEnum;
use app\common\enum\wedding\ServiceOrderPaymentTypeEnum;
use app\common\model\BaseModel;

class ServiceOrder extends BaseModel
{
    protected $name = 'service_order';

    public function getOrderStatusDescAttr($value, $data): string
    {
        return ServiceOrderEnum::getStatusDesc((int)($data['order_status'] ?? 0));
    }

    public function getPaymentTypeDescAttr($value, $data): string
    {
        return ServiceOrderPaymentTypeEnum::getDesc((int)($data['payment_type'] ?? 0));
    }

    public function getPayStatusDescAttr($value, $data): string
    {
        return PayEnum::getPayStatusDesc((int)($data['pay_status'] ?? 0));
    }
}

