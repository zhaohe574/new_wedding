<?php

declare(strict_types=1);

namespace app\common\model\wedding;

use app\common\enum\wedding\ServiceOrderReviewEnum;
use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

class ProviderPost extends BaseModel
{
    use SoftDelete;

    protected $name = 'provider_post';

    protected $deleteTime = 'delete_time';

    public function getCoverAttr($value): string
    {
        return trim((string)$value) !== '' ? FileService::getFileUrl((string)$value) : '';
    }

    public function setCoverAttr($value): string
    {
        return trim((string)$value) !== '' ? FileService::setFileUrl((string)$value) : '';
    }

    public function getImagesAttr($value): array
    {
        $images = json_decode((string)$value, true);
        if (!is_array($images)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($item) {
            $path = trim((string)$item);
            return $path !== '' ? FileService::getFileUrl($path) : '';
        }, $images)));
    }

    public function setImagesAttr($value): string
    {
        if (!is_array($value)) {
            return '[]';
        }

        $images = array_values(array_filter(array_map(static function ($item) {
            $path = trim((string)$item);
            return $path !== '' ? FileService::setFileUrl($path) : '';
        }, $value)));

        return json_encode($images, JSON_UNESCAPED_UNICODE);
    }

    public function getAuditStatusDescAttr($value, $data): string
    {
        return ServiceOrderReviewEnum::getAuditStatusDesc((int)($data['audit_status'] ?? 0));
    }

    public function getAuditRoleDescAttr($value, $data): string
    {
        return ServiceOrderReviewEnum::getAuditRoleDesc((string)($data['audit_role'] ?? ''));
    }

    public function getStatusDescAttr($value, $data): string
    {
        return (int)($data['status'] ?? 0) === 1 ? '启用' : '停用';
    }
}
