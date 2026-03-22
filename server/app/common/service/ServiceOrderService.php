<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\PayEnum;
use app\common\enum\wedding\ServiceNoticeSceneEnum;
use app\common\enum\wedding\ServiceOrderChangeEnum;
use app\common\enum\wedding\ServiceOrderEnum;
use app\common\enum\wedding\ServiceOrderOfflineVoucherEnum;
use app\common\enum\wedding\ServiceOrderPaymentTypeEnum;
use app\common\enum\wedding\ServiceOrderReviewEnum;
use app\common\model\wedding\ProviderSchedule;
use app\common\model\wedding\ServiceOrder;
use app\common\model\wedding\ServiceOrderChange;
use app\common\model\wedding\ServiceOrderOfflineVoucher;
use app\common\model\wedding\ServiceOrderReview;
use app\common\model\wedding\ServiceOrderSnapshot;
use app\common\model\wedding\ServiceProvider;
use think\facade\Db;

class ServiceOrderService
{
    public const PAY_FROM = 'service_order';

    public static function createOrder(int $userId, int $terminal, array $params): array
    {
        $tradeConfig = self::getTradeConfig();
        $paymentType = (int)($params['payment_type'] ?? ServiceOrderPaymentTypeEnum::ONLINE);
        self::assertPaymentTypeEnabled($paymentType, $tradeConfig);

        $preview = WeddingTradeService::buildOrderPreview(
            $userId,
            (int)$params['provider_id'],
            (int)$params['package_id'],
            trim((string)$params['district_code']),
            trim((string)$params['service_date']),
            $params['template_form_data'] ?? []
        );

        $orderId = 0;
        $orderSn = '';
        $now = time();
        $providerConfirmExpireTime = $now + max(1, (int)($tradeConfig['provider_confirm_timeout_minutes'] ?? 30)) * 60;
        $orderData = self::buildCreateOrderData($userId, $terminal, $paymentType, $preview, $providerConfirmExpireTime);
        $snapshotData = self::buildCreateSnapshotData($preview);

        Db::transaction(function () use ($orderData, $snapshotData, &$orderId, &$orderSn) {
            $order = ServiceOrder::create($orderData);
            $orderId = (int)$order->id;
            $orderSn = (string)$order['sn'];

            $snapshotData['order_id'] = $orderId;
            ServiceOrderSnapshot::create($snapshotData);

            WeddingOrderScheduleService::lockForPendingOrder(
                (int)$orderData['provider_id'],
                (string)$orderData['service_date'],
                $orderId,
                '订单创建待服务人员确认锁档'
            );
        });

        self::sendOrderCreatedNotices($orderId);

        return [
            'order_id' => $orderId,
            'order_sn' => $orderSn,
            'order_status' => ServiceOrderEnum::WAIT_PROVIDER_CONFIRM,
            'order_status_desc' => ServiceOrderEnum::getStatusDesc(ServiceOrderEnum::WAIT_PROVIDER_CONFIRM),
        ];
    }

    public static function getUserOrderLists(int $userId, array $params = []): array
    {
        $pageNo = max(1, (int)($params['page_no'] ?? 1));
        $pageSize = min(50, max(1, (int)($params['page_size'] ?? 10)));
        $offset = ($pageNo - 1) * $pageSize;

        $query = ServiceOrder::alias('order')
            ->leftJoin('service_provider provider', 'provider.id = order.provider_id')
            ->where('order.user_id', $userId)
            ->whereNull('order.delete_time')
            ->field([
                'order.id',
                'order.sn',
                'order.provider_id',
                'order.provider_name',
                'order.package_name',
                'order.service_date',
                'order.order_amount',
                'order.order_status',
                'order.payment_type',
                'order.pay_status',
                'order.create_time',
                'provider.avatar' => 'provider_avatar',
            ]);

        if (($params['order_status'] ?? '') !== '') {
            $query->where('order.order_status', (int)$params['order_status']);
        }

        $count = (clone $query)->count();
        $lists = $query->order(['order.id' => 'desc'])->limit($offset, $pageSize)->select()->toArray();
        foreach ($lists as &$item) {
            $item['provider_avatar'] = trim((string)($item['provider_avatar'] ?? '')) !== ''
                ? FileService::getFileUrl((string)$item['provider_avatar'])
                : '';
            self::appendOrderDesc($item);
        }

        return [
            'lists' => $lists,
            'count' => $count,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ];
    }

    public static function getUserOrderDetail(int $userId, int $orderId): array
    {
        $order = ServiceOrder::where([
            'id' => $orderId,
            'user_id' => $userId,
        ])->whereNull('delete_time')->findOrEmpty();
        if ($order->isEmpty()) {
            throw new \RuntimeException('订单不存在');
        }

        return self::buildOrderDetail($order->toArray(), true);
    }

    public static function cancelByUser(int $userId, int $orderId, string $reason = ''): bool
    {
        Db::transaction(function () use ($userId, $orderId, $reason) {
            $order = ServiceOrder::where([
                'id' => $orderId,
                'user_id' => $userId,
            ])->whereNull('delete_time')->lock(true)->findOrEmpty();
            if ($order->isEmpty()) {
                throw new \RuntimeException('订单不存在');
            }

            $allowedStatus = [
                ServiceOrderEnum::WAIT_PROVIDER_CONFIRM,
                ServiceOrderEnum::WAIT_PAY,
                ServiceOrderEnum::WAIT_OFFLINE_VOUCHER_AUDIT,
            ];
            if (!in_array((int)$order['order_status'], $allowedStatus, true)) {
                throw new \RuntimeException('当前状态不可取消订单');
            }

            ServiceOrder::update([
                'id' => (int)$order['id'],
                'order_status' => ServiceOrderEnum::CANCELED,
                'cancel_source' => ServiceOrderEnum::CANCEL_SOURCE_USER,
                'cancel_reason' => trim($reason),
                'update_time' => time(),
            ]);

            WeddingOrderScheduleService::releasePendingOrder(
                (int)$order['provider_id'],
                (string)$order['service_date']
            );
            self::closePendingRescheduleRequests((int)$order['id'], '订单已取消，改期申请已自动关闭');
        });

        return true;
    }

    public static function submitOfflineVoucher(int $userId, int $orderId, array $images, string $remark = ''): bool
    {
        $tradeConfig = self::getTradeConfig();
        if ((int)($tradeConfig['offline_voucher_enabled'] ?? 0) !== 1) {
            throw new \RuntimeException('线下凭证支付当前未开放');
        }

        $normalizedImages = self::normalizeVoucherImages($images);
        if (empty($normalizedImages)) {
            throw new \RuntimeException('请至少上传一张凭证图片');
        }

        Db::transaction(function () use ($userId, $orderId, $normalizedImages, $remark) {
            $order = ServiceOrder::where([
                'id' => $orderId,
                'user_id' => $userId,
            ])->whereNull('delete_time')->lock(true)->findOrEmpty();
            if ($order->isEmpty()) {
                throw new \RuntimeException('订单不存在');
            }

            if ((int)$order['order_status'] !== ServiceOrderEnum::WAIT_PAY) {
                throw new \RuntimeException('当前订单状态不可提交线下凭证');
            }
            if ((int)$order['payment_type'] !== ServiceOrderPaymentTypeEnum::OFFLINE_VOUCHER) {
                throw new \RuntimeException('当前订单不是线下凭证支付类型');
            }

            $existsVoucher = ServiceOrderOfflineVoucher::where(['order_id' => $orderId])
                ->whereNull('delete_time')
                ->lock(true)
                ->count();
            if ($existsVoucher > 0) {
                throw new \RuntimeException('该订单已提交过线下凭证');
            }

            ServiceOrderOfflineVoucher::create([
                'order_id' => (int)$order['id'],
                'user_id' => $userId,
                'provider_id' => (int)$order['provider_id'],
                'voucher_images' => json_encode($normalizedImages, JSON_UNESCAPED_UNICODE),
                'remark' => trim($remark),
                'audit_status' => ServiceOrderOfflineVoucherEnum::PENDING,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            ServiceOrder::update([
                'id' => (int)$order['id'],
                'order_status' => ServiceOrderEnum::WAIT_OFFLINE_VOUCHER_AUDIT,
                'update_time' => time(),
            ]);
        });

        self::sendOfflineVoucherSubmittedNotice($orderId);

        return true;
    }

    public static function getProviderByUserId(int $userId): ServiceProvider
    {
        return ServiceProvider::where([
            'user_id' => $userId,
            'status' => 1,
        ])->whereNull('delete_time')->findOrEmpty();
    }

    public static function getProviderPendingOrderLists(int $userId, array $params = []): array
    {
        $params['view_tab'] = 'pending_confirm';
        return self::getProviderOrderLists($userId, $params);
    }

    public static function getProviderOrderLists(int $userId, array $params = []): array
    {
        $provider = self::getProviderByUserId($userId);
        if ($provider->isEmpty()) {
            throw new \RuntimeException('当前账号未绑定服务人员');
        }

        $pageNo = max(1, (int)($params['page_no'] ?? 1));
        $pageSize = min(50, max(1, (int)($params['page_size'] ?? 10)));
        $offset = ($pageNo - 1) * $pageSize;
        $viewTab = trim((string)($params['view_tab'] ?? 'all'));

        $query = ServiceOrder::alias('order')
            ->leftJoin('user user', 'user.id = order.user_id')
            ->leftJoin(
                'service_order_change change_record',
                'change_record.order_id = order.id AND change_record.status = ' . ServiceOrderChangeEnum::PENDING . ' AND change_record.delete_time IS NULL'
            )
            ->leftJoin(
                'service_order_review review_record',
                'review_record.order_id = order.id AND review_record.audit_status = ' . ServiceOrderReviewEnum::AUDIT_PENDING . ' AND review_record.delete_time IS NULL'
            )
            ->where('order.provider_id', (int)$provider['id'])
            ->whereNull('order.delete_time')
            ->field([
                'order.id',
                'order.sn',
                'order.user_id',
                'order.provider_id',
                'order.provider_name',
                'order.package_name',
                'order.service_date',
                'order.order_amount',
                'order.order_status',
                'order.payment_type',
                'order.pay_status',
                'order.province_name',
                'order.city_name',
                'order.district_name',
                'order.provider_confirm_expire_time',
                'order.create_time',
                'user.nickname' => 'user_nickname',
                'user.mobile' => 'user_mobile',
                'change_record.id' => 'pending_change_id',
                'review_record.id' => 'pending_review_id',
                'review_record.audit_role' => 'pending_review_role',
            ]);

        if ($viewTab === 'pending_confirm') {
            $query->where('order.order_status', ServiceOrderEnum::WAIT_PROVIDER_CONFIRM);
        }
        if ($viewTab === 'wait_service') {
            $query->where('order.order_status', ServiceOrderEnum::WAIT_SERVICE);
        }
        if ($viewTab === 'reschedule_pending') {
            $query->where('change_record.id', '>', 0);
        }
        if ($viewTab === 'review_pending') {
            $query->where('review_record.id', '>', 0)
                ->where('review_record.audit_role', ServiceOrderReviewEnum::AUDIT_ROLE_PROVIDER);
        }
        if (($params['order_status'] ?? '') !== '') {
            $query->where('order.order_status', (int)$params['order_status']);
        }

        $count = (clone $query)->count();
        $lists = $query->order(['order.id' => 'desc'])->limit($offset, $pageSize)->select()->toArray();
        foreach ($lists as &$item) {
            self::appendOrderDesc($item);
            $item['has_pending_reschedule'] = (int)($item['pending_change_id'] ?? 0) > 0;
            $item['has_pending_review'] = (int)($item['pending_review_id'] ?? 0) > 0;
        }

        return [
            'lists' => $lists,
            'count' => $count,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ];
    }

    public static function getProviderOrderDetail(int $userId, int $orderId): array
    {
        $provider = self::getProviderByUserId($userId);
        if ($provider->isEmpty()) {
            throw new \RuntimeException('当前账号未绑定服务人员');
        }

        $order = ServiceOrder::alias('order')
            ->leftJoin('user user', 'user.id = order.user_id')
            ->where('order.id', $orderId)
            ->where('order.provider_id', (int)$provider['id'])
            ->whereNull('order.delete_time')
            ->field([
                'order.*',
                'user.nickname' => 'user_nickname',
                'user.mobile' => 'user_mobile',
            ])
            ->findOrEmpty();
        if ($order->isEmpty()) {
            throw new \RuntimeException('订单不存在');
        }

        return self::buildOrderDetail($order->toArray(), false);
    }

    public static function providerAcceptOrder(int $userId, int $orderId): bool
    {
        $provider = self::getProviderByUserId($userId);
        if ($provider->isEmpty()) {
            throw new \RuntimeException('当前账号未绑定服务人员');
        }

        Db::transaction(function () use ($provider, $orderId) {
            $order = ServiceOrder::where([
                'id' => $orderId,
                'provider_id' => (int)$provider['id'],
            ])->whereNull('delete_time')->lock(true)->findOrEmpty();
            if ($order->isEmpty()) {
                throw new \RuntimeException('订单不存在');
            }
            if ((int)$order['order_status'] !== ServiceOrderEnum::WAIT_PROVIDER_CONFIRM) {
                throw new \RuntimeException('当前订单状态不可接单');
            }

            $tradeConfig = self::getTradeConfig();
            self::assertPaymentTypeEnabled((int)$order['payment_type'], $tradeConfig);
            $payExpireTime = time() + max(1, (int)($tradeConfig['pay_timeout_minutes'] ?? 30)) * 60;

            ServiceOrder::update([
                'id' => (int)$order['id'],
                'order_status' => ServiceOrderEnum::WAIT_PAY,
                'provider_confirm_time' => time(),
                'pay_expire_time' => $payExpireTime,
                'update_time' => time(),
            ]);
        });

        self::sendOrderProviderAcceptedNotice($orderId);
        self::sendOrderWaitPayNotice($orderId);

        return true;
    }

    public static function providerRejectOrder(int $userId, int $orderId, string $reason): bool
    {
        $provider = self::getProviderByUserId($userId);
        if ($provider->isEmpty()) {
            throw new \RuntimeException('当前账号未绑定服务人员');
        }

        Db::transaction(function () use ($provider, $orderId, $reason) {
            $order = ServiceOrder::where([
                'id' => $orderId,
                'provider_id' => (int)$provider['id'],
            ])->whereNull('delete_time')->lock(true)->findOrEmpty();
            if ($order->isEmpty()) {
                throw new \RuntimeException('订单不存在');
            }
            if ((int)$order['order_status'] !== ServiceOrderEnum::WAIT_PROVIDER_CONFIRM) {
                throw new \RuntimeException('当前订单状态不可拒单');
            }

            ServiceOrder::update([
                'id' => (int)$order['id'],
                'order_status' => ServiceOrderEnum::PROVIDER_REJECTED,
                'provider_reject_reason' => trim($reason),
                'cancel_source' => ServiceOrderEnum::CANCEL_SOURCE_PROVIDER,
                'cancel_reason' => trim($reason),
                'update_time' => time(),
            ]);

            WeddingOrderScheduleService::releasePendingOrder(
                (int)$order['provider_id'],
                (string)$order['service_date']
            );
            self::closePendingRescheduleRequests((int)$order['id'], '订单已拒单，改期申请已自动关闭');
        });

        self::sendOrderRejectedNotice($orderId);

        return true;
    }

    public static function rescheduleApplyByUser(int $userId, int $orderId, string $newServiceDate, string $reason = ''): bool
    {
        $newServiceDate = trim($newServiceDate);
        if ($newServiceDate === '') {
            throw new \RuntimeException('请选择新的服务日期');
        }

        Db::transaction(function () use ($userId, $orderId, $newServiceDate, $reason) {
            $order = ServiceOrder::where([
                'id' => $orderId,
                'user_id' => $userId,
            ])->whereNull('delete_time')->lock(true)->findOrEmpty();
            if ($order->isEmpty()) {
                throw new \RuntimeException('订单不存在');
            }

            if (!in_array((int)$order['order_status'], [
                ServiceOrderEnum::WAIT_PAY,
                ServiceOrderEnum::WAIT_OFFLINE_VOUCHER_AUDIT,
                ServiceOrderEnum::WAIT_SERVICE,
            ], true)) {
                throw new \RuntimeException('当前订单状态不可申请改期');
            }

            if ((string)$order['service_date'] === $newServiceDate) {
                throw new \RuntimeException('新旧服务日期不能相同');
            }

            $pendingChange = ServiceOrderChange::where([
                'order_id' => (int)$order['id'],
                'status' => ServiceOrderChangeEnum::PENDING,
            ])->whereNull('delete_time')->lock(true)->findOrEmpty();
            if (!$pendingChange->isEmpty()) {
                throw new \RuntimeException('当前订单已有待处理改期申请');
            }

            ServiceOrderChange::create([
                'order_id' => (int)$order['id'],
                'user_id' => $userId,
                'provider_id' => (int)$order['provider_id'],
                'old_service_date' => (string)$order['service_date'],
                'new_service_date' => $newServiceDate,
                'apply_reason' => trim($reason),
                'status' => ServiceOrderChangeEnum::PENDING,
                'handle_role' => '',
                'handle_by' => 0,
                'handle_time' => 0,
                'handle_remark' => '',
                'create_time' => time(),
                'update_time' => time(),
            ]);
        });

        self::sendRescheduleAppliedNotice($orderId);
        return true;
    }

    public static function providerHandleReschedule(int $userId, int $changeId, int $auditStatus, string $remark = ''): bool
    {
        $provider = self::getProviderByUserId($userId);
        if ($provider->isEmpty()) {
            throw new \RuntimeException('当前账号未绑定服务人员');
        }

        self::handleReschedule(
            (int)$provider['id'],
            ServiceOrderChangeEnum::HANDLE_ROLE_PROVIDER,
            $changeId,
            $auditStatus,
            $remark,
            true
        );

        return true;
    }

    public static function adminHandleReschedule(int $adminId, int $changeId, int $auditStatus, string $remark = ''): bool
    {
        self::handleReschedule(
            $adminId,
            ServiceOrderChangeEnum::HANDLE_ROLE_ADMIN,
            $changeId,
            $auditStatus,
            $remark,
            false
        );

        return true;
    }

    public static function providerCompleteService(int $userId, int $orderId): bool
    {
        $provider = self::getProviderByUserId($userId);
        if ($provider->isEmpty()) {
            throw new \RuntimeException('当前账号未绑定服务人员');
        }

        $reviewEnabled = false;
        Db::transaction(function () use ($provider, $orderId, &$reviewEnabled) {
            $order = ServiceOrder::where([
                'id' => $orderId,
                'provider_id' => (int)$provider['id'],
            ])->whereNull('delete_time')->lock(true)->findOrEmpty();
            if ($order->isEmpty()) {
                throw new \RuntimeException('订单不存在');
            }
            if ((int)$order['order_status'] !== ServiceOrderEnum::WAIT_SERVICE) {
                throw new \RuntimeException('当前订单状态不可完成履约');
            }

            $reviewEnabled = self::isReviewEnabled();
            ServiceOrder::update([
                'id' => (int)$order['id'],
                'order_status' => $reviewEnabled ? ServiceOrderEnum::WAIT_REVIEW : ServiceOrderEnum::FINISHED,
                'update_time' => time(),
            ]);
        });

        if ($reviewEnabled) {
            self::sendWaitReviewNotice($orderId);
        }

        return true;
    }

    public static function submitReviewByUser(int $userId, int $orderId, int $score, string $content = '', array $images = []): bool
    {
        if (!self::isReviewEnabled()) {
            throw new \RuntimeException('订单评价当前未开放');
        }

        $normalizedImages = self::normalizeVoucherImages($images);
        $currentAuditRole = ServiceOrderReviewEnum::AUDIT_ROLE_ADMIN;

        Db::transaction(function () use ($userId, $orderId, $score, $content, $normalizedImages, &$currentAuditRole) {
            $order = ServiceOrder::where([
                'id' => $orderId,
                'user_id' => $userId,
            ])->whereNull('delete_time')->lock(true)->findOrEmpty();
            if ($order->isEmpty()) {
                throw new \RuntimeException('订单不存在');
            }
            if ((int)$order['order_status'] !== ServiceOrderEnum::WAIT_REVIEW) {
                throw new \RuntimeException('当前订单状态不可提交评价');
            }

            $review = ServiceOrderReview::where(['order_id' => $orderId])
                ->whereNull('delete_time')
                ->lock(true)
                ->findOrEmpty();
            if (
                !$review->isEmpty()
                && in_array((int)$review['audit_status'], [
                    ServiceOrderReviewEnum::AUDIT_PENDING,
                    ServiceOrderReviewEnum::AUDIT_APPROVED,
                ], true)
            ) {
                throw new \RuntimeException('该订单评价已提交，请勿重复操作');
            }

            $currentAuditRole = self::getInitialReviewAuditRole();
            $saveData = [
                'order_id' => (int)$order['id'],
                'user_id' => $userId,
                'provider_id' => (int)$order['provider_id'],
                'score' => $score,
                'content' => trim($content),
                'images' => json_encode($normalizedImages, JSON_UNESCAPED_UNICODE),
                'order_snapshot' => json_encode(self::buildReviewOrderSnapshot($order->toArray()), JSON_UNESCAPED_UNICODE),
                'audit_status' => ServiceOrderReviewEnum::AUDIT_PENDING,
                'audit_role' => $currentAuditRole,
                'audit_by' => 0,
                'audit_time' => 0,
                'audit_remark' => '',
                'provider_audit_status' => ServiceOrderReviewEnum::AUDIT_PENDING,
                'provider_audit_by' => 0,
                'provider_audit_time' => 0,
                'provider_audit_remark' => '',
                'admin_audit_status' => ServiceOrderReviewEnum::AUDIT_PENDING,
                'admin_audit_by' => 0,
                'admin_audit_time' => 0,
                'admin_audit_remark' => '',
                'update_time' => time(),
            ];

            if ($review->isEmpty()) {
                $saveData['create_time'] = time();
                ServiceOrderReview::create($saveData);
            } else {
                $saveData['id'] = (int)$review['id'];
                ServiceOrderReview::update($saveData);
            }

            ServiceOrder::update([
                'id' => (int)$order['id'],
                'order_status' => ServiceOrderEnum::REVIEW_PENDING_AUDIT,
                'update_time' => time(),
            ]);
        });

        self::sendReviewSubmittedNotice($orderId, $currentAuditRole);
        return true;
    }

    public static function providerAuditReview(int $userId, int $orderId, int $auditStatus, string $remark = ''): bool
    {
        $provider = self::getProviderByUserId($userId);
        if ($provider->isEmpty()) {
            throw new \RuntimeException('当前账号未绑定服务人员');
        }

        $approved = false;
        $mode = self::getReviewMode();

        Db::transaction(function () use ($provider, $orderId, $auditStatus, $remark, $mode, &$approved) {
            $order = ServiceOrder::where([
                'id' => $orderId,
                'provider_id' => (int)$provider['id'],
            ])->whereNull('delete_time')->lock(true)->findOrEmpty();
            if ($order->isEmpty()) {
                throw new \RuntimeException('订单不存在');
            }
            if ((int)$order['order_status'] !== ServiceOrderEnum::REVIEW_PENDING_AUDIT) {
                throw new \RuntimeException('当前订单状态不可审核评价');
            }

            $review = ServiceOrderReview::where([
                'order_id' => (int)$order['id'],
                'audit_status' => ServiceOrderReviewEnum::AUDIT_PENDING,
                'audit_role' => ServiceOrderReviewEnum::AUDIT_ROLE_PROVIDER,
            ])->whereNull('delete_time')->lock(true)->findOrEmpty();
            if ($review->isEmpty()) {
                throw new \RuntimeException('当前没有待服务人员审核的评价');
            }

            $approved = $auditStatus === ServiceOrderReviewEnum::AUDIT_APPROVED;
            $saveData = [
                'id' => (int)$review['id'],
                'provider_audit_status' => $auditStatus,
                'provider_audit_by' => (int)$provider['id'],
                'provider_audit_time' => time(),
                'provider_audit_remark' => trim($remark),
                'update_time' => time(),
            ];

            if ($approved && $mode === 'provider_then_admin') {
                $saveData['audit_role'] = ServiceOrderReviewEnum::AUDIT_ROLE_ADMIN;
                ServiceOrderReview::update($saveData);
                return;
            }

            $saveData['audit_status'] = $auditStatus;
            $saveData['audit_role'] = ServiceOrderReviewEnum::AUDIT_ROLE_PROVIDER;
            $saveData['audit_by'] = (int)$provider['id'];
            $saveData['audit_time'] = time();
            $saveData['audit_remark'] = trim($remark);
            ServiceOrderReview::update($saveData);

            ServiceOrder::update([
                'id' => (int)$order['id'],
                'order_status' => $approved ? ServiceOrderEnum::FINISHED : ServiceOrderEnum::WAIT_REVIEW,
                'update_time' => time(),
            ]);
        });

        if ($mode === 'provider') {
            self::sendReviewResultNotice($orderId, $approved);
        }

        return true;
    }

    public static function adminAuditReview(int $adminId, int $reviewId, int $auditStatus, string $remark = ''): bool
    {
        $approved = false;
        $orderId = 0;

        Db::transaction(function () use ($adminId, $reviewId, $auditStatus, $remark, &$approved, &$orderId) {
            $review = ServiceOrderReview::where(['id' => $reviewId])
                ->whereNull('delete_time')
                ->lock(true)
                ->findOrEmpty();
            if ($review->isEmpty()) {
                throw new \RuntimeException('评价记录不存在');
            }
            if ((int)$review['audit_status'] !== ServiceOrderReviewEnum::AUDIT_PENDING) {
                throw new \RuntimeException('当前评价不是待审核状态');
            }
            if ((string)$review['audit_role'] !== ServiceOrderReviewEnum::AUDIT_ROLE_ADMIN) {
                throw new \RuntimeException('当前评价不是待管理员审核状态');
            }

            $order = ServiceOrder::where(['id' => (int)$review['order_id']])
                ->whereNull('delete_time')
                ->lock(true)
                ->findOrEmpty();
            if ($order->isEmpty()) {
                throw new \RuntimeException('订单不存在');
            }

            $approved = $auditStatus === ServiceOrderReviewEnum::AUDIT_APPROVED;
            $orderId = (int)$order['id'];
            ServiceOrderReview::update([
                'id' => (int)$review['id'],
                'audit_status' => $auditStatus,
                'audit_role' => ServiceOrderReviewEnum::AUDIT_ROLE_ADMIN,
                'audit_by' => $adminId,
                'audit_time' => time(),
                'audit_remark' => trim($remark),
                'admin_audit_status' => $auditStatus,
                'admin_audit_by' => $adminId,
                'admin_audit_time' => time(),
                'admin_audit_remark' => trim($remark),
                'update_time' => time(),
            ]);

            ServiceOrder::update([
                'id' => (int)$order['id'],
                'order_status' => $approved ? ServiceOrderEnum::FINISHED : ServiceOrderEnum::WAIT_REVIEW,
                'update_time' => time(),
            ]);
        });

        self::sendReviewResultNotice($orderId, $approved);
        return true;
    }

    public static function getOrderById(int $orderId): ServiceOrder
    {
        return ServiceOrder::where(['id' => $orderId])->whereNull('delete_time')->findOrEmpty();
    }

    public static function getOrderBySn(string $sn): ServiceOrder
    {
        return ServiceOrder::where(['sn' => $sn])->whereNull('delete_time')->findOrEmpty();
    }

    public static function getPayOrderInfo(int $orderId): ServiceOrder
    {
        $order = self::getOrderById($orderId);
        if ($order->isEmpty()) {
            throw new \RuntimeException('订单不存在');
        }
        if ((int)$order['pay_status'] === PayEnum::ISPAID) {
            throw new \RuntimeException('订单已支付');
        }
        if ((int)$order['payment_type'] !== ServiceOrderPaymentTypeEnum::ONLINE) {
            throw new \RuntimeException('当前订单不支持在线支付');
        }
        if ((int)$order['order_status'] !== ServiceOrderEnum::WAIT_PAY) {
            throw new \RuntimeException('当前订单状态不可支付');
        }

        $tradeConfig = self::getTradeConfig();
        if ((int)($tradeConfig['online_pay_enabled'] ?? 0) !== 1) {
            throw new \RuntimeException('在线支付当前未开放');
        }

        return $order;
    }

    public static function handlePaySuccess(string $orderSn, array $extra = [], int $payWay = 0): bool
    {
        $isPaid = Db::transaction(function () use ($orderSn, $extra, $payWay) {
            $order = ServiceOrder::where(['sn' => $orderSn])->whereNull('delete_time')->lock(true)->findOrEmpty();
            if ($order->isEmpty()) {
                throw new \RuntimeException('订单不存在');
            }
            if ((int)$order['pay_status'] === PayEnum::ISPAID) {
                return true;
            }
            if ((int)$order['order_status'] !== ServiceOrderEnum::WAIT_PAY) {
                throw new \RuntimeException('当前订单状态不可更新为已支付');
            }

            ServiceOrder::update([
                'id' => (int)$order['id'],
                'order_status' => ServiceOrderEnum::WAIT_SERVICE,
                'pay_status' => PayEnum::ISPAID,
                'pay_way' => $payWay > 0 ? $payWay : (int)$order['pay_way'],
                'pay_time' => time(),
                'transaction_id' => trim((string)($extra['transaction_id'] ?? '')),
                'update_time' => time(),
            ]);

            ProviderScheduleService::occupy(
                (int)$order['provider_id'],
                (string)$order['service_date'],
                WeddingOrderScheduleService::SOURCE_PENDING_ORDER,
                (int)$order['id'],
                '支付成功占档'
            );

            return true;
        });

        if ($isPaid) {
            self::sendOrderPaySuccessNoticesBySn($orderSn);
        }

        return (bool)$isPaid;
    }

    public static function adminAuditOfflineVoucher(int $adminId, int $orderId, int $auditStatus, string $auditRemark = ''): bool
    {
        if ($auditStatus !== ServiceOrderOfflineVoucherEnum::APPROVED && $auditStatus !== ServiceOrderOfflineVoucherEnum::REJECTED) {
            throw new \RuntimeException('审核状态不正确');
        }

        Db::transaction(function () use ($orderId, $auditStatus, $auditRemark, $adminId) {
            $order = ServiceOrder::where(['id' => $orderId])->whereNull('delete_time')->lock(true)->findOrEmpty();
            if ($order->isEmpty()) {
                throw new \RuntimeException('订单不存在');
            }
            if ((int)$order['order_status'] !== ServiceOrderEnum::WAIT_OFFLINE_VOUCHER_AUDIT) {
                throw new \RuntimeException('当前订单状态不可审核线下凭证');
            }

            $voucher = ServiceOrderOfflineVoucher::where([
                'order_id' => $orderId,
                'audit_status' => ServiceOrderOfflineVoucherEnum::PENDING,
            ])->whereNull('delete_time')->lock(true)->findOrEmpty();
            if ($voucher->isEmpty()) {
                throw new \RuntimeException('线下凭证不存在或已审核');
            }

            ServiceOrderOfflineVoucher::update([
                'id' => (int)$voucher['id'],
                'audit_status' => $auditStatus,
                'audit_by' => $adminId,
                'audit_time' => time(),
                'audit_remark' => trim($auditRemark),
                'update_time' => time(),
            ]);

            if ($auditStatus === ServiceOrderOfflineVoucherEnum::APPROVED) {
                ServiceOrder::update([
                    'id' => (int)$order['id'],
                    'order_status' => ServiceOrderEnum::WAIT_SERVICE,
                    'pay_status' => PayEnum::ISPAID,
                    'pay_time' => time(),
                    'cancel_source' => '',
                    'cancel_reason' => '',
                    'update_time' => time(),
                ]);

                ProviderScheduleService::occupy(
                    (int)$order['provider_id'],
                    (string)$order['service_date'],
                    WeddingOrderScheduleService::SOURCE_PENDING_ORDER,
                    (int)$order['id'],
                    '线下凭证审核通过占档'
                );
                return;
            }

            ServiceOrder::update([
                'id' => (int)$order['id'],
                'order_status' => ServiceOrderEnum::CANCELED,
                'cancel_source' => ServiceOrderEnum::CANCEL_SOURCE_ADMIN,
                'cancel_reason' => trim($auditRemark),
                'update_time' => time(),
            ]);

            WeddingOrderScheduleService::releasePendingOrder(
                (int)$order['provider_id'],
                (string)$order['service_date']
            );
            self::closePendingRescheduleRequests((int)$order['id'], '线下凭证审核驳回，改期申请已自动关闭');
        });

        if ($auditStatus === ServiceOrderOfflineVoucherEnum::APPROVED) {
            self::sendOfflineVoucherApprovedNotice($orderId);
        } else {
            self::sendOfflineVoucherRejectedNotice($orderId);
        }

        return true;
    }

    public static function closeTimedOutOrderBySystem(int $orderId, int $expectedStatus, int $now, string $reason): array
    {
        return Db::transaction(function () use ($orderId, $expectedStatus, $now, $reason) {
            $order = ServiceOrder::where(['id' => $orderId])->whereNull('delete_time')->lock(true)->findOrEmpty();
            if ($order->isEmpty()) {
                return [
                    'order_id' => $orderId,
                    'result' => 'skipped',
                    'message' => '订单不存在',
                ];
            }

            $orderData = $order->toArray();
            if (!self::shouldAutoCloseTimedOutOrder($orderData, $expectedStatus, $now)) {
                return [
                    'order_id' => $orderId,
                    'result' => 'skipped',
                    'message' => '订单状态或超时条件已变化',
                ];
            }

            self::closeOrderBySystem($order, $reason);

            return [
                'order_id' => $orderId,
                'result' => 'closed',
                'message' => '订单已自动关闭',
            ];
        });
    }

    public static function buildOrderDetail(array $orderData, bool $isUserView = true): array
    {
        self::appendOrderDesc($orderData);
        $snapshot = ServiceOrderSnapshot::where(['order_id' => (int)$orderData['id']])
            ->whereNull('delete_time')
            ->findOrEmpty();
        $voucher = ServiceOrderOfflineVoucher::where(['order_id' => (int)$orderData['id']])
            ->whereNull('delete_time')
            ->findOrEmpty();
        $latestChange = self::getLatestChangeByOrderId((int)$orderData['id']);
        $review = self::getReviewByOrderId((int)$orderData['id']);
        $tradeConfig = self::getTradeConfig();

        $snapshotData = $snapshot->isEmpty() ? [] : $snapshot->toArray();
        $voucherData = $voucher->isEmpty() ? [] : $voucher->toArray();
        $changeData = $latestChange->isEmpty() ? [] : $latestChange->append(['status_desc', 'handle_role_desc'])->toArray();
        $reviewData = $review->isEmpty() ? [] : $review->append(['audit_status_desc', 'audit_role_desc'])->toArray();

        if (!empty($voucherData['voucher_images']) && is_array($voucherData['voucher_images'])) {
            $voucherData['voucher_images'] = array_values(array_map(function ($item) {
                return FileService::getFileUrl((string)$item);
            }, $voucherData['voucher_images']));
        }
        if (!empty($reviewData['images']) && is_array($reviewData['images'])) {
            $reviewData['images'] = array_values(array_map(function ($item) {
                return FileService::getFileUrl((string)$item);
            }, $reviewData['images']));
        }

        $action = [
            'can_cancel' => in_array((int)$orderData['order_status'], [
                ServiceOrderEnum::WAIT_PROVIDER_CONFIRM,
                ServiceOrderEnum::WAIT_PAY,
                ServiceOrderEnum::WAIT_OFFLINE_VOUCHER_AUDIT,
            ], true),
            'can_pay_online' => (int)$orderData['payment_type'] === ServiceOrderPaymentTypeEnum::ONLINE
                && (int)$orderData['order_status'] === ServiceOrderEnum::WAIT_PAY
                && (int)($tradeConfig['online_pay_enabled'] ?? 0) === 1,
            'can_upload_voucher' => (int)$orderData['payment_type'] === ServiceOrderPaymentTypeEnum::OFFLINE_VOUCHER
                && (int)$orderData['order_status'] === ServiceOrderEnum::WAIT_PAY
                && (int)($tradeConfig['offline_voucher_enabled'] ?? 0) === 1,
            'can_apply_reschedule' => self::canUserApplyReschedule($orderData, $changeData),
            'can_review' => $isUserView
                && self::isReviewEnabled()
                && (int)$orderData['order_status'] === ServiceOrderEnum::WAIT_REVIEW,
            'can_handle_reschedule' => !$isUserView
                && !empty($changeData)
                && (int)($changeData['status'] ?? -1) === ServiceOrderChangeEnum::PENDING,
            'can_complete_service' => !$isUserView
                && (int)$orderData['order_status'] === ServiceOrderEnum::WAIT_SERVICE,
            'can_audit_review' => !$isUserView
                && !empty($reviewData)
                && (int)($reviewData['audit_status'] ?? -1) === ServiceOrderReviewEnum::AUDIT_PENDING
                && (string)($reviewData['audit_role'] ?? '') === ServiceOrderReviewEnum::AUDIT_ROLE_PROVIDER,
        ];

        if (!$isUserView) {
            $action['can_cancel'] = false;
        }

        return [
            'order' => $orderData,
            'snapshot' => $snapshotData,
            'offline_voucher' => $voucherData,
            'latest_change' => $changeData,
            'review' => $reviewData,
            'action' => $action,
            'pay_from' => self::PAY_FROM,
        ];
    }

    public static function appendOrderDesc(array &$orderData): void
    {
        $orderData['order_status_desc'] = ServiceOrderEnum::getStatusDesc((int)($orderData['order_status'] ?? 0));
        $orderData['payment_type_desc'] = ServiceOrderPaymentTypeEnum::getDesc((int)($orderData['payment_type'] ?? 0));
        $orderData['pay_status_desc'] = PayEnum::getPayStatusDesc((int)($orderData['pay_status'] ?? 0));
    }

    public static function getTradeConfig(): array
    {
        $config = ServiceBusinessConfigService::getConfig();
        return $config['trade'] ?? [];
    }

    public static function isReviewEnabled(): bool
    {
        $config = ServiceBusinessConfigService::getConfig();
        return (int)($config['interaction']['order_review_enabled'] ?? 0) === 1;
    }

    public static function getReviewMode(): string
    {
        $config = ServiceBusinessConfigService::getConfig();
        $mode = trim((string)($config['review']['order_review_mode'] ?? 'admin'));
        return in_array($mode, ['admin', 'provider', 'provider_then_admin'], true) ? $mode : 'admin';
    }

    private static function shouldAutoCloseTimedOutOrder(array $orderData, int $expectedStatus, int $now): bool
    {
        if ((int)($orderData['order_status'] ?? 0) !== $expectedStatus) {
            return false;
        }

        if ($expectedStatus === ServiceOrderEnum::WAIT_PROVIDER_CONFIRM) {
            return (int)($orderData['provider_confirm_expire_time'] ?? 0) > 0
                && (int)($orderData['provider_confirm_expire_time'] ?? 0) <= $now;
        }

        if ($expectedStatus === ServiceOrderEnum::WAIT_PAY) {
            return (int)($orderData['pay_status'] ?? PayEnum::UNPAID) === PayEnum::UNPAID
                && (int)($orderData['pay_expire_time'] ?? 0) > 0
                && (int)($orderData['pay_expire_time'] ?? 0) <= $now;
        }

        return false;
    }

    private static function closeOrderBySystem(ServiceOrder $order, string $reason): void
    {
        $schedule = ProviderSchedule::where([
            'provider_id' => (int)$order['provider_id'],
            'service_date' => (string)$order['service_date'],
        ])->whereNull('delete_time')->lock(true)->findOrEmpty();

        if ($schedule->isEmpty() || (string)$schedule['status'] !== ProviderScheduleService::STATUS_LOCKED) {
            throw new \RuntimeException('订单锁档状态异常，无法执行系统关闭');
        }

        ServiceOrder::update([
            'id' => (int)$order['id'],
            'order_status' => ServiceOrderEnum::CANCELED,
            'cancel_source' => ServiceOrderEnum::CANCEL_SOURCE_SYSTEM,
            'cancel_reason' => trim($reason),
            'update_time' => time(),
        ]);

        ProviderSchedule::destroy((int)$schedule['id']);
        self::closePendingRescheduleRequests((int)$order['id'], '订单超时关闭，改期申请已自动关闭');
    }

    private static function canUserApplyReschedule(array $orderData, array $changeData): bool
    {
        if (!in_array((int)($orderData['order_status'] ?? 0), [
            ServiceOrderEnum::WAIT_PAY,
            ServiceOrderEnum::WAIT_OFFLINE_VOUCHER_AUDIT,
            ServiceOrderEnum::WAIT_SERVICE,
        ], true)) {
            return false;
        }

        return (int)($changeData['status'] ?? -1) !== ServiceOrderChangeEnum::PENDING;
    }

    private static function getLatestChangeByOrderId(int $orderId): ServiceOrderChange
    {
        return ServiceOrderChange::where(['order_id' => $orderId])
            ->whereNull('delete_time')
            ->order(['id' => 'desc'])
            ->findOrEmpty();
    }

    private static function getReviewByOrderId(int $orderId): ServiceOrderReview
    {
        return ServiceOrderReview::where(['order_id' => $orderId])
            ->whereNull('delete_time')
            ->findOrEmpty();
    }

    private static function getInitialReviewAuditRole(): string
    {
        return self::getReviewMode() === 'admin'
            ? ServiceOrderReviewEnum::AUDIT_ROLE_ADMIN
            : ServiceOrderReviewEnum::AUDIT_ROLE_PROVIDER;
    }

    private static function buildReviewOrderSnapshot(array $orderData): array
    {
        self::appendOrderDesc($orderData);
        return [
            'order_id' => (int)$orderData['id'],
            'order_sn' => (string)$orderData['sn'],
            'provider_name' => (string)($orderData['provider_name'] ?? ''),
            'package_name' => (string)($orderData['package_name'] ?? ''),
            'service_date' => (string)($orderData['service_date'] ?? ''),
            'order_amount' => round((float)($orderData['order_amount'] ?? 0), 2),
            'order_status_desc' => (string)($orderData['order_status_desc'] ?? ''),
        ];
    }

    private static function closePendingRescheduleRequests(int $orderId, string $remark): void
    {
        ServiceOrderChange::where([
            'order_id' => $orderId,
            'status' => ServiceOrderChangeEnum::PENDING,
        ])->whereNull('delete_time')->update([
            'status' => ServiceOrderChangeEnum::REJECTED,
            'handle_role' => ServiceOrderChangeEnum::HANDLE_ROLE_ADMIN,
            'handle_by' => 0,
            'handle_time' => time(),
            'handle_remark' => trim($remark),
            'update_time' => time(),
        ]);
    }

    private static function handleReschedule(
        int $actorId,
        string $handleRole,
        int $changeId,
        int $auditStatus,
        string $remark = '',
        bool $enforceProviderOwnership = false
    ): void {
        if (!in_array($auditStatus, [ServiceOrderChangeEnum::APPROVED, ServiceOrderChangeEnum::REJECTED], true)) {
            throw new \RuntimeException('改期处理结果不正确');
        }

        $approved = false;
        $orderId = 0;
        Db::transaction(function () use (
            $actorId,
            $handleRole,
            $changeId,
            $auditStatus,
            $remark,
            $enforceProviderOwnership,
            &$approved,
            &$orderId
        ) {
            $change = ServiceOrderChange::where(['id' => $changeId])
                ->whereNull('delete_time')
                ->lock(true)
                ->findOrEmpty();
            if ($change->isEmpty()) {
                throw new \RuntimeException('改期申请不存在');
            }
            if ((int)$change['status'] !== ServiceOrderChangeEnum::PENDING) {
                throw new \RuntimeException('当前改期申请已处理');
            }

            if ($enforceProviderOwnership && (int)$change['provider_id'] !== $actorId) {
                throw new \RuntimeException('无权处理该改期申请');
            }

            $order = ServiceOrder::where(['id' => (int)$change['order_id']])
                ->whereNull('delete_time')
                ->lock(true)
                ->findOrEmpty();
            if ($order->isEmpty()) {
                throw new \RuntimeException('订单不存在');
            }
            if ($enforceProviderOwnership && (int)$order['provider_id'] !== $actorId) {
                throw new \RuntimeException('无权处理该订单改期申请');
            }
            if (!in_array((int)$order['order_status'], [
                ServiceOrderEnum::WAIT_PAY,
                ServiceOrderEnum::WAIT_OFFLINE_VOUCHER_AUDIT,
                ServiceOrderEnum::WAIT_SERVICE,
            ], true)) {
                throw new \RuntimeException('当前订单状态不可处理改期');
            }

            $approved = $auditStatus === ServiceOrderChangeEnum::APPROVED;
            $orderId = (int)$order['id'];
            if ($approved) {
                self::moveOrderScheduleForReschedule($order->toArray(), (string)$change['new_service_date']);
                ServiceOrder::update([
                    'id' => (int)$order['id'],
                    'service_date' => (string)$change['new_service_date'],
                    'update_time' => time(),
                ]);
                ServiceOrderSnapshot::where(['order_id' => (int)$order['id']])
                    ->whereNull('delete_time')
                    ->update([
                        'service_date' => (string)$change['new_service_date'],
                        'update_time' => time(),
                    ]);
            }

            ServiceOrderChange::update([
                'id' => (int)$change['id'],
                'status' => $auditStatus,
                'handle_role' => $handleRole,
                'handle_by' => $actorId,
                'handle_time' => time(),
                'handle_remark' => trim($remark),
                'update_time' => time(),
            ]);
        });

        self::sendRescheduleResultNotice($orderId, $approved);
    }

    private static function moveOrderScheduleForReschedule(array $orderData, string $newServiceDate): void
    {
        $providerId = (int)$orderData['provider_id'];
        $oldServiceDate = (string)$orderData['service_date'];
        $orderId = (int)$orderData['id'];

        if (in_array((int)$orderData['order_status'], [
            ServiceOrderEnum::WAIT_PAY,
            ServiceOrderEnum::WAIT_OFFLINE_VOUCHER_AUDIT,
        ], true)) {
            WeddingOrderScheduleService::movePendingOrder(
                $providerId,
                $oldServiceDate,
                $newServiceDate,
                $orderId,
                '改期通过迁移锁定档期'
            );
            return;
        }

        if ((int)$orderData['order_status'] === ServiceOrderEnum::WAIT_SERVICE) {
            WeddingOrderScheduleService::moveOccupiedOrder(
                $providerId,
                $oldServiceDate,
                $newServiceDate,
                $orderId,
                '改期通过迁移已占用档期'
            );
            return;
        }

        throw new \RuntimeException('当前订单状态不支持改期');
    }

    private static function sendOrderCreatedNotices(int $orderId): void
    {
        $order = self::getOrderById($orderId);
        if ($order->isEmpty()) {
            return;
        }
        $params = self::buildOrderNoticeParams($order->toArray());
        ServiceOrderNoticeService::sendByScene(
            ServiceNoticeSceneEnum::ORDER_CREATED,
            (int)$order['user_id'],
            $params,
            self::buildUserOrderNoticeExtra((int)$order['id'])
        );

        $providerUserId = self::getProviderBindUserId((int)$order['provider_id']);
        if ($providerUserId > 0) {
            ServiceOrderNoticeService::sendByScene(
                ServiceNoticeSceneEnum::ORDER_CREATED,
                $providerUserId,
                $params,
                self::buildProviderOrderNoticeExtra((int)$order['id'])
            );
        }
    }

    private static function sendOrderWaitPayNotice(int $orderId): void
    {
        $order = self::getOrderById($orderId);
        if ($order->isEmpty()) {
            return;
        }
        ServiceOrderNoticeService::sendByScene(
            ServiceNoticeSceneEnum::ORDER_WAIT_PAY,
            (int)$order['user_id'],
            self::buildOrderNoticeParams($order->toArray()),
            self::buildUserOrderNoticeExtra((int)$order['id'])
        );
    }

    private static function sendOrderProviderAcceptedNotice(int $orderId): void
    {
        $order = self::getOrderById($orderId);
        if ($order->isEmpty()) {
            return;
        }
        ServiceOrderNoticeService::sendByScene(
            ServiceNoticeSceneEnum::ORDER_PROVIDER_ACCEPTED,
            (int)$order['user_id'],
            self::buildOrderNoticeParams($order->toArray()),
            self::buildUserOrderNoticeExtra((int)$order['id'])
        );
    }

    private static function sendOrderRejectedNotice(int $orderId): void
    {
        $order = self::getOrderById($orderId);
        if ($order->isEmpty()) {
            return;
        }
        ServiceOrderNoticeService::sendByScene(
            ServiceNoticeSceneEnum::ORDER_PROVIDER_REJECTED,
            (int)$order['user_id'],
            self::buildOrderNoticeParams($order->toArray()),
            self::buildUserOrderNoticeExtra((int)$order['id'])
        );
    }

    private static function sendOrderPaySuccessNoticesBySn(string $orderSn): void
    {
        $order = self::getOrderBySn($orderSn);
        if ($order->isEmpty()) {
            return;
        }
        $params = self::buildOrderNoticeParams($order->toArray());
        ServiceOrderNoticeService::sendByScene(
            ServiceNoticeSceneEnum::ORDER_PAY_SUCCESS,
            (int)$order['user_id'],
            $params,
            self::buildUserOrderNoticeExtra((int)$order['id'])
        );
        $providerUserId = self::getProviderBindUserId((int)$order['provider_id']);
        if ($providerUserId > 0) {
            ServiceOrderNoticeService::sendByScene(
                ServiceNoticeSceneEnum::ORDER_PAY_SUCCESS,
                $providerUserId,
                $params,
                self::buildProviderOrderNoticeExtra((int)$order['id'])
            );
        }
    }

    private static function sendOfflineVoucherSubmittedNotice(int $orderId): void
    {
        $order = self::getOrderById($orderId);
        if ($order->isEmpty()) {
            return;
        }
        $providerUserId = self::getProviderBindUserId((int)$order['provider_id']);
        if ($providerUserId <= 0) {
            return;
        }
        ServiceOrderNoticeService::sendByScene(
            ServiceNoticeSceneEnum::ORDER_OFFLINE_VOUCHER_SUBMITTED,
            $providerUserId,
            self::buildOrderNoticeParams($order->toArray()),
            self::buildProviderOrderNoticeExtra((int)$order['id'])
        );
    }

    private static function sendOfflineVoucherApprovedNotice(int $orderId): void
    {
        $order = self::getOrderById($orderId);
        if ($order->isEmpty()) {
            return;
        }
        ServiceOrderNoticeService::sendByScene(
            ServiceNoticeSceneEnum::ORDER_OFFLINE_VOUCHER_APPROVED,
            (int)$order['user_id'],
            self::buildOrderNoticeParams($order->toArray()),
            self::buildUserOrderNoticeExtra((int)$order['id'])
        );
    }

    private static function sendOfflineVoucherRejectedNotice(int $orderId): void
    {
        $order = self::getOrderById($orderId);
        if ($order->isEmpty()) {
            return;
        }
        ServiceOrderNoticeService::sendByScene(
            ServiceNoticeSceneEnum::ORDER_OFFLINE_VOUCHER_REJECTED,
            (int)$order['user_id'],
            self::buildOrderNoticeParams($order->toArray()),
            self::buildUserOrderNoticeExtra((int)$order['id'])
        );
    }

    private static function sendRescheduleAppliedNotice(int $orderId): void
    {
        $order = self::getOrderById($orderId);
        if ($order->isEmpty()) {
            return;
        }
        $providerUserId = self::getProviderBindUserId((int)$order['provider_id']);
        if ($providerUserId <= 0) {
            return;
        }
        ServiceOrderNoticeService::sendByScene(
            ServiceNoticeSceneEnum::ORDER_RESCHEDULE_APPLIED,
            $providerUserId,
            self::buildOrderNoticeParams($order->toArray()),
            self::buildProviderOrderNoticeExtra((int)$order['id'])
        );
    }

    private static function sendRescheduleResultNotice(int $orderId, bool $approved): void
    {
        $order = self::getOrderById($orderId);
        if ($order->isEmpty()) {
            return;
        }
        ServiceOrderNoticeService::sendByScene(
            ServiceNoticeSceneEnum::ORDER_RESCHEDULE_RESULT,
            (int)$order['user_id'],
            array_merge(self::buildOrderNoticeParams($order->toArray()), [
                'reschedule_result' => $approved ? '已通过' : '已驳回',
            ]),
            self::buildUserOrderNoticeExtra((int)$order['id'])
        );
    }

    private static function sendWaitReviewNotice(int $orderId): void
    {
        $order = self::getOrderById($orderId);
        if ($order->isEmpty()) {
            return;
        }
        ServiceOrderNoticeService::sendByScene(
            ServiceNoticeSceneEnum::ORDER_WAIT_REVIEW,
            (int)$order['user_id'],
            self::buildOrderNoticeParams($order->toArray()),
            self::buildUserOrderNoticeExtra((int)$order['id'])
        );
    }

    private static function sendReviewSubmittedNotice(int $orderId, string $auditRole): void
    {
        if ($auditRole !== ServiceOrderReviewEnum::AUDIT_ROLE_PROVIDER) {
            return;
        }

        $order = self::getOrderById($orderId);
        if ($order->isEmpty()) {
            return;
        }
        $providerUserId = self::getProviderBindUserId((int)$order['provider_id']);
        if ($providerUserId <= 0) {
            return;
        }
        ServiceOrderNoticeService::sendByScene(
            ServiceNoticeSceneEnum::ORDER_REVIEW_SUBMITTED,
            $providerUserId,
            self::buildOrderNoticeParams($order->toArray()),
            self::buildProviderOrderNoticeExtra((int)$order['id'])
        );
    }

    private static function sendReviewResultNotice(int $orderId, bool $approved): void
    {
        $order = self::getOrderById($orderId);
        if ($order->isEmpty()) {
            return;
        }
        ServiceOrderNoticeService::sendByScene(
            ServiceNoticeSceneEnum::ORDER_REVIEW_RESULT,
            (int)$order['user_id'],
            array_merge(self::buildOrderNoticeParams($order->toArray()), [
                'review_result' => $approved ? '已通过' : '已驳回',
            ]),
            self::buildUserOrderNoticeExtra((int)$order['id'])
        );
    }

    private static function buildOrderNoticeParams(array $orderData): array
    {
        self::appendOrderDesc($orderData);
        return [
            'order_sn' => (string)($orderData['sn'] ?? ''),
            'provider_name' => (string)($orderData['provider_name'] ?? ''),
            'package_name' => (string)($orderData['package_name'] ?? ''),
            'service_date' => (string)($orderData['service_date'] ?? ''),
            'order_amount' => number_format((float)($orderData['order_amount'] ?? 0), 2, '.', ''),
            'order_status_desc' => (string)($orderData['order_status_desc'] ?? ''),
        ];
    }

    private static function buildUserOrderNoticeExtra(int $orderId): array
    {
        return [
            'order_id' => $orderId,
            'path' => '/pages/wedding_order_detail/wedding_order_detail?order_id=' . $orderId,
        ];
    }

    private static function buildProviderOrderNoticeExtra(int $orderId): array
    {
        return [
            'order_id' => $orderId,
            'path' => '/pages/provider_order_detail/provider_order_detail?order_id=' . $orderId,
        ];
    }

    private static function getProviderBindUserId(int $providerId): int
    {
        return (int)ServiceProvider::where(['id' => $providerId])
            ->whereNull('delete_time')
            ->value('user_id');
    }

    private static function buildCreateOrderData(
        int $userId,
        int $terminal,
        int $paymentType,
        array $preview,
        int $providerConfirmExpireTime
    ): array {
        return [
            'sn' => generate_sn(ServiceOrder::class, 'sn'),
            'user_id' => $userId,
            'provider_id' => (int)($preview['provider']['provider_id'] ?? 0),
            'package_id' => (int)($preview['package']['package_id'] ?? 0),
            'category_id' => (int)($preview['provider']['category_id'] ?? 0),
            'provider_name' => (string)($preview['provider']['name'] ?? ''),
            'package_name' => (string)($preview['package']['name'] ?? ''),
            'service_date' => (string)($preview['service_date'] ?? ''),
            'province_code' => (string)($preview['region']['province_code'] ?? ''),
            'province_name' => (string)($preview['region']['province_name'] ?? ''),
            'city_code' => (string)($preview['region']['city_code'] ?? ''),
            'city_name' => (string)($preview['region']['city_name'] ?? ''),
            'district_code' => (string)($preview['region']['district_code'] ?? ''),
            'district_name' => (string)($preview['region']['district_name'] ?? ''),
            'price_match_level' => (string)($preview['pricing']['price_match_level'] ?? ''),
            'matched_region_code' => (string)($preview['pricing']['matched_region_code'] ?? ''),
            'matched_region_name' => (string)($preview['pricing']['matched_region_name'] ?? ''),
            'order_amount' => round((float)($preview['pricing']['price'] ?? 0), 2),
            'payment_type' => $paymentType,
            'order_status' => ServiceOrderEnum::WAIT_PROVIDER_CONFIRM,
            'pay_status' => PayEnum::UNPAID,
            'order_terminal' => $terminal,
            'provider_confirm_expire_time' => $providerConfirmExpireTime,
            'create_time' => time(),
            'update_time' => time(),
        ];
    }

    private static function buildCreateSnapshotData(array $preview): array
    {
        return [
            'service_date' => (string)($preview['service_date'] ?? ''),
            'province_code' => (string)($preview['region']['province_code'] ?? ''),
            'province_name' => (string)($preview['region']['province_name'] ?? ''),
            'city_code' => (string)($preview['region']['city_code'] ?? ''),
            'city_name' => (string)($preview['region']['city_name'] ?? ''),
            'district_code' => (string)($preview['region']['district_code'] ?? ''),
            'district_name' => (string)($preview['region']['district_name'] ?? ''),
            'price' => round((float)($preview['pricing']['price'] ?? 0), 2),
            'price_match_level' => (string)($preview['pricing']['price_match_level'] ?? ''),
            'matched_region_code' => (string)($preview['pricing']['matched_region_code'] ?? ''),
            'matched_region_name' => (string)($preview['pricing']['matched_region_name'] ?? ''),
            'provider_snapshot' => json_encode($preview['provider'] ?? [], JSON_UNESCAPED_UNICODE),
            'package_snapshot' => json_encode($preview['package'] ?? [], JSON_UNESCAPED_UNICODE),
            'profile_snapshot' => json_encode($preview['profile_summary'] ?? [], JSON_UNESCAPED_UNICODE),
            'template_snapshot' => json_encode($preview['template_summary'] ?? [], JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
            'update_time' => time(),
        ];
    }

    private static function assertPaymentTypeEnabled(int $paymentType, array $tradeConfig): void
    {
        if ($paymentType === ServiceOrderPaymentTypeEnum::ONLINE && (int)($tradeConfig['online_pay_enabled'] ?? 0) !== 1) {
            throw new \RuntimeException('在线支付当前未开放');
        }
        if ($paymentType === ServiceOrderPaymentTypeEnum::OFFLINE_VOUCHER && (int)($tradeConfig['offline_voucher_enabled'] ?? 0) !== 1) {
            throw new \RuntimeException('线下凭证支付当前未开放');
        }
        if ($paymentType !== ServiceOrderPaymentTypeEnum::ONLINE && $paymentType !== ServiceOrderPaymentTypeEnum::OFFLINE_VOUCHER) {
            throw new \RuntimeException('支付类型不正确');
        }
    }

    private static function normalizeVoucherImages(array $images): array
    {
        return array_values(array_filter(array_map(function ($item) {
            $path = trim((string)$item);
            if ($path === '') {
                return '';
            }
            return FileService::setFileUrl($path);
        }, $images)));
    }
}
