<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\PayEnum;
use app\common\enum\wedding\ServiceOrderChangeEnum;
use app\common\enum\wedding\ServiceOrderEnum;
use app\common\enum\wedding\ProviderChangeRequestEnum;
use app\common\enum\wedding\ServiceOrderReviewEnum;
use app\common\model\wedding\ProviderChangeRequest;
use app\common\model\wedding\ProviderPost;
use app\common\model\wedding\ProviderPostComment;
use app\common\model\wedding\ServiceOrder;
use app\common\model\wedding\ServiceOrderChange;
use app\common\model\wedding\ServiceOrderRefund;
use app\common\model\wedding\ServiceOrderReview;

class WeddingWorkbenchStatService
{
    public static function getOverview(): array
    {
        [$todayStart, $todayEnd] = self::getDayRange(0);
        $time = date('Y-m-d H:i:s');
        $todayOrderCount = self::countOrdersByCreateTime($todayStart, $todayEnd);
        $totalOrderCount = self::countAllOrders();
        $todayPaidAmount = self::sumPaidAmount($todayStart, $todayEnd);
        $totalPaidAmount = self::sumPaidAmount();
        $waitProviderConfirmCount = self::countOrdersByStatus(ServiceOrderEnum::WAIT_PROVIDER_CONFIRM);
        $waitOfflineVoucherAuditCount = self::countOrdersByStatus(ServiceOrderEnum::WAIT_OFFLINE_VOUCHER_AUDIT);
        $waitRescheduleCount = self::countPendingReschedules();
        $waitReviewAuditCount = self::countPendingReviews();
        $waitRefundCount = self::countPendingRefunds();
        $waitProfileChangeCount = self::countPendingProfileChanges();
        $waitPostAuditCount = self::countPendingPosts();
        $waitCommentAuditCount = self::countPendingComments();
        $orderTrend = self::buildOrderTrend(15);
        $paymentTrend = self::buildPaymentTrend(7);

        return [
            'time' => $time,
            'today' => [
                'time' => $time,
                'today_order_count' => $todayOrderCount,
                'total_order_count' => $totalOrderCount,
                'today_paid_amount' => $todayPaidAmount,
                'total_paid_amount' => $totalPaidAmount,
                'wait_provider_confirm_count' => $waitProviderConfirmCount,
                'wait_offline_voucher_audit_count' => $waitOfflineVoucherAuditCount,
                'wait_reschedule_count' => $waitRescheduleCount,
                'wait_review_audit_count' => $waitReviewAuditCount,
                'wait_refund_count' => $waitRefundCount,
                'wait_profile_change_count' => $waitProfileChangeCount,
                'wait_post_audit_count' => $waitPostAuditCount,
                'wait_comment_audit_count' => $waitCommentAuditCount,
                'order_count' => $todayOrderCount,
                'order_total_count' => $totalOrderCount,
                'paid_amount' => $todayPaidAmount,
                'paid_total_amount' => $totalPaidAmount,
            ],
            'todo' => [
                'wait_provider_confirm_count' => $waitProviderConfirmCount,
                'wait_offline_voucher_audit_count' => $waitOfflineVoucherAuditCount,
                'wait_reschedule_count' => $waitRescheduleCount,
                'wait_review_audit_count' => $waitReviewAuditCount,
                'wait_refund_count' => $waitRefundCount,
                'wait_profile_change_count' => $waitProfileChangeCount,
                'wait_post_audit_count' => $waitPostAuditCount,
                'wait_comment_audit_count' => $waitCommentAuditCount,
            ],
            'order_trend' => [
                'date' => $orderTrend['date'],
                'data' => $orderTrend['data'],
                'labels' => $orderTrend['date'],
                'values' => $orderTrend['data'],
            ],
            'payment_trend' => [
                'date' => $paymentTrend['date'],
                'data' => $paymentTrend['data'],
                'labels' => $paymentTrend['date'],
                'values' => $paymentTrend['data'],
            ],
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

    private static function countPendingRefunds(): int
    {
        return (int)ServiceOrderRefund::whereNull('delete_time')
            ->where('status', 0)
            ->count();
    }

    private static function countPendingProfileChanges(): int
    {
        return (int)ProviderChangeRequest::whereNull('delete_time')
            ->where('audit_status', ProviderChangeRequestEnum::AUDIT_STATUS_PENDING)
            ->count();
    }

    private static function countPendingPosts(): int
    {
        return (int)ProviderPost::whereNull('delete_time')
            ->where('audit_status', ServiceOrderReviewEnum::AUDIT_PENDING)
            ->count();
    }

    private static function countPendingComments(): int
    {
        return (int)ProviderPostComment::whereNull('delete_time')
            ->where('audit_status', ServiceOrderReviewEnum::AUDIT_PENDING)
            ->count();
    }

    private static function buildOrderTrend(int $days): array
    {
        $date = [];
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
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
        for ($i = $days - 1; $i >= 0; $i--) {
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
