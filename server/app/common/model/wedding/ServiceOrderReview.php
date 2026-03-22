<?php

declare(strict_types=1);

namespace app\common\model\wedding;

use app\common\enum\wedding\ServiceOrderReviewEnum;
use app\common\model\BaseModel;

class ServiceOrderReview extends BaseModel
{
    protected $name = 'service_order_review';

    public function getImagesAttr($value): array
    {
        $data = json_decode((string)$value, true);
        return is_array($data) ? $data : [];
    }

    public function getOrderSnapshotAttr($value): array
    {
        $data = json_decode((string)$value, true);
        return is_array($data) ? $data : [];
    }

    public function getAuditStatusDescAttr($value, $data): string
    {
        return ServiceOrderReviewEnum::getAuditStatusDesc((int)($data['audit_status'] ?? 0));
    }

    public function getAuditRoleDescAttr($value, $data): string
    {
        return ServiceOrderReviewEnum::getAuditRoleDesc((string)($data['audit_role'] ?? ''));
    }
}
