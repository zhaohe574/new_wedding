<?php

declare(strict_types=1);

namespace app\adminapi\lists\wedding;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wedding\ServiceOpenCity;

class ServiceOpenCityLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return ['status'];
    }

    public function lists(): array
    {
        $query = ServiceOpenCity::whereNull('delete_time');

        if ($this->params['keyword'] ?? '') {
            $keyword = trim((string)$this->params['keyword']);
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('province_name', '%' . $keyword . '%')
                    ->whereOrLike('city_name', '%' . $keyword . '%')
                    ->whereOrLike('city_code', '%' . $keyword . '%');
            });
        }

        if (($this->params['status'] ?? '') !== '') {
            $query->where('status', (int)$this->params['status']);
        }

        return $query
            ->append(['status_desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->select()
            ->toArray();
    }

    public function count(): int
    {
        $query = ServiceOpenCity::whereNull('delete_time');

        if ($this->params['keyword'] ?? '') {
            $keyword = trim((string)$this->params['keyword']);
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('province_name', '%' . $keyword . '%')
                    ->whereOrLike('city_name', '%' . $keyword . '%')
                    ->whereOrLike('city_code', '%' . $keyword . '%');
            });
        }

        if (($this->params['status'] ?? '') !== '') {
            $query->where('status', (int)$this->params['status']);
        }

        return $query->count();
    }
}
