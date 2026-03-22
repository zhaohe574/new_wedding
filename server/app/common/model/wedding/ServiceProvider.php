<?php

declare(strict_types=1);

namespace app\common\model\wedding;

use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

class ServiceProvider extends BaseModel
{
    use SoftDelete;

    protected $name = 'service_provider';

    protected $deleteTime = 'delete_time';

    public function getAvatarAttr($value): string
    {
        return trim((string)$value) !== '' ? FileService::getFileUrl((string)$value) : '';
    }

    public function setAvatarAttr($value): string
    {
        return trim((string)$value) !== '' ? FileService::setFileUrl((string)$value) : '';
    }

    public function getTagIdsAttr($value): array
    {
        if (empty($value)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', explode(',', (string)$value))));
    }

    public function setTagIdsAttr($value): string
    {
        if (is_array($value)) {
            $value = array_values(array_unique(array_filter(array_map('intval', $value))));
            return implode(',', $value);
        }

        return trim((string)$value);
    }

    public function getStatusDescAttr($value, $data): string
    {
        return (int)($data['status'] ?? 0) === 1 ? '启用' : '停用';
    }

    public function getIsRecommendDescAttr($value, $data): string
    {
        return (int)($data['is_recommend'] ?? 0) === 1 ? '推荐' : '普通';
    }
}
