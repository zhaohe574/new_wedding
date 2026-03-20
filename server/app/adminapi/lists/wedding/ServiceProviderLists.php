<?php

declare(strict_types=1);

namespace app\adminapi\lists\wedding;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wedding\ServiceProvider;
use app\common\model\wedding\ServiceTag;

class ServiceProviderLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return ['status', 'category_id'];
    }

    public function lists(): array
    {
        $query = ServiceProvider::alias('provider')
            ->leftJoin('user user', 'user.id = provider.user_id')
            ->leftJoin('service_category category', 'category.id = provider.category_id')
            ->whereNull('provider.delete_time')
            ->field([
                'provider.id',
                'provider.user_id',
                'provider.category_id',
                'provider.name',
                'provider.avatar',
                'provider.mobile',
                'provider.tag_ids',
                'provider.status',
                'provider.is_recommend',
                'provider.sort',
                'provider.create_time',
                'user.nickname' => 'user_nickname',
                'user.account' => 'user_account',
                'category.name' => 'category_name',
            ]);

        if ($this->params['keyword'] ?? '') {
            $keyword = trim((string)$this->params['keyword']);
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('provider.name', '%' . $keyword . '%')
                    ->whereOrLike('provider.mobile', '%' . $keyword . '%')
                    ->whereOrLike('user.nickname', '%' . $keyword . '%')
                    ->whereOrLike('user.account', '%' . $keyword . '%');
            });
        }

        if (($this->params['status'] ?? '') !== '') {
            $query->where('provider.status', (int)$this->params['status']);
        }

        if (($this->params['category_id'] ?? '') !== '') {
            $query->where('provider.category_id', (int)$this->params['category_id']);
        }

        $lists = $query
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['provider.sort' => 'desc', 'provider.id' => 'desc'])
            ->select()
            ->toArray();

        $tagMap = ServiceTag::whereNull('delete_time')->column('name', 'id');
        foreach ($lists as &$item) {
            $tagIds = empty($item['tag_ids']) ? [] : array_values(array_filter(array_map('intval', explode(',', (string)$item['tag_ids']))));
            $item['tag_names'] = array_values(array_filter(array_map(function ($tagId) use ($tagMap) {
                return $tagMap[$tagId] ?? '';
            }, $tagIds)));
            $item['status_desc'] = (int)$item['status'] === 1 ? '启用' : '停用';
            $item['is_recommend_desc'] = (int)$item['is_recommend'] === 1 ? '推荐' : '普通';
        }

        return $lists;
    }

    public function count(): int
    {
        $query = ServiceProvider::alias('provider')
            ->leftJoin('user user', 'user.id = provider.user_id')
            ->whereNull('provider.delete_time');

        if ($this->params['keyword'] ?? '') {
            $keyword = trim((string)$this->params['keyword']);
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('provider.name', '%' . $keyword . '%')
                    ->whereOrLike('provider.mobile', '%' . $keyword . '%')
                    ->whereOrLike('user.nickname', '%' . $keyword . '%')
                    ->whereOrLike('user.account', '%' . $keyword . '%');
            });
        }

        if (($this->params['status'] ?? '') !== '') {
            $query->where('provider.status', (int)$this->params['status']);
        }

        if (($this->params['category_id'] ?? '') !== '') {
            $query->where('provider.category_id', (int)$this->params['category_id']);
        }

        return $query->count();
    }
}
