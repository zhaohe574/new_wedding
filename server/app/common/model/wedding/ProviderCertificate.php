<?php

declare(strict_types=1);

namespace app\common\model\wedding;

use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

class ProviderCertificate extends BaseModel
{
    use SoftDelete;

    protected $name = 'provider_certificate';

    protected $deleteTime = 'delete_time';

    public function getCertificateImageAttr($value): string
    {
        return trim((string)$value) !== '' ? FileService::getFileUrl((string)$value) : '';
    }

    public function setCertificateImageAttr($value): string
    {
        return trim((string)$value) !== '' ? FileService::setFileUrl((string)$value) : '';
    }

    public function getStatusDescAttr($value, $data): string
    {
        return (int)($data['status'] ?? 0) === 1 ? '启用' : '停用';
    }
}
