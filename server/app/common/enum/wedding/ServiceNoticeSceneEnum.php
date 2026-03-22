<?php

declare(strict_types=1);

namespace app\common\enum\wedding;

class ServiceNoticeSceneEnum
{
    public const ORDER_CREATED = 201;
    public const ORDER_WAIT_PAY = 202;
    public const ORDER_PROVIDER_ACCEPTED = 203;
    public const ORDER_PROVIDER_REJECTED = 204;
    public const ORDER_PAY_SUCCESS = 205;
    public const ORDER_OFFLINE_VOUCHER_SUBMITTED = 206;
    public const ORDER_OFFLINE_VOUCHER_APPROVED = 207;
    public const ORDER_OFFLINE_VOUCHER_REJECTED = 208;
    public const ORDER_RESCHEDULE_APPLIED = 209;
    public const ORDER_RESCHEDULE_RESULT = 210;
    public const ORDER_WAIT_REVIEW = 211;
    public const ORDER_REVIEW_SUBMITTED = 212;
    public const ORDER_REVIEW_RESULT = 213;
    public const ORDER_REFUND_APPLIED = 214;
    public const ORDER_REFUND_RESULT = 215;
}
