<?php

declare(strict_types=1);

namespace app\adminapi\logic\wedding;

use app\common\logic\BaseLogic;
use app\common\model\wedding\ProviderPackage;
use app\common\model\wedding\ProviderPackageAreaPrice;
use app\common\model\wedding\ServiceOpenCity;
use app\common\model\wedding\ServiceProvider;
use app\common\service\ProviderPackageDataService;
use app\common\service\ServiceRegionService;
use think\facade\Db;

class ProviderPackageLogic extends BaseLogic
{
    public static function add(array $params): void
    {
        self::save($params);
    }

    public static function edit(array $params): bool
    {
        try {
            self::save($params, (int)$params['id']);
            return true;
        } catch (\Throwable $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    public static function delete(array $params): void
    {
        $id = (int)$params['id'];
        ProviderPackage::destroy($id);
        ProviderPackageAreaPrice::where(['package_id' => $id])->delete();
    }

    public static function detail(array $params): array
    {
        $package = ProviderPackage::findOrEmpty((int)$params['id']);
        if ($package->isEmpty()) {
            return [];
        }

        $data = $package->toArray();
        $data['area_prices'] = self::getAreaPrices((int)$package['id']);
        return $data;
    }

    public static function updateStatus(array $params): bool
    {
        ProviderPackage::update([
            'id' => (int)$params['id'],
            'status' => (int)$params['status'],
            'update_time' => time(),
        ]);
        return true;
    }

    public static function getProviderOptions(): array
    {
        return ServiceProvider::alias('provider')
            ->leftJoin('service_category category', 'category.id = provider.category_id')
            ->whereNull('provider.delete_time')
            ->field([
                'provider.id',
                'provider.name',
                'provider.category_id',
                'category.name' => 'category_name',
            ])
            ->order(['provider.sort' => 'desc', 'provider.id' => 'desc'])
            ->select()
            ->toArray();
    }

    public static function getOpenRegionOptions(): array
    {
        $openCityCodes = ServiceOpenCity::where(['status' => 1])
            ->whereNull('delete_time')
            ->column('city_code');

        return ServiceRegionService::getOpenRegionCascaderOptions($openCityCodes);
    }

    private static function save(array $params, int $id = 0): void
    {
        $areaPrices = ProviderPackageDataService::normalizeAreaPrices($params['area_prices'] ?? []);
        Db::transaction(function () use ($params, $areaPrices, $id) {
            $saveData = [
                'provider_id' => (int)$params['provider_id'],
                'name' => trim((string)$params['name']),
                'summary' => trim((string)($params['summary'] ?? '')),
                'service_duration' => trim((string)($params['service_duration'] ?? '')),
                'status' => (int)$params['status'],
                'sort' => (int)($params['sort'] ?? 0),
                'update_time' => time(),
            ];

            if ($id > 0) {
                $saveData['id'] = $id;
                ProviderPackage::update($saveData);
                ProviderPackageAreaPrice::where(['package_id' => $id])->delete();
            } else {
                $saveData['create_time'] = time();
                $package = ProviderPackage::create($saveData);
                $id = (int)$package->id;
            }

            foreach ($areaPrices as $item) {
                ProviderPackageAreaPrice::create(array_merge($item, [
                    'package_id' => $id,
                    'create_time' => time(),
                    'update_time' => time(),
                ]));
            }
        });
    }

    private static function normalizeAreaPrices(array $areaPrices): array
    {
        return ProviderPackageDataService::normalizeAreaPrices($areaPrices);
    }

    private static function getAreaPrices(int $packageId): array
    {
        return ProviderPackageDataService::getAreaPricesByPackageId($packageId);
    }
}
