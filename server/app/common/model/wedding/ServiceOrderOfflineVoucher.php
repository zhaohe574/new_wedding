<?php

declare(strict_types=1);

namespace app\common\model\wedding;

use app\common\enum\wedding\ServiceOrderOfflineVoucherEnum;
use app\common\model\BaseModel;

class ServiceOrderOfflineVoucher extends BaseModel
{
    protected $name = 'service_order_offline_voucher';

    public function getVoucherImagesAttr($value): array
    {
        $data = json_decode((string)$value, true);
        return is_array($data) ? $data : [];
    }

    public function getAuditStatusDescAttr($value, $data): string
    {
        return ServiceOrderOfflineVoucherEnum::getDesc((int)($data['audit_status'] ?? 0));
    }
}

