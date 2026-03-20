<?php

declare(strict_types=1);

namespace app\common\enum\wedding;

class ServiceOrderPaymentTypeEnum
{
    public const ONLINE = 1;
    public const OFFLINE_VOUCHER = 2;

    public static function getDesc($value = true)
    {
        $data = [
            self::ONLINE => '在线支付',
            self::OFFLINE_VOUCHER => '线下凭证',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }
}

