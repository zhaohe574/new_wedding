<?php

declare(strict_types=1);

namespace app\common\model\wedding;

use app\common\enum\wedding\ProviderChangeRequestEnum;
use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class ProviderChangeRequest extends BaseModel
{
    use SoftDelete;

    protected $name = 'provider_change_request';

    protected $deleteTime = 'delete_time';

    public function getBeforeSnapshotAttr($value): array
    {
        $data = json_decode((string)$value, true);
        return is_array($data) ? $data : [];
    }

    public function getAfterSnapshotAttr($value): array
    {
        $data = json_decode((string)$value, true);
        return is_array($data) ? $data : [];
    }

    public function getChangeTypeDescAttr($value, $data): string
    {
        return ProviderChangeRequestEnum::getChangeTypeDesc((string)($data['change_type'] ?? ''));
    }

    public function getAuditStatusDescAttr($value, $data): string
    {
        return ProviderChangeRequestEnum::getAuditStatusDesc((int)($data['audit_status'] ?? 0));
    }

    public function getAuditRoleDescAttr($value, $data): string
    {
        return ProviderChangeRequestEnum::getAuditRoleDesc((string)($data['audit_role'] ?? ''));
    }
}
