<?php

declare(strict_types=1);

namespace app\adminapi\lists\wedding;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\wedding\ServiceOrderReviewEnum;
use app\common\lists\ListsSearchInterface;
use app\common\model\wedding\ServiceOrderReview;

class ServiceOrderReviewLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return ['audit_status'];
    }

    public function lists(): array
    {
        $lists = $this->buildQuery()
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['review.id' => 'desc'])
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['audit_status_desc'] = ServiceOrderReviewEnum::getAuditStatusDesc((int)$item['audit_status']);
            $item['audit_role_desc'] = ServiceOrderReviewEnum::getAuditRoleDesc((string)($item['audit_role'] ?? ''));
        }

        return $lists;
    }

    public function count(): int
    {
        return $this->buildQuery()->count();
    }

    private function buildQuery()
    {
        $query = ServiceOrderReview::alias('review')
            ->leftJoin('service_order order', 'order.id = review.order_id')
            ->leftJoin('user user', 'user.id = review.user_id')
            ->leftJoin('service_provider provider', 'provider.id = review.provider_id')
            ->whereNull('review.delete_time')
            ->field([
                'review.id',
                'review.order_id',
                'review.score',
                'review.content',
                'review.audit_status',
                'review.audit_role',
                'review.audit_time',
                'review.create_time',
                'order.sn',
                'order.package_name',
                'user.nickname' => 'user_nickname',
                'user.mobile' => 'user_mobile',
                'provider.name' => 'provider_name',
            ]);

        if (($this->params['keyword'] ?? '') !== '') {
            $keyword = trim((string)$this->params['keyword']);
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('order.sn', '%' . $keyword . '%')
                    ->whereOrLike('provider.name', '%' . $keyword . '%')
                    ->whereOrLike('user.nickname', '%' . $keyword . '%')
                    ->whereOrLike('user.mobile', '%' . $keyword . '%');
            });
        }
        if (($this->params['audit_status'] ?? '') !== '') {
            $query->where('review.audit_status', (int)$this->params['audit_status']);
        }

        return $query;
    }
}
