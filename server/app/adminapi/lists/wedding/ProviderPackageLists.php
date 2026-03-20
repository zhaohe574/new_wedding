<?php

declare(strict_types=1);

namespace app\adminapi\lists\wedding;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wedding\ProviderPackage;
use app\common\model\wedding\ProviderPackageAreaPrice;

class ProviderPackageLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return ['status', 'provider_id', 'category_id'];
    }

    public function lists(): array
    {
        $query = ProviderPackage::alias('package')
            ->leftJoin('service_provider provider', 'provider.id = package.provider_id')
            ->leftJoin('service_category category', 'category.id = provider.category_id')
            ->whereNull('package.delete_time')
            ->field([
                'package.id',
                'package.provider_id',
                'package.name',
                'package.summary',
                'package.service_duration',
                'package.status',
                'package.sort',
                'package.create_time',
                'provider.name' => 'provider_name',
                'provider.category_id',
                'category.name' => 'category_name',
            ]);

        if ($this->params['keyword'] ?? '') {
            $keyword = trim((string)$this->params['keyword']);
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('package.name', '%' . $keyword . '%')
                    ->whereOrLike('provider.name', '%' . $keyword . '%')
                    ->whereOrLike('category.name', '%' . $keyword . '%');
            });
        }

        if (($this->params['status'] ?? '') !== '') {
            $query->where('package.status', (int)$this->params['status']);
        }

        if (($this->params['provider_id'] ?? '') !== '') {
            $query->where('package.provider_id', (int)$this->params['provider_id']);
        }

        if (($this->params['category_id'] ?? '') !== '') {
            $query->where('provider.category_id', (int)$this->params['category_id']);
        }

        $lists = $query
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['package.sort' => 'desc', 'package.id' => 'desc'])
            ->select()
            ->toArray();

        $packageIds = array_column($lists, 'id');
        $priceCounts = [];
        if (!empty($packageIds)) {
            $priceCounts = ProviderPackageAreaPrice::whereIn('package_id', $packageIds)
                ->whereNull('delete_time')
                ->group('package_id')
                ->column('COUNT(*)', 'package_id');
        }

        foreach ($lists as &$item) {
            $item['status_desc'] = (int)$item['status'] === 1 ? '启用' : '停用';
            $item['price_count'] = (int)($priceCounts[$item['id']] ?? 0);
        }

        return $lists;
    }

    public function count(): int
    {
        $query = ProviderPackage::alias('package')
            ->leftJoin('service_provider provider', 'provider.id = package.provider_id')
            ->leftJoin('service_category category', 'category.id = provider.category_id')
            ->whereNull('package.delete_time');

        if ($this->params['keyword'] ?? '') {
            $keyword = trim((string)$this->params['keyword']);
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('package.name', '%' . $keyword . '%')
                    ->whereOrLike('provider.name', '%' . $keyword . '%')
                    ->whereOrLike('category.name', '%' . $keyword . '%');
            });
        }

        if (($this->params['status'] ?? '') !== '') {
            $query->where('package.status', (int)$this->params['status']);
        }

        if (($this->params['provider_id'] ?? '') !== '') {
            $query->where('package.provider_id', (int)$this->params['provider_id']);
        }

        if (($this->params['category_id'] ?? '') !== '') {
            $query->where('provider.category_id', (int)$this->params['category_id']);
        }

        return $query->count();
    }
}
