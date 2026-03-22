<?php

declare(strict_types=1);

namespace app\adminapi\lists\wedding;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\wedding\ServiceOrderChangeEnum;
use app\common\lists\ListsSearchInterface;
use app\common\model\wedding\ServiceOrderChange;

class ServiceOrderChangeLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return ['status'];
    }

    public function lists(): array
    {
        $lists = $this->buildQuery()
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['change_record.id' => 'desc'])
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['status_desc'] = ServiceOrderChangeEnum::getStatusDesc((int)$item['status']);
            $item['handle_role_desc'] = ServiceOrderChangeEnum::getHandleRoleDesc((string)($item['handle_role'] ?? ''));
        }

        return $lists;
    }

    public function count(): int
    {
        return $this->buildQuery()->count();
    }

    private function buildQuery()
    {
        $query = ServiceOrderChange::alias('change_record')
            ->leftJoin('service_order order', 'order.id = change_record.order_id')
            ->leftJoin('user user', 'user.id = change_record.user_id')
            ->leftJoin('service_provider provider', 'provider.id = change_record.provider_id')
            ->whereNull('change_record.delete_time')
            ->field([
                'change_record.id',
                'change_record.order_id',
                'change_record.old_service_date',
                'change_record.new_service_date',
                'change_record.apply_reason',
                'change_record.status',
                'change_record.handle_role',
                'change_record.handle_by',
                'change_record.handle_time',
                'change_record.handle_remark',
                'change_record.create_time',
                'order.sn',
                'order.order_status',
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
        if (($this->params['status'] ?? '') !== '') {
            $query->where('change_record.status', (int)$this->params['status']);
        }

        return $query;
    }
}
