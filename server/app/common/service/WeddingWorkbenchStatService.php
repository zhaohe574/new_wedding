<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\PayEnum;
use app\common\enum\wedding\ServiceOrderChangeEnum;
use app\common\enum\wedding\ServiceOrderEnum;
use app\common\enum\wedding\ServiceOrderReviewEnum;
use app\common\model\wedding\ServiceOrder;
use app\common\model\wedding\ServiceOrderChange;
use app\common\model\wedding\ServiceOrderReview;

class WeddingWorkbenchStatService
{
    public static function getOverview(): array
    {
        [$todayStart, $todayEnd] = self::getDayRange(0);

        return [
            'today' => [
                'time' => date('Y-m-d H:i:s'),
                'today_order_count' => self::countOrdersByCreateTime($todayStart, $todayEnd),
                'total_order_count' => self::countAllOrders(),
                'today_paid_amount' => self::sumPaidAmount($todayStart, $todayEnd),
                'total_paid_amount' => self::sumPaidAmount(),
                'wait_provider_confirm_count' => self::countOrdersByStatus(ServiceOrderEnum::WAIT_PROVIDER_CONFIRM),
                'wait_offline_voucher_audit_count' => self::countOrdersByStatus(ServiceOrderEnum::WAIT_OFFLINE_VOUCHER_AUDIT),
                'wait_reschedule_count' => self::countPendingReschedules(),
                'wait_review_audit_count' => self::countPendingReviews(),
            ],
            'order_trend' => self::buildOrderTrend(15),
            'payment_trend' => self::buildPaymentTrend(7),
        ];
    }

    public static function buildWorkbenchPayload(): array
    {
        $overview = self::getOverview();

        return [
            'today' => [
                'time' => $overview['today']['time'],
                'today_sales' => self::formatAmount($overview['today']['today_paid_amount']),
                'total_sales' => self::formatAmount($overview['today']['total_paid_amount']),
                'today_visitor' => $overview['today']['wait_reschedule_count'],
                'total_visitor' => $overview['today']['wait_review_audit_count'],
                'today_new_user' => $overview['today']['wait_provider_confirm_count'],
                'total_new_user' => $overview['today']['wait_offline_voucher_audit_count'],
                'order_num' => $overview['today']['today_order_count'],
                'order_sum' => $overview['today']['total_order_count'],
            ],
            'visitor' => [
                'date' => $overview['order_trend']['date'],
                'list' => [
                    [
                        'name' => '订单数',
                        'data' => $overview['order_trend']['data'],
                    ],
                ],
            ],
            'sale' => [
                'date' => $overview['payment_trend']['date'],
                'list' => [
                    [
                        'name' => '支付金额',
                        'data' => $overview['payment_trend']['data'],
                    ],
                ],
            ],
        ];
    }

    private static function countOrdersByCreateTime(?int $start = null, ?int $end = null): int
    {
        $query = ServiceOrder::whereNull('delete_time');
        if ($start !== null && $end !== null) {
            $query->whereBetween('create_time', [$start, $end]);
        }
        return (int)$query->count();
    }

    private static function countAllOrders(): int
    {
        return (int)ServiceOrder::whereNull('delete_time')->count();
    }

    private static function sumPaidAmount(?int $start = null, ?int $end = null): float
    {
        $query = ServiceOrder::whereNull('delete_time')
            ->where('pay_status', PayEnum::ISPAID);
        if ($start !== null && $end !== null) {
            $query->whereBetween('pay_time', [$start, $end]);
        }
        return round((float)$query->sum('order_amount'), 2);
    }

    private static function countOrdersByStatus(int $status): int
    {
        return (int)ServiceOrder::whereNull('delete_time')
            ->where('order_status', $status)
            ->count();
    }

    private static function countPendingReschedules(): int
    {
        return (int)ServiceOrderChange::whereNull('delete_time')
            ->where('status', ServiceOrderChangeEnum::PENDING)
            ->count();
    }

    private static function countPendingReviews(): int
    {
        return (int)ServiceOrderReview::whereNull('delete_time')
            ->where('audit_status', ServiceOrderReviewEnum::AUDIT_PENDING)
            ->count();
    }

    private static function buildOrderTrend(int $days): array
    {
        $date = [];
        $data = [];
        for ($i = 0; $i < $days; $i++) {
            [$start, $end] = self::getDayRange($i);
            $date[] = date('m/d', $start);
            $data[] = self::countOrdersByCreateTime($start, $end);
        }
        return [
            'date' => $date,
            'data' => $data,
        ];
    }

    private static function buildPaymentTrend(int $days): array
    {
        $date = [];
        $data = [];
        for ($i = 0; $i < $days; $i++) {
            [$start, $end] = self::getDayRange($i);
            $date[] = date('m/d', $start);
            $data[] = self::formatAmount(self::sumPaidAmount($start, $end));
        }
        return [
            'date' => $date,
            'data' => $data,
        ];
    }

    private static function getDayRange(int $beforeDays): array
    {
        $start = strtotime(date('Y-m-d 00:00:00', strtotime('-' . $beforeDays . ' day')));
        $end = strtotime(date('Y-m-d 23:59:59', strtotime('-' . $beforeDays . ' day')));
        return [$start, $end];
    }

    private static function formatAmount(float $amount): float
    {
        return round($amount, 2);
    }
}
