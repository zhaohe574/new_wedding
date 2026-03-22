<?php

declare(strict_types=1);

namespace app\adminapi\lists\wedding;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\notice\NoticeMnpLog;

class NoticeMnpLogLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return ['send_status', 'scene_id'];
    }

    public function lists(): array
    {
        return $this->buildQuery()
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['log.id' => 'desc'])
            ->select()
            ->toArray();
    }

    public function count(): int
    {
        return $this->buildQuery()->count();
    }

    private function buildQuery()
    {
        $query = NoticeMnpLog::alias('log')
            ->leftJoin('user user', 'user.id = log.user_id')
            ->field([
                'log.*',
                'user.nickname' => 'user_nickname',
                'user.mobile' => 'user_mobile',
            ]);

        if (($this->params['keyword'] ?? '') !== '') {
            $keyword = trim((string)$this->params['keyword']);
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('log.scene_name', '%' . $keyword . '%')
                    ->whereOrLike('log.template_id', '%' . $keyword . '%')
                    ->whereOrLike('user.nickname', '%' . $keyword . '%')
                    ->whereOrLike('user.mobile', '%' . $keyword . '%');
            });
        }

        if (($this->params['send_status'] ?? '') !== '') {
            $query->where('log.send_status', (int)$this->params['send_status']);
        }

        if (($this->params['scene_id'] ?? '') !== '') {
            $query->where('log.scene_id', (int)$this->params['scene_id']);
        }

        return $query;
    }
}
