<?php

declare(strict_types=1);

namespace app\common\enum\wedding;

class ServiceOrderChangeEnum
{
    public const PENDING = 0;
    public const APPROVED = 1;
    public const REJECTED = 2;

    public const HANDLE_ROLE_PROVIDER = 'provider';
    public const HANDLE_ROLE_ADMIN = 'admin';

    public static function getStatusDesc($value = true)
    {
        $data = [
            self::PENDING => '待处理',
            self::APPROVED => '已通过',
            self::REJECTED => '已驳回',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    public static function getHandleRoleDesc($value = true)
    {
        $data = [
            self::HANDLE_ROLE_PROVIDER => '服务人员',
            self::HANDLE_ROLE_ADMIN => '管理员',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }
}
