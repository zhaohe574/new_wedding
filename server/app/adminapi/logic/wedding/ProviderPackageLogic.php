<?php

declare(strict_types=1);

namespace app\adminapi\logic\wedding;

use app\common\logic\BaseLogic;
use app\common\model\wedding\ProviderPackage;
use app\common\model\wedding\ProviderPackageAreaPrice;
use app\common\model\wedding\ServiceOpenCity;
use app\common\model\wedding\ServiceProvider;
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
        $areaPrices = self::normalizeAreaPrices($params['area_prices'] ?? []);
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
        if (empty($areaPrices)) {
            throw new \RuntimeException('请至少配置一条地区价格');
        }

        $normalized = [];
        $uniqueKeys = [];

        foreach ($areaPrices as $item) {
            $regionCodes = array_values(array_filter(array_map('strval', $item['region_codes'] ?? [])));
            $regionDetail = ServiceRegionService::getRegionDetailByPath($regionCodes);
            if (empty($regionDetail)) {
                throw new \RuntimeException('存在无效的地区价格配置');
            }

            $isOpen = match ($regionDetail['region_level']) {
                'province' => ServiceOpenCity::where([
                    'province_code' => $regionDetail['province_code'],
                    'status' => 1,
                ])->whereNull('delete_time')->count() > 0,
                default => ServiceOpenCity::where([
                    'city_code' => $regionDetail['city_code'],
                    'status' => 1,
                ])->whereNull('delete_time')->count() > 0,
            };

            if (!$isOpen) {
                throw new \RuntimeException('地区价格只能配置在已开放城市及其下属县区范围内');
            }

            $uniqueKey = $regionDetail['region_level'] . ':' . $regionDetail['region_code'];
            if (isset($uniqueKeys[$uniqueKey])) {
                throw new \RuntimeException('同一套餐同一层级同一地区不可重复配置');
            }

            $uniqueKeys[$uniqueKey] = true;
            $price = round((float)($item['price'] ?? 0), 2);
            if ($price <= 0) {
                throw new \RuntimeException('地区价格必须大于 0');
            }

            $normalized[] = [
                'region_level' => $regionDetail['region_level'],
                'region_code' => $regionDetail['region_code'],
                'region_name' => $regionDetail['region_name'],
                'province_code' => $regionDetail['province_code'],
                'city_code' => $regionDetail['city_code'],
                'district_code' => $regionDetail['district_code'],
                'price' => $price,
                'status' => (int)!empty($item['status']),
                'sort' => (int)($item['sort'] ?? 0),
            ];
        }

        return $normalized;
    }

    private static function getAreaPrices(int $packageId): array
    {
        $areaPrices = ProviderPackageAreaPrice::where(['package_id' => $packageId])
            ->whereNull('delete_time')
            ->order(['sort' => 'desc', 'id' => 'asc'])
            ->select()
            ->toArray();

        foreach ($areaPrices as &$item) {
            $regionCodes = [(string)$item['province_code']];
            if ($item['region_level'] === 'city') {
                $regionCodes[] = (string)$item['city_code'];
            }
            if ($item['region_level'] === 'district') {
                $regionCodes[] = (string)$item['city_code'];
                $regionCodes[] = (string)$item['district_code'];
            }
            $item['region_codes'] = array_values(array_filter($regionCodes));
        }

        return $areaPrices;
    }
}
