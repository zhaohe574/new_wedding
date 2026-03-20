<?php

declare(strict_types=1);

namespace app\adminapi\logic\wedding;

use app\common\logic\BaseLogic;
use app\common\model\wedding\ServiceCategory;

class ServiceCategoryLogic extends BaseLogic
{
    public static function add(array $params): void
    {
        ServiceCategory::create([
            'name' => trim((string)$params['name']),
            'sort' => (int)($params['sort'] ?? 0),
            'status' => (int)$params['status'],
            'create_time' => time(),
            'update_time' => time(),
        ]);
    }

    public static function edit(array $params): bool
    {
        try {
            ServiceCategory::update([
                'id' => (int)$params['id'],
                'name' => trim((string)$params['name']),
                'sort' => (int)($params['sort'] ?? 0),
                'status' => (int)$params['status'],
                'update_time' => time(),
            ]);
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    public static function delete(array $params): void
    {
        ServiceCategory::destroy((int)$params['id']);
    }

    public static function detail(array $params): array
    {
        return ServiceCategory::findOrEmpty((int)$params['id'])->toArray();
    }

    public static function updateStatus(array $params): bool
    {
        ServiceCategory::update([
            'id' => (int)$params['id'],
            'status' => (int)$params['status'],
            'update_time' => time(),
        ]);
        return true;
    }

    public static function getAllData(): array
    {
        return ServiceCategory::where(['status' => 1])
            ->whereNull('delete_time')
            ->field('id,name,sort,status')
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->select()
            ->toArray();
    }
}
