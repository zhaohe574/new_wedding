<?php

declare(strict_types=1);

namespace app\adminapi\validate\wedding;

use app\common\model\wedding\ServiceProvider;
use app\common\model\wedding\ServiceTag;
use app\common\validate\BaseValidate;

class ServiceTagValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|checkTag',
        'name' => 'require|length:1,60|checkNameUnique',
        'sort' => 'egt:0',
        'status' => 'require|in:0,1',
    ];

    protected $message = [
        'id.require' => '标签 ID 不能为空',
        'name.require' => '标签名称不能为空',
        'name.length' => '标签名称长度须在 1-60 位字符',
        'sort.egt' => '排序值不正确',
        'status.require' => '请选择状态',
        'status.in' => '标签状态不正确',
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

    public function checkTag($value): bool|string
    {
        $tag = ServiceTag::findOrEmpty((int)$value);
        if ($tag->isEmpty()) {
            return '风格标签不存在';
        }
        return true;
    }

    public function checkNameUnique($value): bool|string
    {
        $query = ServiceTag::where(['name' => trim((string)$value)])->whereNull('delete_time');
        $id = (int)request()->post('id/d', 0);
        if ($id > 0) {
            $query->where('id', '<>', $id);
        }

        if ($query->findOrEmpty()->isEmpty()) {
            return true;
        }

        return '标签名称已存在';
    }

    public function checkDeleteAble($value): bool|string
    {
        $provider = ServiceProvider::whereFindInSet('tag_ids', (string)(int)$value)->whereNull('delete_time')->findOrEmpty();
        if (!$provider->isEmpty()) {
            return '该标签已被服务人员使用，暂不可删除';
        }
        return true;
    }
}
