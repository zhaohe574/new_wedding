<?php

declare(strict_types=1);

namespace app\common\model\notice;

use app\common\model\BaseModel;

class NoticeMnpLog extends BaseModel
{
    protected $name = 'notice_mnp_log';

    public function getRequestDataAttr($value): array
    {
        $data = json_decode((string)$value, true);
        return is_array($data) ? $data : [];
    }

    public function getResponseDataAttr($value): array
    {
        $data = json_decode((string)$value, true);
        return is_array($data) ? $data : [];
    }

    public function getSendStatusDescAttr($value, $data): string
    {
        return (int)($data['send_status'] ?? 0) === 1 ? '发送成功' : '发送失败';
    }
}
