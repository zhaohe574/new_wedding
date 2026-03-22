<?php

declare(strict_types=1);

namespace app\api\validate;

use app\common\validate\BaseValidate;

class WeddingProviderPostValidate extends BaseValidate
{
    protected $rule = [
        'page_no' => 'integer|gt:0',
        'page_size' => 'integer|between:1,20',
        'audit_status' => 'in:0,1,2',
        'only_pending' => 'in:0,1',
        'id' => 'integer|gt:0',
        'post_id' => 'require|integer|gt:0',
        'provider_id' => 'require|integer|gt:0',
        'title' => 'require|max:120',
        'content' => 'require|max:5000',
        'cover' => 'max:255',
        'images' => 'array',
        'status' => 'in:0,1',
        'comment_id' => 'require|integer|gt:0',
        'parent_id' => 'integer|egt:0',
        'comment_content' => 'require|max:1000',
        'audit_remark' => 'max:500',
    ];

    protected $message = [
        'page_no.integer' => '页码参数不正确',
        'page_no.gt' => '页码参数不正确',
        'page_size.integer' => '分页参数不正确',
        'page_size.between' => '分页参数不正确',
        'audit_status.in' => '审核状态不正确',
        'only_pending.in' => '筛选参数不正确',
        'id.integer' => '动态参数不正确',
        'id.gt' => '动态参数不正确',
        'post_id.require' => '动态参数缺失',
        'post_id.integer' => '动态参数不正确',
        'post_id.gt' => '动态参数不正确',
        'provider_id.require' => '服务人员参数缺失',
        'provider_id.integer' => '服务人员参数不正确',
        'provider_id.gt' => '服务人员参数不正确',
        'title.require' => '请输入动态标题',
        'title.max' => '动态标题最多120个字符',
        'content.require' => '请输入动态内容',
        'content.max' => '动态内容最多5000个字符',
        'cover.max' => '封面地址长度不能超过255个字符',
        'images.array' => '图片格式不正确',
        'status.in' => '状态不正确',
        'comment_id.require' => '评论参数缺失',
        'comment_id.integer' => '评论参数不正确',
        'comment_id.gt' => '评论参数不正确',
        'parent_id.integer' => '父评论参数不正确',
        'parent_id.egt' => '父评论参数不正确',
        'comment_content.require' => '请输入评论内容',
        'comment_content.max' => '评论内容最多1000个字符',
        'audit_remark.max' => '审核说明最多500个字符',
    ];

    protected $scene = [
        'providerLists' => ['page_no', 'page_size', 'audit_status'],
        'save' => ['id', 'title', 'content', 'cover', 'images', 'status'],
        'delete' => ['id'],
        'pendingComments' => ['page_no', 'page_size', 'only_pending'],
        'auditComment' => ['comment_id', 'audit_status', 'audit_remark'],
        'publicLists' => ['provider_id', 'page_no', 'page_size'],
        'createComment' => ['post_id', 'parent_id', 'comment_content'],
    ];
}
