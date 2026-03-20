<?php

declare(strict_types=1);

namespace app\adminapi\validate\wedding;

use app\common\model\wedding\ServiceOpenCity;
use app\common\service\ServiceRegionService;
use app\common\validate\BaseValidate;

class ServiceOpenCityValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|checkOpenCity',
        'city_code' => 'require|checkCityCode|checkCityUnique',
        'sort' => 'egt:0',
        'status' => 'require|in:0,1',
    ];

    protected $message = [
        'id.require' => '开放城市 ID 不能为空',
        'city_code.require' => '请选择开放城市',
        'sort.egt' => '排序值不正确',
        'status.require' => '请选择状态',
        'status.in' => '开放状态不正确',
    ];

    public function sceneAdd()
    {
        return $this->remove('id', true);
    }

    public function sceneEdit()
    {
        return $this->only(['id', 'city_code', 'sort', 'status']);
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

    public function checkOpenCity($value): bool|string
    {
        $openCity = ServiceOpenCity::findOrEmpty((int)$value);
        if ($openCity->isEmpty()) {
            return '开放城市不存在';
        }
        return true;
    }

    public function checkCityCode($value): bool|string
    {
        if (empty(ServiceRegionService::getCityDetail((string)$value))) {
            return '城市编码不存在';
        }
        return true;
    }

    public function checkCityUnique($value): bool|string
    {
        $query = ServiceOpenCity::where(['city_code' => trim((string)$value)])->whereNull('delete_time');
        $id = (int)request()->post('id/d', 0);
        if ($id > 0) {
            $query->where('id', '<>', $id);
        }

        if ($query->findOrEmpty()->isEmpty()) {
            return true;
        }

        return '该城市已在开放列表中';
    }
}
