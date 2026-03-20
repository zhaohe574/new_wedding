<?php

declare(strict_types=1);

namespace app\adminapi\lists\wedding;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wedding\ServiceTag;

class ServiceTagLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return ['name', 'status'];
    }

    public function lists(): array
    {
        return ServiceTag::where($this->searchWhere)
            ->whereNull('delete_time')
            ->append(['status_desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->select()
            ->toArray();
    }

    public function count(): int
    {
        return ServiceTag::where($this->searchWhere)->whereNull('delete_time')->count();
    }
}
