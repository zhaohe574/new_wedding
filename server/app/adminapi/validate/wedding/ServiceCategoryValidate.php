<?php

declare(strict_types=1);

namespace app\adminapi\validate\wedding;

use app\common\model\wedding\ServiceCategory;
use app\common\model\wedding\ServiceProvider;
use app\common\validate\BaseValidate;

class ServiceCategoryValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|checkCategory',
        'name' => 'require|length:1,60|checkNameUnique',
        'sort' => 'egt:0',
        'status' => 'require|in:0,1',
    ];

    protected $message = [
        'id.require' => '分类 ID 不能为空',
        'name.require' => '分类名称不能为空',
        'name.length' => '分类名称长度须在 1-60 位字符',
        'sort.egt' => '排序值不正确',
        'status.require' => '请选择状态',
        'status.in' => '分类状态不正确',
    ];

    public function sceneAdd()
    {
        return $this->remove('id', true);
    }

    public function sceneEdit()
    {
        return $this->only(['id', 'name', 'sort', 'status']);
    }

    public function sceneDelete()
    {
        return $this->only(['id'])->append('id', 'checkDeleteAble');
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneStatus()
    {
        return $this->only(['id', 'status']);
    }

    public function checkCategory($value): bool|string
    {
        $category = ServiceCategory::findOrEmpty((int)$value);
        if ($category->isEmpty()) {
            return '服务分类不存在';
        }
        return true;
    }

    public function checkNameUnique($value): bool|string
    {
        $query = ServiceCategory::where(['name' => trim((string)$value)])->whereNull('delete_time');
        $id = (int)request()->post('id/d', 0);
        if ($id > 0) {
            $query->where('id', '<>', $id);
        }

        if ($query->findOrEmpty()->isEmpty()) {
            return true;
        }

        return '分类名称已存在';
    }

    public function checkDeleteAble($value): bool|string
    {
        $provider = ServiceProvider::where(['category_id' => (int)$value])->whereNull('delete_time')->findOrEmpty();
        if (!$provider->isEmpty()) {
            return '该分类已被服务人员使用，暂不可删除';
        }
        return true;
    }
}
