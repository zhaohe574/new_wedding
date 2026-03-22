<?php

declare(strict_types=1);

namespace app\api\validate;

use app\common\validate\BaseValidate;

class NoticeValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'page_no' => 'integer|gt:0',
        'page_size' => 'integer|between:1,50',
    ];

    protected $message = [
        'id.require' => '通知参数缺失',
        'id.integer' => '通知参数不正确',
        'id.gt' => '通知参数不正确',
        'page_no.integer' => '页码参数不正确',
        'page_no.gt' => '页码参数不正确',
        'page_size.integer' => '分页参数不正确',
        'page_size.between' => '分页参数不正确',
    ];

    protected $scene = [
        'lists' => ['page_no', 'page_size'],
        'read' => ['id'],
    ];
}
