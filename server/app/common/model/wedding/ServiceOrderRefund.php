<?php

declare(strict_types=1);

namespace app\common\model\wedding;

use app\common\enum\wedding\ServiceOrderRefundEnum;
use app\common\model\BaseModel;

class ServiceOrderRefund extends BaseModel
{
    protected $name = 'service_order_refund';

    public function getStatusDescAttr($value, $data): string
    {
        return ServiceOrderRefundEnum::getStatusDesc((int)($data['status'] ?? 0));
    }

    public function getApplySourceDescAttr($value, $data): string
    {
        return ServiceOrderRefundEnum::getApplySourceDesc((string)($data['apply_source'] ?? ''));
    }
}
