<?php

declare(strict_types=1);

namespace app\common\model\wedding;

use app\common\model\BaseModel;

class ServiceOrderSnapshot extends BaseModel
{
    protected $name = 'service_order_snapshot';

    public function getProviderSnapshotAttr($value): array
    {
        return $this->decodeJson($value);
    }

    public function getPackageSnapshotAttr($value): array
    {
        return $this->decodeJson($value);
    }

    public function getProfileSnapshotAttr($value): array
    {
        return $this->decodeJson($value);
    }

    public function getTemplateSnapshotAttr($value): array
    {
        return $this->decodeJson($value);
    }

    private function decodeJson($value): array
    {
        $data = json_decode((string)$value, true);
        return is_array($data) ? $data : [];
    }
}

