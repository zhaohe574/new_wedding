<?php

declare(strict_types=1);

namespace app\adminapi\lists\wedding;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\PayEnum;
use app\common\enum\wedding\ServiceOrderEnum;
use app\common\enum\wedding\ServiceOrderOfflineVoucherEnum;
use app\common\enum\wedding\ServiceOrderPaymentTypeEnum;
use app\common\lists\ListsSearchInterface;
use app\common\model\wedding\ServiceOrder;

class ServiceOrderLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return ['order_status', 'provider_id', 'user_id'];
    }

    public function lists(): array
    {
        $query = $this->buildQuery();
        $lists = $query->limit($this->limitOffset, $this->limitLength)->order(['order.id' => 'desc'])->select()->toArray();
        foreach ($lists as &$item) {
            $item['order_status_desc'] = ServiceOrderEnum::getStatusDesc((int)$item['order_status']);
            $item['payment_type_desc'] = ServiceOrderPaymentTypeEnum::getDesc((int)$item['payment_type']);
            $item['pay_status_desc'] = PayEnum::getPayStatusDesc((int)$item['pay_status']);
            $item['voucher_audit_status_desc'] = ServiceOrderOfflineVoucherEnum::getDesc((int)($item['voucher_audit_status'] ?? 0));
        }
        return $lists;
    }

    public function count(): int
    {
        return $this->buildQuery()->count();
    }

    private function buildQuery()
    {
        $query = ServiceOrder::alias('order')
            ->leftJoin('service_provider provider', 'provider.id = order.provider_id')
            ->leftJoin('user user', 'user.id = order.user_id')
            ->leftJoin('service_order_offline_voucher voucher', 'voucher.order_id = order.id AND voucher.delete_time IS NULL')
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
                'order.pay_time',
                'order.create_time',
                'order.cancel_source',
                'order.cancel_reason',
                'user.nickname' => 'user_nickname',
                'user.mobile' => 'user_mobile',
                'voucher.audit_status' => 'voucher_audit_status',
                'voucher.audit_time' => 'voucher_audit_time',
            ]);

        if (($this->params['keyword'] ?? '') !== '') {
            $keyword = trim((string)$this->params['keyword']);
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('order.sn', '%' . $keyword . '%')
                    ->whereOrLike('order.provider_name', '%' . $keyword . '%')
                    ->whereOrLike('user.nickname', '%' . $keyword . '%')
                    ->whereOrLike('user.mobile', '%' . $keyword . '%');
            });
        }

        if (($this->params['order_status'] ?? '') !== '') {
            $query->where('order.order_status', (int)$this->params['order_status']);
        }
        if (($this->params['provider_id'] ?? '') !== '') {
            $query->where('order.provider_id', (int)$this->params['provider_id']);
        }
        if (($this->params['user_id'] ?? '') !== '') {
            $query->where('order.user_id', (int)$this->params['user_id']);
        }
        if (($this->params['service_date_start'] ?? '') !== '') {
            $query->where('order.service_date', '>=', (string)$this->params['service_date_start']);
        }
        if (($this->params['service_date_end'] ?? '') !== '') {
            $query->where('order.service_date', '<=', (string)$this->params['service_date_end']);
        }
        if ((int)($this->params['voucher_only'] ?? 0) === 1) {
            $query->where('order.order_status', ServiceOrderEnum::WAIT_OFFLINE_VOUCHER_AUDIT);
        }

        return $query;
    }
}

