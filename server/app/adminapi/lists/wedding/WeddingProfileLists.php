<?php

declare(strict_types=1);

namespace app\adminapi\lists\wedding;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wedding\WeddingProfile;
use app\common\service\WeddingProfileService;

class WeddingProfileLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [];
    }

    public function lists(): array
    {
        $query = WeddingProfile::alias('profile')
            ->leftJoin('user user', 'user.id = profile.user_id')
            ->whereNull('profile.delete_time')
            ->field([
                'profile.id',
                'profile.user_id',
                'profile.wedding_date',
                'profile.city_name',
                'profile.district_name',
                'profile.contact_name',
                'profile.contact_mobile',
                'profile.create_time',
                'user.nickname' => 'user_nickname',
                'user.account' => 'user_account',
                'user.mobile' => 'user_mobile',
            ]);

        if (($this->params['keyword'] ?? '') !== '') {
            $keyword = trim((string)$this->params['keyword']);
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('user.nickname', '%' . $keyword . '%')
                    ->whereOrLike('user.account', '%' . $keyword . '%')
                    ->whereOrLike('profile.city_name', '%' . $keyword . '%')
                    ->whereOrLike('profile.district_name', '%' . $keyword . '%')
                    ->whereOrLike('profile.contact_name', '%' . $keyword . '%')
                    ->whereOrLike('profile.contact_mobile', '%' . $keyword . '%');
            });
        }

        if (($this->params['city_name'] ?? '') !== '') {
            $query->whereLike('profile.city_name', '%' . trim((string)$this->params['city_name']) . '%');
        }

        if (($this->params['wedding_date'] ?? '') !== '') {
            $query->where('profile.wedding_date', (string)$this->params['wedding_date']);
        }

        $lists = $query
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['profile.id' => 'desc'])
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item = array_merge($item, WeddingProfileService::formatProfile($item));
        }

        return $lists;
    }

    public function count(): int
    {
        $query = WeddingProfile::alias('profile')
            ->leftJoin('user user', 'user.id = profile.user_id')
            ->whereNull('profile.delete_time');

        if (($this->params['keyword'] ?? '') !== '') {
            $keyword = trim((string)$this->params['keyword']);
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('user.nickname', '%' . $keyword . '%')
                    ->whereOrLike('user.account', '%' . $keyword . '%')
                    ->whereOrLike('profile.city_name', '%' . $keyword . '%')
                    ->whereOrLike('profile.district_name', '%' . $keyword . '%')
                    ->whereOrLike('profile.contact_name', '%' . $keyword . '%')
                    ->whereOrLike('profile.contact_mobile', '%' . $keyword . '%');
            });
        }

        if (($this->params['city_name'] ?? '') !== '') {
            $query->whereLike('profile.city_name', '%' . trim((string)$this->params['city_name']) . '%');
        }

        if (($this->params['wedding_date'] ?? '') !== '') {
            $query->where('profile.wedding_date', (string)$this->params['wedding_date']);
        }

        return $query->count();
    }
}
