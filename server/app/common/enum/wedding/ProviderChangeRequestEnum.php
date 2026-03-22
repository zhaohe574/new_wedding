<?php

declare(strict_types=1);

namespace app\common\enum\wedding;

class ProviderChangeRequestEnum
{
    public const CHANGE_TYPE_PROFILE = 'profile';
    public const CHANGE_TYPE_CERTIFICATE = 'certificate';
    public const CHANGE_TYPE_WORK = 'work';
    public const CHANGE_TYPE_PACKAGE = 'package';

    public const AUDIT_STATUS_PENDING = 0;
    public const AUDIT_STATUS_APPROVED = 1;
    public const AUDIT_STATUS_REJECTED = 2;

    public const AUDIT_ROLE_ADMIN = 'admin';

    public static function getChangeTypeDesc($value = true)
    {
        $data = [
            self::CHANGE_TYPE_PROFILE => '资料',
            self::CHANGE_TYPE_CERTIFICATE => '证书',
            self::CHANGE_TYPE_WORK => '作品',
            self::CHANGE_TYPE_PACKAGE => '套餐',
        ];

        if ($value === true) {
            return $data;
        }

        return $data[$value] ?? '';
    }

    public static function getAuditStatusDesc($value = true)
    {
        $data = [
            self::AUDIT_STATUS_PENDING => '待审核',
            self::AUDIT_STATUS_APPROVED => '审核通过',
            self::AUDIT_STATUS_REJECTED => '审核驳回',
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
        ];

        if ($value === true) {
            return $data;
        }

        return $data[$value] ?? '';
    }
}
