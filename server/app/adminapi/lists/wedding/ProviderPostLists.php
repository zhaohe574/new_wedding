<?php

declare(strict_types=1);

namespace app\adminapi\lists\wedding;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\service\ProviderPostService;

class ProviderPostLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return ['audit_status'];
    }

    public function lists(): array
    {
        return ProviderPostService::getAdminPostLists(array_merge($this->params, [
            'page_no' => $this->pageNo,
            'page_size' => $this->limitLength,
        ]))['lists'] ?? [];
    }

    public function count(): int
    {
        return (int)(ProviderPostService::getAdminPostLists(array_merge($this->params, [
            'page_no' => 1,
            'page_size' => 1,
        ]))['count'] ?? 0);
    }
}
