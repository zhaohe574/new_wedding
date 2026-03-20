<?php

declare(strict_types=1);

namespace app\common\enum\wedding;

class ServiceOrderOfflineVoucherEnum
{
    public const PENDING = 0;
    public const APPROVED = 1;
    public const REJECTED = 2;

    public static function getDesc($value = true)
    {
        $data = [
            self::PENDING => '待审核',
            self::APPROVED => '审核通过',
            self::REJECTED => '审核驳回',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }
}

