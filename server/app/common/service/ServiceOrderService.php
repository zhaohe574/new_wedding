<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\PayEnum;
use app\common\enum\wedding\ServiceOrderEnum;
use app\common\enum\wedding\ServiceOrderOfflineVoucherEnum;
use app\common\enum\wedding\ServiceOrderPaymentTypeEnum;
use app\common\model\wedding\ServiceOrder;
use app\common\model\wedding\ServiceOrderOfflineVoucher;
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
        $order = ServiceOrder::where([
            'id' => $orderId,
            'user_id' => $userId,
        ])->whereNull('delete_time')->findOrEmpty();
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

        Db::transaction(function () use ($order, $reason) {
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
        });

        return true;
    }

    public static function submitOfflineVoucher(int $userId, int $orderId, array $images, string $remark = ''): bool
    {
        $tradeConfig = self::getTradeConfig();
        if ((int)($tradeConfig['offline_voucher_enabled'] ?? 0) !== 1) {
            throw new \RuntimeException('线下凭证支付当前未开放');
        }

        $order = ServiceOrder::where([
            'id' => $orderId,
            'user_id' => $userId,
        ])->whereNull('delete_time')->findOrEmpty();
        if ($order->isEmpty()) {
            throw new \RuntimeException('订单不存在');
        }

        if ((int)$order['order_status'] !== ServiceOrderEnum::WAIT_PAY) {
            throw new \RuntimeException('当前订单状态不可提交线下凭证');
        }
        if ((int)$order['payment_type'] !== ServiceOrderPaymentTypeEnum::OFFLINE_VOUCHER) {
            throw new \RuntimeException('当前订单不是线下凭证支付类型');
        }
        $existsVoucher = ServiceOrderOfflineVoucher::where(['order_id' => $orderId])->whereNull('delete_time')->count();
        if ($existsVoucher > 0) {
            throw new \RuntimeException('该订单已提交过线下凭证');
        }

        $normalizedImages = self::normalizeVoucherImages($images);
        if (empty($normalizedImages)) {
            throw new \RuntimeException('请至少上传一张凭证图片');
        }

        Db::transaction(function () use ($order, $normalizedImages, $remark, $userId) {
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
        $provider = self::getProviderByUserId($userId);
        if ($provider->isEmpty()) {
            throw new \RuntimeException('当前账号未绑定服务人员');
        }

        $pageNo = max(1, (int)($params['page_no'] ?? 1));
        $pageSize = min(50, max(1, (int)($params['page_size'] ?? 10)));
        $offset = ($pageNo - 1) * $pageSize;

        $query = ServiceOrder::alias('order')
            ->leftJoin('user user', 'user.id = order.user_id')
            ->where('order.provider_id', (int)$provider['id'])
            ->where('order.order_status', ServiceOrderEnum::WAIT_PROVIDER_CONFIRM)
            ->whereNull('order.delete_time')
            ->field([
                'order.id',
                'order.sn',
                'order.user_id',
                'order.package_name',
                'order.service_date',
                'order.order_amount',
                'order.province_name',
                'order.city_name',
                'order.district_name',
                'order.provider_confirm_expire_time',
                'order.create_time',
                'user.nickname' => 'user_nickname',
                'user.mobile' => 'user_mobile',
            ]);

        $count = (clone $query)->count();
        $lists = $query->order(['order.id' => 'desc'])->limit($offset, $pageSize)->select()->toArray();
        foreach ($lists as &$item) {
            $item['order_status'] = ServiceOrderEnum::WAIT_PROVIDER_CONFIRM;
            $item['order_status_desc'] = ServiceOrderEnum::getStatusDesc(ServiceOrderEnum::WAIT_PROVIDER_CONFIRM);
            $item['payment_type'] = 0;
            $item['payment_type_desc'] = '-';
            $item['pay_status'] = 0;
            $item['pay_status_desc'] = PayEnum::getPayStatusDesc(PayEnum::UNPAID);
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

        $order = ServiceOrder::where([
            'id' => $orderId,
            'provider_id' => (int)$provider['id'],
        ])->whereNull('delete_time')->findOrEmpty();
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

        return true;
    }

    public static function providerRejectOrder(int $userId, int $orderId, string $reason): bool
    {
        $provider = self::getProviderByUserId($userId);
        if ($provider->isEmpty()) {
            throw new \RuntimeException('当前账号未绑定服务人员');
        }

        $order = ServiceOrder::where([
            'id' => $orderId,
            'provider_id' => (int)$provider['id'],
        ])->whereNull('delete_time')->findOrEmpty();
        if ($order->isEmpty()) {
            throw new \RuntimeException('订单不存在');
        }
        if ((int)$order['order_status'] !== ServiceOrderEnum::WAIT_PROVIDER_CONFIRM) {
            throw new \RuntimeException('当前订单状态不可拒单');
        }

        Db::transaction(function () use ($order, $reason) {
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
        });

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
        $order = self::getOrderBySn($orderSn);
        if ($order->isEmpty()) {
            throw new \RuntimeException('订单不存在');
        }
        if ((int)$order['pay_status'] === PayEnum::ISPAID) {
            return true;
        }
        if ((int)$order['order_status'] !== ServiceOrderEnum::WAIT_PAY) {
            throw new \RuntimeException('当前订单状态不可更新为已支付');
        }

        Db::transaction(function () use ($order, $extra, $payWay) {
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
        });

        return true;
    }

    public static function adminAuditOfflineVoucher(int $adminId, int $orderId, int $auditStatus, string $auditRemark = ''): bool
    {
        $order = self::getOrderById($orderId);
        if ($order->isEmpty()) {
            throw new \RuntimeException('订单不存在');
        }
        if ((int)$order['order_status'] !== ServiceOrderEnum::WAIT_OFFLINE_VOUCHER_AUDIT) {
            throw new \RuntimeException('当前订单状态不可审核线下凭证');
        }

        $voucher = ServiceOrderOfflineVoucher::where([
            'order_id' => $orderId,
            'audit_status' => ServiceOrderOfflineVoucherEnum::PENDING,
        ])->whereNull('delete_time')->findOrEmpty();
        if ($voucher->isEmpty()) {
            throw new \RuntimeException('线下凭证不存在或已审核');
        }

        if ($auditStatus !== ServiceOrderOfflineVoucherEnum::APPROVED && $auditStatus !== ServiceOrderOfflineVoucherEnum::REJECTED) {
            throw new \RuntimeException('审核状态不正确');
        }

        Db::transaction(function () use ($order, $voucher, $auditStatus, $auditRemark, $adminId) {
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
        });

        return true;
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
        $tradeConfig = self::getTradeConfig();

        $snapshotData = $snapshot->isEmpty() ? [] : $snapshot->toArray();
        $voucherData = $voucher->isEmpty() ? [] : $voucher->toArray();

        if (!empty($voucherData['voucher_images']) && is_array($voucherData['voucher_images'])) {
            $voucherData['voucher_images'] = array_values(array_map(function ($item) {
                return FileService::getFileUrl((string)$item);
            }, $voucherData['voucher_images']));
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
        ];

        if (!$isUserView) {
            $action['can_cancel'] = false;
        }

        return [
            'order' => $orderData,
            'snapshot' => $snapshotData,
            'offline_voucher' => $voucherData,
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
