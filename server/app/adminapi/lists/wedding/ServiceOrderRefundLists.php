<?php

declare(strict_types=1);

namespace app\adminapi\lists\wedding;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\PayEnum;
use app\common\enum\wedding\ServiceOrderEnum;
use app\common\enum\wedding\ServiceOrderPaymentTypeEnum;
use app\common\enum\wedding\ServiceOrderRefundEnum;
use app\common\lists\ListsSearchInterface;
use app\common\model\wedding\ServiceOrderRefund;

class ServiceOrderRefundLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return ['status', 'apply_source'];
    }

    public function lists(): array
    {
        $lists = $this->buildQuery()
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['refund.id' => 'desc'])
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['status_desc'] = ServiceOrderRefundEnum::getStatusDesc((int)$item['status']);
            $item['apply_source_desc'] = ServiceOrderRefundEnum::getApplySourceDesc((string)($item['apply_source'] ?? ''));
            $item['order_status_desc'] = ServiceOrderEnum::getStatusDesc((int)($item['order_status'] ?? 0));
            $item['payment_type_desc'] = ServiceOrderPaymentTypeEnum::getDesc((int)($item['payment_type'] ?? 0));
            $item['pay_status_desc'] = PayEnum::getPayStatusDesc((int)($item['pay_status'] ?? 0));
        }

        return $lists;
    }

    public function count(): int
    {
        return $this->buildQuery()->count();
    }

    private function buildQuery()
    {
        $query = ServiceOrderRefund::alias('refund')
            ->leftJoin('service_order order', 'order.id = refund.order_id')
            ->leftJoin('user user', 'user.id = refund.user_id')
            ->leftJoin('service_provider provider', 'provider.id = refund.provider_id')
            ->leftJoin('refund_record refund_record', 'refund_record.id = refund.refund_record_id')
            ->whereNull('refund.delete_time')
            ->field([
                'refund.id',
                'refund.order_id',
                'refund.apply_source',
                'refund.origin_order_status',
                'refund.refund_amount',
                'refund.apply_reason',
                'refund.status',
                'refund.handle_by',
                'refund.handle_remark',
                'refund.handle_time',
                'refund.refund_record_id',
                'refund.create_time',
                'order.sn',
                'order.order_status',
                'order.payment_type',
                'order.pay_status',
                'order.package_name',
                'order.service_date',
                'user.nickname' => 'user_nickname',
                'user.mobile' => 'user_mobile',
                'provider.name' => 'provider_name',
                'refund_record.sn' => 'refund_record_sn',
                'refund_record.refund_status' => 'refund_record_status',
            ]);

        if (($this->params['keyword'] ?? '') !== '') {
            $keyword = trim((string)$this->params['keyword']);
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('order.sn', '%' . $keyword . '%')
                    ->whereOrLike('refund_record.sn', '%' . $keyword . '%')
                    ->whereOrLike('provider.name', '%' . $keyword . '%')
                    ->whereOrLike('user.nickname', '%' . $keyword . '%')
                    ->whereOrLike('user.mobile', '%' . $keyword . '%');
            });
        }
        if (($this->params['status'] ?? '') !== '') {
            $query->where('refund.status', (int)$this->params['status']);
        }
        if (($this->params['apply_source'] ?? '') !== '') {
            $query->where('refund.apply_source', trim((string)$this->params['apply_source']));
        }

        return $query;
    }
}
