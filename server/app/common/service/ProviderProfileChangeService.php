<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\wedding\ProviderChangeRequestEnum;
use app\common\model\wedding\ProviderCertificate;
use app\common\model\wedding\ProviderChangeRequest;
use app\common\model\wedding\ProviderPackage;
use app\common\model\wedding\ProviderPackageAreaPrice;
use app\common\model\wedding\ProviderWork;
use app\common\model\wedding\ServiceProvider;
use app\common\model\wedding\ServiceTag;
use think\facade\Db;

class ProviderProfileChangeService
{
    public static function getProviderCenterDetail(int $userId): array
    {
        $provider = self::getProviderByUserId($userId);
        $providerId = (int)$provider['id'];
        $pendingRequests = self::getPendingRequestMap($providerId);

        return [
            'provider' => self::getProviderProfileData($providerId),
            'certificates' => self::getProviderCertificates($providerId),
            'works' => self::getProviderWorks($providerId),
            'packages' => self::getProviderPackages($providerId),
            'pending_map' => $pendingRequests,
            'review_mode' => ServiceBusinessConfigService::getConfig()['review']['provider_profile_review_mode'] ?? 'admin',
        ];
    }

    public static function getChangeRequestLists(int $userId, array $params = []): array
    {
        $provider = self::getProviderByUserId($userId);
        $pageNo = max(1, (int)($params['page_no'] ?? 1));
        $pageSize = min(20, max(1, (int)($params['page_size'] ?? 10)));
        $offset = ($pageNo - 1) * $pageSize;

        $query = ProviderChangeRequest::where(['provider_id' => (int)$provider['id']])
            ->whereNull('delete_time');

        $changeType = trim((string)($params['change_type'] ?? ''));
        if ($changeType !== '') {
            $query->where('change_type', $changeType);
        }

        if (($params['audit_status'] ?? '') !== '') {
            $query->where('audit_status', (int)$params['audit_status']);
        }

        $count = (clone $query)->count();
        $lists = $query->order(['id' => 'desc'])
            ->limit($offset, $pageSize)
            ->select()
            ->toArray();

        return [
            'lists' => $lists,
            'count' => $count,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ];
    }

    public static function getChangeRequestDetail(int $id): array
    {
        $request = ProviderChangeRequest::where(['id' => $id])
            ->whereNull('delete_time')
            ->findOrEmpty();
        if ($request->isEmpty()) {
            throw new \RuntimeException('变更申请不存在');
        }

        $data = $request->toArray();
        $data['diff'] = [
            'before' => $data['before_snapshot'] ?? [],
            'after' => $data['after_snapshot'] ?? [],
        ];

        return $data;
    }

    public static function submitChange(int $userId, string $changeType, array $payload): array
    {
        $provider = self::getProviderByUserId($userId);
        $providerId = (int)$provider['id'];
        $beforeSnapshot = self::getFormalDataByType($providerId, $changeType);
        $afterSnapshot = self::normalizeChangePayload($providerId, $changeType, $payload);
        $now = time();

        $request = ProviderChangeRequest::where([
            'provider_id' => $providerId,
            'change_type' => $changeType,
            'audit_status' => ProviderChangeRequestEnum::AUDIT_STATUS_PENDING,
        ])->whereNull('delete_time')->findOrEmpty();

        $saveData = [
            'provider_id' => $providerId,
            'change_type' => $changeType,
            'target_id' => 0,
            'change_title' => ProviderChangeRequestEnum::getChangeTypeDesc($changeType) . '变更申请',
            'before_snapshot' => json_encode($beforeSnapshot, JSON_UNESCAPED_UNICODE),
            'after_snapshot' => json_encode($afterSnapshot, JSON_UNESCAPED_UNICODE),
            'audit_status' => ProviderChangeRequestEnum::AUDIT_STATUS_PENDING,
            'audit_role' => ProviderChangeRequestEnum::AUDIT_ROLE_ADMIN,
            'audit_by' => 0,
            'audit_time' => 0,
            'audit_remark' => '',
            'update_time' => $now,
        ];

        if ($request->isEmpty()) {
            $saveData['create_time'] = $now;
            $request = ProviderChangeRequest::create($saveData);
        } else {
            $saveData['id'] = (int)$request['id'];
            ProviderChangeRequest::update($saveData);
            $request = ProviderChangeRequest::find((int)$request['id']);
        }

        return $request ? $request->toArray() : [];
    }

    public static function audit(int $id, int $adminId, int $auditStatus, string $remark = ''): bool
    {
        if (!in_array($auditStatus, [
            ProviderChangeRequestEnum::AUDIT_STATUS_APPROVED,
            ProviderChangeRequestEnum::AUDIT_STATUS_REJECTED,
        ], true)) {
            throw new \RuntimeException('审核结果不正确');
        }

        Db::transaction(function () use ($id, $adminId, $auditStatus, $remark) {
            $request = ProviderChangeRequest::where(['id' => $id])
                ->whereNull('delete_time')
                ->lock(true)
                ->findOrEmpty();
            if ($request->isEmpty()) {
                throw new \RuntimeException('变更申请不存在');
            }
            if ((int)$request['audit_status'] !== ProviderChangeRequestEnum::AUDIT_STATUS_PENDING) {
                throw new \RuntimeException('该变更申请已处理');
            }

            if ($auditStatus === ProviderChangeRequestEnum::AUDIT_STATUS_APPROVED) {
                self::applyApprovedChange($request->toArray());
            }

            ProviderChangeRequest::update([
                'id' => (int)$request['id'],
                'audit_status' => $auditStatus,
                'audit_role' => ProviderChangeRequestEnum::AUDIT_ROLE_ADMIN,
                'audit_by' => $adminId,
                'audit_time' => time(),
                'audit_remark' => trim($remark),
                'update_time' => time(),
            ]);
        });

        return true;
    }

    public static function getPendingRequestMap(int $providerId): array
    {
        $types = ProviderChangeRequestEnum::getChangeTypeDesc(true);
        $map = [];
        foreach (array_keys($types) as $changeType) {
            $request = ProviderChangeRequest::where([
                'provider_id' => $providerId,
                'change_type' => $changeType,
                'audit_status' => ProviderChangeRequestEnum::AUDIT_STATUS_PENDING,
            ])->whereNull('delete_time')
                ->order(['id' => 'desc'])
                ->findOrEmpty();

            $map[$changeType] = $request->isEmpty() ? null : $request->toArray();
        }

        return $map;
    }

    private static function getProviderByUserId(int $userId): ServiceProvider
    {
        $provider = ServiceProvider::where([
            'user_id' => $userId,
            'status' => 1,
        ])->whereNull('delete_time')->findOrEmpty();

        if ($provider->isEmpty()) {
            throw new \RuntimeException('当前账号未绑定服务人员档案');
        }

        return $provider;
    }

    private static function getFormalDataByType(int $providerId, string $changeType)
    {
        return match ($changeType) {
            ProviderChangeRequestEnum::CHANGE_TYPE_PROFILE => self::getProviderProfileData($providerId),
            ProviderChangeRequestEnum::CHANGE_TYPE_CERTIFICATE => self::getProviderCertificates($providerId),
            ProviderChangeRequestEnum::CHANGE_TYPE_WORK => self::getProviderWorks($providerId),
            ProviderChangeRequestEnum::CHANGE_TYPE_PACKAGE => self::getProviderPackages($providerId),
            default => throw new \RuntimeException('暂不支持的变更类型'),
        };
    }

    private static function normalizeChangePayload(int $providerId, string $changeType, array $payload)
    {
        return match ($changeType) {
            ProviderChangeRequestEnum::CHANGE_TYPE_PROFILE => self::normalizeProfilePayload($payload),
            ProviderChangeRequestEnum::CHANGE_TYPE_CERTIFICATE => self::normalizeCertificatesPayload($payload),
            ProviderChangeRequestEnum::CHANGE_TYPE_WORK => self::normalizeWorksPayload($payload),
            ProviderChangeRequestEnum::CHANGE_TYPE_PACKAGE => self::normalizePackagesPayload($providerId, $payload),
            default => throw new \RuntimeException('暂不支持的变更类型'),
        };
    }

    private static function getProviderProfileData(int $providerId): array
    {
        $provider = ServiceProvider::findOrEmpty($providerId);
        if ($provider->isEmpty()) {
            throw new \RuntimeException('服务人员不存在');
        }

        $data = $provider->toArray();

        return [
            'provider_id' => (int)$data['id'],
            'category_id' => (int)($data['category_id'] ?? 0),
            'name' => (string)($data['name'] ?? ''),
            'avatar' => (string)($data['avatar'] ?? ''),
            'mobile' => (string)($data['mobile'] ?? ''),
            'work_wechat_userid' => (string)($data['work_wechat_userid'] ?? ''),
            'tag_ids' => array_values(array_unique(array_filter(array_map('intval', $data['tag_ids'] ?? [])))),
            'intro' => (string)($data['intro'] ?? ''),
            'status' => (int)($data['status'] ?? 0),
            'is_recommend' => (int)($data['is_recommend'] ?? 0),
        ];
    }

    private static function getProviderCertificates(int $providerId): array
    {
        return ProviderCertificate::where(['provider_id' => $providerId])
            ->whereNull('delete_time')
            ->order(['sort' => 'desc', 'id' => 'asc'])
            ->select()
            ->toArray();
    }

    private static function getProviderWorks(int $providerId): array
    {
        return ProviderWork::where(['provider_id' => $providerId])
            ->whereNull('delete_time')
            ->order(['sort' => 'desc', 'id' => 'asc'])
            ->select()
            ->toArray();
    }

    private static function getProviderPackages(int $providerId): array
    {
        $packages = ProviderPackage::where(['provider_id' => $providerId])
            ->whereNull('delete_time')
            ->order(['sort' => 'desc', 'id' => 'asc'])
            ->select()
            ->toArray();

        foreach ($packages as &$item) {
            $item['area_prices'] = ProviderPackageDataService::getAreaPricesByPackageId((int)$item['id']);
        }

        return $packages;
    }

    private static function normalizeProfilePayload(array $payload): array
    {
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $payload['tag_ids'] ?? []))));
        if (!empty($tagIds)) {
            $count = ServiceTag::whereIn('id', $tagIds)->whereNull('delete_time')->count();
            if ($count !== count($tagIds)) {
                throw new \RuntimeException('存在无效的风格标签');
            }
        }

        $name = trim((string)($payload['name'] ?? ''));
        if ($name === '') {
            throw new \RuntimeException('请填写服务人员名称');
        }

        return [
            'name' => $name,
            'avatar' => trim((string)($payload['avatar'] ?? '')),
            'mobile' => trim((string)($payload['mobile'] ?? '')),
            'work_wechat_userid' => trim((string)($payload['work_wechat_userid'] ?? '')),
            'tag_ids' => $tagIds,
            'intro' => trim((string)($payload['intro'] ?? '')),
        ];
    }

    private static function normalizeCertificatesPayload(array $payload): array
    {
        $rows = is_array($payload['items'] ?? null) ? $payload['items'] : $payload;
        if (!is_array($rows)) {
            throw new \RuntimeException('证书数据格式不正确');
        }

        return array_values(array_map(static function ($item) {
            if (!is_array($item)) {
                throw new \RuntimeException('证书数据格式不正确');
            }

            $name = trim((string)($item['certificate_name'] ?? ''));
            if ($name === '') {
                throw new \RuntimeException('请填写证书名称');
            }

            return [
                'certificate_name' => $name,
                'certificate_image' => trim((string)($item['certificate_image'] ?? '')),
                'issuing_authority' => trim((string)($item['issuing_authority'] ?? '')),
                'issue_date' => trim((string)($item['issue_date'] ?? '')),
                'expire_date' => trim((string)($item['expire_date'] ?? '')),
                'description' => trim((string)($item['description'] ?? '')),
                'status' => (int)($item['status'] ?? 1) === 1 ? 1 : 0,
                'sort' => (int)($item['sort'] ?? 0),
            ];
        }, $rows));
    }

    private static function normalizeWorksPayload(array $payload): array
    {
        $rows = is_array($payload['items'] ?? null) ? $payload['items'] : $payload;
        if (!is_array($rows)) {
            throw new \RuntimeException('作品数据格式不正确');
        }

        return array_values(array_map(static function ($item) {
            if (!is_array($item)) {
                throw new \RuntimeException('作品数据格式不正确');
            }

            $title = trim((string)($item['title'] ?? ''));
            if ($title === '') {
                throw new \RuntimeException('请填写作品标题');
            }

            $images = array_values(array_filter(array_map('strval', $item['images'] ?? [])));

            return [
                'title' => $title,
                'cover' => trim((string)($item['cover'] ?? '')),
                'images' => $images,
                'content' => trim((string)($item['content'] ?? '')),
                'status' => (int)($item['status'] ?? 1) === 1 ? 1 : 0,
                'sort' => (int)($item['sort'] ?? 0),
            ];
        }, $rows));
    }

    private static function normalizePackagesPayload(int $providerId, array $payload): array
    {
        $rows = is_array($payload['items'] ?? null) ? $payload['items'] : $payload;
        if (!is_array($rows) || empty($rows)) {
            throw new \RuntimeException('请至少保留一个套餐');
        }

        return array_values(array_map(static function ($item) use ($providerId) {
            if (!is_array($item)) {
                throw new \RuntimeException('套餐数据格式不正确');
            }

            $name = trim((string)($item['name'] ?? ''));
            if ($name === '') {
                throw new \RuntimeException('请填写套餐名称');
            }

            return [
                'provider_id' => $providerId,
                'name' => $name,
                'summary' => trim((string)($item['summary'] ?? '')),
                'service_duration' => trim((string)($item['service_duration'] ?? '')),
                'status' => (int)($item['status'] ?? 1) === 1 ? 1 : 0,
                'sort' => (int)($item['sort'] ?? 0),
                'area_prices' => ProviderPackageDataService::normalizeAreaPrices($item['area_prices'] ?? []),
            ];
        }, $rows));
    }

    private static function applyApprovedChange(array $request): void
    {
        $providerId = (int)($request['provider_id'] ?? 0);
        $afterSnapshot = $request['after_snapshot'] ?? [];
        if (!is_array($afterSnapshot)) {
            $afterSnapshot = [];
        }

        match ((string)($request['change_type'] ?? '')) {
            ProviderChangeRequestEnum::CHANGE_TYPE_PROFILE => self::applyProfileData($providerId, $afterSnapshot),
            ProviderChangeRequestEnum::CHANGE_TYPE_CERTIFICATE => self::replaceCertificates($providerId, $afterSnapshot),
            ProviderChangeRequestEnum::CHANGE_TYPE_WORK => self::replaceWorks($providerId, $afterSnapshot),
            ProviderChangeRequestEnum::CHANGE_TYPE_PACKAGE => self::replacePackages($providerId, $afterSnapshot),
            default => throw new \RuntimeException('暂不支持的变更类型'),
        };
    }

    private static function applyProfileData(int $providerId, array $payload): void
    {
        ServiceProvider::update([
            'id' => $providerId,
            'name' => (string)($payload['name'] ?? ''),
            'avatar' => (string)($payload['avatar'] ?? ''),
            'mobile' => (string)($payload['mobile'] ?? ''),
            'work_wechat_userid' => (string)($payload['work_wechat_userid'] ?? ''),
            'tag_ids' => $payload['tag_ids'] ?? [],
            'intro' => (string)($payload['intro'] ?? ''),
            'update_time' => time(),
        ]);
    }

    private static function replaceCertificates(int $providerId, array $rows): void
    {
        $oldIds = ProviderCertificate::where(['provider_id' => $providerId])
            ->whereNull('delete_time')
            ->column('id');
        if (!empty($oldIds)) {
            ProviderCertificate::destroy($oldIds);
        }

        foreach ($rows as $item) {
            ProviderCertificate::create([
                'provider_id' => $providerId,
                'certificate_name' => (string)($item['certificate_name'] ?? ''),
                'certificate_image' => (string)($item['certificate_image'] ?? ''),
                'issuing_authority' => (string)($item['issuing_authority'] ?? ''),
                'issue_date' => $item['issue_date'] ?: null,
                'expire_date' => $item['expire_date'] ?: null,
                'description' => (string)($item['description'] ?? ''),
                'status' => (int)($item['status'] ?? 1),
                'sort' => (int)($item['sort'] ?? 0),
                'create_time' => time(),
                'update_time' => time(),
            ]);
        }
    }

    private static function replaceWorks(int $providerId, array $rows): void
    {
        $oldIds = ProviderWork::where(['provider_id' => $providerId])
            ->whereNull('delete_time')
            ->column('id');
        if (!empty($oldIds)) {
            ProviderWork::destroy($oldIds);
        }

        foreach ($rows as $item) {
            ProviderWork::create([
                'provider_id' => $providerId,
                'title' => (string)($item['title'] ?? ''),
                'cover' => (string)($item['cover'] ?? ''),
                'images' => $item['images'] ?? [],
                'content' => (string)($item['content'] ?? ''),
                'status' => (int)($item['status'] ?? 1),
                'sort' => (int)($item['sort'] ?? 0),
                'create_time' => time(),
                'update_time' => time(),
            ]);
        }
    }

    private static function replacePackages(int $providerId, array $rows): void
    {
        $oldIds = ProviderPackage::where(['provider_id' => $providerId])
            ->whereNull('delete_time')
            ->column('id');
        if (!empty($oldIds)) {
            ProviderPackageAreaPrice::whereIn('package_id', $oldIds)->delete();
            ProviderPackage::destroy($oldIds);
        }

        foreach ($rows as $item) {
            $package = ProviderPackage::create([
                'provider_id' => $providerId,
                'name' => (string)($item['name'] ?? ''),
                'summary' => (string)($item['summary'] ?? ''),
                'service_duration' => (string)($item['service_duration'] ?? ''),
                'status' => (int)($item['status'] ?? 1),
                'sort' => (int)($item['sort'] ?? 0),
                'create_time' => time(),
                'update_time' => time(),
            ]);

            foreach (($item['area_prices'] ?? []) as $areaPrice) {
                ProviderPackageAreaPrice::create([
                    'package_id' => (int)$package->id,
                    'region_level' => (string)($areaPrice['region_level'] ?? ''),
                    'region_code' => (string)($areaPrice['region_code'] ?? ''),
                    'region_name' => (string)($areaPrice['region_name'] ?? ''),
                    'province_code' => (string)($areaPrice['province_code'] ?? ''),
                    'city_code' => (string)($areaPrice['city_code'] ?? ''),
                    'district_code' => (string)($areaPrice['district_code'] ?? ''),
                    'price' => round((float)($areaPrice['price'] ?? 0), 2),
                    'status' => (int)($areaPrice['status'] ?? 1),
                    'sort' => (int)($areaPrice['sort'] ?? 0),
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            }
        }
    }
}
