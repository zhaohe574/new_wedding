<?php

declare(strict_types=1);

namespace app\common\enum\wedding;

class ServiceOrderRefundEnum
{
    public const STATUS_PENDING = 0;
    public const STATUS_REJECTED = 1;
    public const STATUS_REFUNDING = 2;
    public const STATUS_REFUNDED = 3;
    public const STATUS_REFUND_FAILED = 4;

    public const APPLY_SOURCE_USER = 'user';
    public const APPLY_SOURCE_ADMIN = 'admin';

    public static function getStatusDesc($value = true)
    {
        $data = [
            self::STATUS_PENDING => '待处理',
            self::STATUS_REJECTED => '已驳回',
            self::STATUS_REFUNDING => '退款中',
            self::STATUS_REFUNDED => '已退款',
            self::STATUS_REFUND_FAILED => '退款失败',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    public static function getApplySourceDesc($value = true)
    {
        $data = [
            self::APPLY_SOURCE_USER => '用户申请',
            self::APPLY_SOURCE_ADMIN => '后台发起',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    public static function getTerminalStatusList(): array
    {
        return [
            self::STATUS_REJECTED,
            self::STATUS_REFUNDED,
            self::STATUS_REFUND_FAILED,
        ];
    }
}
