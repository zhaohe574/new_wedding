<?php

declare(strict_types=1);

namespace app\common\model\wedding;

use app\common\enum\wedding\ServiceOrderReviewEnum;
use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class ProviderPostComment extends BaseModel
{
    use SoftDelete;

    protected $name = 'provider_post_comment';

    protected $deleteTime = 'delete_time';

    public function getAuditStatusDescAttr($value, $data): string
    {
        return ServiceOrderReviewEnum::getAuditStatusDesc((int)($data['audit_status'] ?? 0));
    }

    public function getAuditRoleDescAttr($value, $data): string
    {
        return ServiceOrderReviewEnum::getAuditRoleDesc((string)($data['audit_role'] ?? ''));
    }

    public function getStatusDescAttr($value, $data): string
    {
        return (int)($data['status'] ?? 0) === 1 ? '启用' : '停用';
    }
}
