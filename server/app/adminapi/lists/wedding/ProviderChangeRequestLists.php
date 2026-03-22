<?php

declare(strict_types=1);

namespace app\adminapi\lists\wedding;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wedding\ProviderChangeRequest;

class ProviderChangeRequestLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return ['change_type', 'audit_status'];
    }

    public function lists(): array
    {
        return $this->buildQuery()
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['request.id' => 'desc'])
            ->select()
            ->toArray();
    }

    public function count(): int
    {
        return $this->buildQuery()->count();
    }

    private function buildQuery()
    {
        $query = ProviderChangeRequest::alias('request')
            ->leftJoin('service_provider provider', 'provider.id = request.provider_id')
            ->leftJoin('service_category category', 'category.id = provider.category_id')
            ->whereNull('request.delete_time')
            ->field([
                'request.id',
                'request.provider_id',
                'request.change_type',
                'request.change_title',
                'request.audit_status',
                'request.audit_role',
                'request.audit_time',
                'request.create_time',
                'provider.name' => 'provider_name',
                'category.name' => 'category_name',
            ]);

        if (($this->params['keyword'] ?? '') !== '') {
            $keyword = trim((string)$this->params['keyword']);
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('provider.name', '%' . $keyword . '%')
                    ->whereOrLike('request.change_title', '%' . $keyword . '%')
                    ->whereOrLike('category.name', '%' . $keyword . '%');
            });
        }

        if (($this->params['change_type'] ?? '') !== '') {
            $query->where('request.change_type', trim((string)$this->params['change_type']));
        }

        if (($this->params['audit_status'] ?? '') !== '') {
            $query->where('request.audit_status', (int)$this->params['audit_status']);
        }

        return $query;
    }
}
