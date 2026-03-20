<?php

declare(strict_types=1);

namespace app\adminapi\logic\wedding;

use app\common\logic\BaseLogic;
use app\common\model\user\User;
use app\common\model\wedding\ServiceCategory;
use app\common\model\wedding\ServiceProvider;
use app\common\model\wedding\ServiceTag;
use app\common\service\FileService;

class ServiceProviderLogic extends BaseLogic
{
    public static function add(array $params): void
    {
        ServiceProvider::create(self::buildSaveData($params, true));
    }

    public static function edit(array $params): bool
    {
        try {
            $data = self::buildSaveData($params, false);
            $data['id'] = (int)$params['id'];
            ServiceProvider::update($data);
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    public static function delete(array $params): void
    {
        ServiceProvider::destroy((int)$params['id']);
    }

    public static function detail(array $params): array
    {
        return ServiceProvider::findOrEmpty((int)$params['id'])->toArray();
    }

    public static function updateStatus(array $params): bool
    {
        ServiceProvider::update([
            'id' => (int)$params['id'],
            'status' => (int)$params['status'],
            'update_time' => time(),
        ]);
        return true;
    }

    public static function getUserOptions(string $keyword = ''): array
    {
        $query = User::field('id,nickname,account,mobile')
            ->whereNull('delete_time')
            ->order('id', 'desc')
            ->limit(20);

        if ($keyword !== '') {
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('nickname', '%' . $keyword . '%')
                    ->whereOrLike('account', '%' . $keyword . '%')
                    ->whereOrLike('mobile', '%' . $keyword . '%')
                    ->whereOrLike('sn', '%' . $keyword . '%');
            });
        }

        return $query->select()->toArray();
    }

    public static function getCategoryOptions(): array
    {
        return ServiceCategory::where(['status' => 1])
            ->whereNull('delete_time')
            ->field('id,name')
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->select()
            ->toArray();
    }

    public static function getTagOptions(): array
    {
        return ServiceTag::where(['status' => 1])
            ->whereNull('delete_time')
            ->field('id,name')
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->select()
            ->toArray();
    }

    private static function buildSaveData(array $params, bool $withCreateTime): array
    {
        $data = [
            'user_id' => (int)$params['user_id'],
            'category_id' => (int)$params['category_id'],
            'name' => trim((string)$params['name']),
            'avatar' => FileService::setFileUrl((string)($params['avatar'] ?? '')),
            'mobile' => trim((string)($params['mobile'] ?? '')),
            'tag_ids' => array_values(array_unique(array_filter(array_map('intval', $params['tag_ids'] ?? [])))),
            'intro' => trim((string)($params['intro'] ?? '')),
            'status' => (int)$params['status'],
            'is_recommend' => (int)$params['is_recommend'],
            'sort' => (int)($params['sort'] ?? 0),
            'update_time' => time(),
        ];

        if ($withCreateTime) {
            $data['create_time'] = time();
        }

        return $data;
    }
}
