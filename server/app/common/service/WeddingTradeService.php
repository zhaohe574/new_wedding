<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\model\wedding\ProviderPackage;
use app\common\model\wedding\ServiceProvider;
use app\common\model\wedding\ServiceTag;

class WeddingTradeService
{
    public static function getProviderLists(int $categoryId, string $districtCode, string $serviceDate): array
    {
        $providers = self::getActiveProviderQuery()
            ->where('provider.category_id', $categoryId)
            ->order(['provider.is_recommend' => 'desc', 'provider.sort' => 'desc', 'provider.id' => 'desc'])
            ->select()
            ->toArray();

        $lists = [];
        foreach ($providers as $provider) {
            $saleContext = self::buildProviderSaleContext($provider, $districtCode, $serviceDate);
            if (!$saleContext['is_sellable']) {
                continue;
            }

            $bestPackage = $saleContext['best_package'];
            $lists[] = [
                'provider_id' => (int)$provider['id'],
                'category_id' => (int)$provider['category_id'],
                'name' => (string)$provider['name'],
                'avatar' => self::formatAvatar((string)($provider['avatar'] ?? '')),
                'summary' => (string)($provider['intro'] ?? ''),
                'tag_ids' => $provider['tag_ids'] ?? [],
                'recommend' => (int)($provider['is_recommend'] ?? 0),
                'sort' => (int)($provider['sort'] ?? 0),
                'min_price' => round((float)($bestPackage['price'] ?? 0), 2),
                'matched_package_count' => count($saleContext['packages']),
                'price_match_level' => (string)($bestPackage['price_match_level'] ?? ''),
            ];
        }

        return $lists;
    }

    public static function getProviderDetail(int $providerId, string $districtCode, string $serviceDate): array
    {
        $provider = self::getActiveProviderQuery()
            ->where('provider.id', $providerId)
            ->findOrEmpty();

        if ($provider->isEmpty()) {
            throw new \RuntimeException('服务人员不存在或已停用');
        }

        $providerData = $provider->toArray();
        $saleContext = self::buildProviderSaleContext($providerData, $districtCode, $serviceDate);

        return [
            'provider' => self::formatProviderSummary($providerData),
            'category' => [
                'id' => (int)$providerData['category_id'],
                'name' => (string)($providerData['category_name'] ?? ''),
            ],
            'tags' => self::getTagsByIds($providerData['tag_ids'] ?? []),
            'schedule' => [
                'status' => $saleContext['schedule_status'],
                'status_desc' => ProviderScheduleService::getStatusMap()[$saleContext['schedule_status']] ?? '-',
            ],
            'service_date' => $serviceDate,
            'region' => $saleContext['region'],
            'packages' => $saleContext['packages'],
        ];
    }

    public static function buildOrderPreview(
        int $userId,
        int $providerId,
        int $packageId,
        string $districtCode,
        string $serviceDate,
        array $templateFormData
    ): array {
        $provider = self::getActiveProviderQuery()
            ->where('provider.id', $providerId)
            ->findOrEmpty();

        if ($provider->isEmpty()) {
            throw new \RuntimeException('服务人员不存在或已停用');
        }

        $providerData = $provider->toArray();
        $package = ProviderPackage::where([
            'id' => $packageId,
            'provider_id' => $providerId,
            'status' => 1,
        ])->whereNull('delete_time')->findOrEmpty();

        if ($package->isEmpty()) {
            throw new \RuntimeException('所选套餐不存在、已停用或不属于当前服务人员');
        }

        $matchedPrice = ServicePackagePriceService::getMatchedPriceByPackage($packageId, $districtCode);
        if (empty($matchedPrice)) {
            throw new \RuntimeException('当前县区下该套餐暂不可售');
        }

        WeddingOrderScheduleService::assertReservable($providerId, $serviceDate);
        $validatedTemplate = ServiceContentTemplateService::validateFrontendFormData(
            (int)$providerData['category_id'],
            $templateFormData
        );
        $profile = WeddingProfileService::getUserProfile($userId);

        $snapshot = WeddingOrderSnapshotService::buildPreviewSnapshot([
            'service_date' => $serviceDate,
            'region' => $matchedPrice,
            'pricing' => $matchedPrice,
            'provider' => self::formatProviderSummary($providerData),
            'package' => [
                'package_id' => (int)$package['id'],
                'name' => (string)$package['name'],
                'summary' => (string)($package['summary'] ?? ''),
                'service_duration' => (string)($package['service_duration'] ?? ''),
            ],
            'profile_summary' => self::buildProfileSummary($profile),
            'template' => $validatedTemplate,
        ]);

        $snapshot['preview_payload'] = [
            'provider_id' => $providerId,
            'package_id' => $packageId,
            'category_id' => (int)$providerData['category_id'],
            'district_code' => (string)$matchedPrice['district_code'],
            'service_date' => $serviceDate,
            'template_form_data' => $validatedTemplate['form_data'],
            'pricing' => $snapshot['pricing'],
            'region' => $snapshot['region'],
        ];

        return $snapshot;
    }

    private static function getActiveProviderQuery()
    {
        return ServiceProvider::alias('provider')
            ->leftJoin('service_category category', 'category.id = provider.category_id')
            ->where('provider.status', 1)
            ->where('category.status', 1)
            ->whereNull('provider.delete_time')
            ->whereNull('category.delete_time')
            ->field([
                'provider.id',
                'provider.user_id',
                'provider.category_id',
                'provider.name',
                'provider.avatar',
                'provider.mobile',
                'provider.tag_ids',
                'provider.intro',
                'provider.is_recommend',
                'provider.sort',
                'category.name' => 'category_name',
            ]);
    }

    private static function buildProviderSaleContext(array $provider, string $districtCode, string $serviceDate): array
    {
        $districtDetail = ServiceRegionService::getDistrictDetail($districtCode);
        if (empty($districtDetail)) {
            throw new \RuntimeException('服务区县不正确');
        }

        $scheduleStatus = ProviderScheduleService::getEffectiveStatus((int)$provider['id'], $serviceDate);
        $packages = ProviderPackage::where([
            'provider_id' => (int)$provider['id'],
            'status' => 1,
        ])->whereNull('delete_time')
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->select()
            ->toArray();

        $matchedPackages = [];
        foreach ($packages as $package) {
            $matchedPrice = ServicePackagePriceService::getMatchedPriceByPackage((int)$package['id'], $districtCode);
            if (empty($matchedPrice)) {
                continue;
            }

            $matchedPackages[] = [
                'package_id' => (int)$package['id'],
                'name' => (string)$package['name'],
                'summary' => (string)($package['summary'] ?? ''),
                'service_duration' => (string)($package['service_duration'] ?? ''),
                'price' => round((float)$matchedPrice['price'], 2),
                'price_match_level' => (string)$matchedPrice['matched_level'],
                'matched_region_code' => (string)$matchedPrice['matched_region_code'],
                'matched_region_name' => (string)$matchedPrice['matched_region_name'],
            ];
        }

        usort($matchedPackages, function (array $left, array $right): int {
            if ($left['price'] === $right['price']) {
                return $left['package_id'] <=> $right['package_id'];
            }
            return $left['price'] <=> $right['price'];
        });

        $sellablePackages = $scheduleStatus === ProviderScheduleService::STATUS_AVAILABLE ? $matchedPackages : [];

        return [
            'is_sellable' => !empty($sellablePackages),
            'schedule_status' => $scheduleStatus,
            'packages' => $sellablePackages,
            'best_package' => $sellablePackages[0] ?? [],
            'region' => [
                'province_code' => (string)$districtDetail['province_code'],
                'province_name' => (string)$districtDetail['province_name'],
                'city_code' => (string)$districtDetail['city_code'],
                'city_name' => (string)$districtDetail['city_name'],
                'district_code' => (string)$districtDetail['district_code'],
                'district_name' => (string)$districtDetail['district_name'],
            ],
        ];
    }

    private static function formatProviderSummary(array $provider): array
    {
        return [
            'provider_id' => (int)$provider['id'],
            'category_id' => (int)$provider['category_id'],
            'name' => (string)$provider['name'],
            'avatar' => self::formatAvatar((string)($provider['avatar'] ?? '')),
            'mobile' => (string)($provider['mobile'] ?? ''),
            'summary' => (string)($provider['intro'] ?? ''),
            'recommend' => (int)($provider['is_recommend'] ?? 0),
        ];
    }

    private static function getTagsByIds(array $tagIds): array
    {
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds))));
        if (empty($tagIds)) {
            return [];
        }

        $tagMap = ServiceTag::whereIn('id', $tagIds)
            ->where(['status' => 1])
            ->whereNull('delete_time')
            ->column('name', 'id');

        $tags = [];
        foreach ($tagIds as $tagId) {
            if (!isset($tagMap[$tagId])) {
                continue;
            }
            $tags[] = [
                'id' => $tagId,
                'name' => (string)$tagMap[$tagId],
            ];
        }

        return $tags;
    }

    private static function buildProfileSummary(array $profile): array
    {
        return [
            'wedding_date' => (string)($profile['wedding_date'] ?? ''),
            'province_name' => (string)($profile['province_name'] ?? ''),
            'city_name' => (string)($profile['city_name'] ?? ''),
            'district_name' => (string)($profile['district_name'] ?? ''),
            'banquet_hotel' => (string)($profile['banquet_hotel'] ?? ''),
            'table_count' => (int)($profile['table_count'] ?? 0),
            'budget_min' => round((float)($profile['budget_min'] ?? 0), 2),
            'budget_max' => round((float)($profile['budget_max'] ?? 0), 2),
            'style_preference' => $profile['style_preference'] ?? [],
            'contact_name' => (string)($profile['contact_name'] ?? ''),
            'contact_mobile' => (string)($profile['contact_mobile'] ?? ''),
            'remark' => (string)($profile['remark'] ?? ''),
        ];
    }

    private static function formatAvatar(string $avatar): string
    {
        $avatar = trim($avatar);
        if ($avatar === '') {
            return '';
        }

        return FileService::getFileUrl($avatar);
    }
}
