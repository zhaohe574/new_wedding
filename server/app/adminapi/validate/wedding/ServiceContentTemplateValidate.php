<?php

declare(strict_types=1);

namespace app\adminapi\validate\wedding;

use app\common\model\wedding\ServiceCategory;
use app\common\model\wedding\ServiceContentTemplate;
use app\common\validate\BaseValidate;

class ServiceContentTemplateValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|checkTemplate',
        'category_id' => 'require|integer|gt:0|checkCategory',
        'name' => 'require|length:1,80',
        'status' => 'require|in:0,1',
        'sort' => 'integer|egt:0',
        'pages' => 'require|array|checkPages',
    ];

    protected $message = [
        'id.require' => '模板 ID 不能为空',
        'category_id.require' => '请选择服务分类',
        'category_id.integer' => '服务分类不正确',
        'category_id.gt' => '服务分类不正确',
        'name.require' => '请输入模板名称',
        'name.length' => '模板名称长度须在 1-80 位字符',
        'status.require' => '请选择状态',
        'status.in' => '模板状态不正确',
        'sort.integer' => '排序值不正确',
        'sort.egt' => '排序值不正确',
        'pages.require' => '请至少配置一个模板页面',
        'pages.array' => '模板页面格式不正确',
    ];

    public function sceneAdd()
    {
        return $this->remove('id', true);
    }

    public function sceneEdit()
    {
        return $this->only(['id', 'category_id', 'name', 'status', 'sort', 'pages']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneStatus()
    {
        return $this->only(['id', 'status']);
    }

    public function checkTemplate($value): bool|string
    {
        return ServiceContentTemplate::findOrEmpty((int)$value)->isEmpty() ? '模板不存在' : true;
    }

    public function checkCategory($value): bool|string
    {
        return ServiceCategory::findOrEmpty((int)$value)->isEmpty() ? '服务分类不存在' : true;
    }

    public function checkPages($value): bool|string
    {
        if (!is_array($value) || empty($value)) {
            return '请至少配置一个模板页面';
        }

        return true;
    }
}
