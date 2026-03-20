<?php

declare(strict_types=1);

namespace app\common\enum\wedding;

class ServiceOrderEnum
{
    public const WAIT_PROVIDER_CONFIRM = 10;
    public const PROVIDER_REJECTED = 11;
    public const WAIT_PAY = 20;
    public const WAIT_OFFLINE_VOUCHER_AUDIT = 21;
    public const WAIT_SERVICE = 30;
    public const IN_SERVICE = 40;
    public const WAIT_REVIEW = 50;
    public const REVIEW_PENDING_AUDIT = 51;
    public const FINISHED = 60;
    public const CANCELED = 70;
    public const REFUNDING = 80;
    public const REFUNDED = 81;

    public const CANCEL_SOURCE_USER = 'user';
    public const CANCEL_SOURCE_PROVIDER = 'provider';
    public const CANCEL_SOURCE_ADMIN = 'admin';
    public const CANCEL_SOURCE_SYSTEM = 'system';

    public static function getStatusDesc($value = true)
    {
        $data = [
            self::WAIT_PROVIDER_CONFIRM => '待服务人员确认',
            self::PROVIDER_REJECTED => '服务人员已拒绝',
            self::WAIT_PAY => '待支付',
            self::WAIT_OFFLINE_VOUCHER_AUDIT => '待线下凭证审核',
            self::WAIT_SERVICE => '待履约',
            self::IN_SERVICE => '履约中',
            self::WAIT_REVIEW => '待评价',
            self::REVIEW_PENDING_AUDIT => '评价待审核',
            self::FINISHED => '已完成',
            self::CANCELED => '已取消',
            self::REFUNDING => '退款中',
            self::REFUNDED => '已退款',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }
}

