<?php

declare(strict_types=1);

namespace app\common\enum\wedding;

class ServiceOrderReviewEnum
{
    public const AUDIT_PENDING = 0;
    public const AUDIT_APPROVED = 1;
    public const AUDIT_REJECTED = 2;

    public const AUDIT_ROLE_ADMIN = 'admin';
    public const AUDIT_ROLE_PROVIDER = 'provider';

    public static function getAuditStatusDesc($value = true)
    {
        $data = [
            self::AUDIT_PENDING => '待审核',
            self::AUDIT_APPROVED => '审核通过',
            self::AUDIT_REJECTED => '审核驳回',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    public static function getAuditRoleDesc($value = true)
    {
        $data = [
            self::AUDIT_ROLE_ADMIN => '管理员审核',
            self::AUDIT_ROLE_PROVIDER => '服务人员审核',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }
}
