<?php

declare(strict_types=1);

namespace app\adminapi\validate\wedding;

use app\common\model\user\User;
use app\common\model\wedding\ServiceCategory;
use app\common\model\wedding\ServiceProvider;
use app\common\model\wedding\ServiceTag;
use app\common\validate\BaseValidate;

class ServiceProviderValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|checkProvider',
        'user_id' => 'require|integer|gt:0|checkUserExists|checkUserUnique',
        'category_id' => 'require|integer|gt:0|checkCategoryExists',
        'name' => 'require|length:1,60',
        'mobile' => 'max:32',
        'work_wechat_userid' => 'max:64',
        'tag_ids' => 'array|checkTagIds',
        'status' => 'require|in:0,1',
        'is_recommend' => 'require|in:0,1',
        'sort' => 'egt:0',
        'intro' => 'max:1000',
    ];

    protected $message = [
        'id.require' => '服务人员 ID 不能为空',
        'user_id.require' => '请选择绑定用户',
        'user_id.integer' => '绑定用户不正确',
        'user_id.gt' => '绑定用户不正确',
        'category_id.require' => '请选择服务分类',
        'category_id.integer' => '服务分类不正确',
        'category_id.gt' => '服务分类不正确',
        'name.require' => '请输入服务人员名称',
        'name.length' => '服务人员名称长度须在 1-60 位字符',
        'mobile.max' => '手机号长度不能超过 32 位字符',
        'work_wechat_userid.max' => '企业微信接收账号长度不能超过 64 位字符',
        'tag_ids.array' => '风格标签格式不正确',
        'status.require' => '请选择状态',
        'status.in' => '服务人员状态不正确',
        'is_recommend.require' => '请选择推荐状态',
        'is_recommend.in' => '推荐状态不正确',
        'sort.egt' => '排序值不正确',
        'intro.max' => '简介内容不能超过 1000 个字符',
    ];

    public function sceneAdd()
    {
        return $this->remove('id', true);
    }

    public function sceneEdit()
    {
        return $this->only([
            'id',
            'user_id',
            'category_id',
            'name',
            'avatar',
            'mobile',
            'work_wechat_userid',
            'tag_ids',
            'status',
            'is_recommend',
            'sort',
            'intro',
        ]);
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

    public function checkProvider($value): bool|string
    {
        $provider = ServiceProvider::findOrEmpty((int)$value);
        if ($provider->isEmpty()) {
            return '服务人员不存在';
        }
        return true;
    }

    public function checkUserExists($value): bool|string
    {
        $user = User::findOrEmpty((int)$value);
        if ($user->isEmpty()) {
            return '绑定用户不存在';
        }
        return true;
    }

    public function checkUserUnique($value): bool|string
    {
        $query = ServiceProvider::where(['user_id' => (int)$value])->whereNull('delete_time');
        $id = (int)request()->post('id/d', 0);
        if ($id > 0) {
            $query->where('id', '<>', $id);
        }

        if ($query->findOrEmpty()->isEmpty()) {
            return true;
        }

        return '该用户已绑定服务人员档案';
    }

    public function checkCategoryExists($value): bool|string
    {
        $category = ServiceCategory::findOrEmpty((int)$value);
        if ($category->isEmpty()) {
            return '服务分类不存在';
        }
        return true;
    }

    public function checkTagIds($value): bool|string
    {
        if (!is_array($value)) {
            return '风格标签格式不正确';
        }

        $tagIds = array_values(array_unique(array_filter(array_map('intval', $value))));
        if (empty($tagIds)) {
            return true;
        }

        $count = ServiceTag::whereIn('id', $tagIds)->whereNull('delete_time')->count();
        if ($count !== count($tagIds)) {
            return '存在无效的风格标签';
        }

        return true;
    }
}
