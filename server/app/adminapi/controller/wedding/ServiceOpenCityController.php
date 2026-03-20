<?php

declare(strict_types=1);

namespace app\adminapi\controller\wedding;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\wedding\ServiceOpenCityLists;
use app\adminapi\logic\wedding\ServiceOpenCityLogic;
use app\adminapi\validate\wedding\ServiceOpenCityValidate;
use think\response\Json;

class ServiceOpenCityController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new ServiceOpenCityLists());
    }

    public function add(): Json
    {
        $params = (new ServiceOpenCityValidate())->post()->goCheck('add');
        ServiceOpenCityLogic::add($params);
        return $this->success('新增成功', [], 1, 1);
    }

    public function edit(): Json
    {
        $params = (new ServiceOpenCityValidate())->post()->goCheck('edit');
        $result = ServiceOpenCityLogic::edit($params);
        if ($result === true) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(ServiceOpenCityLogic::getError());
    }

    public function delete(): Json
    {
        $params = (new ServiceOpenCityValidate())->post()->goCheck('delete');
        ServiceOpenCityLogic::delete($params);
        return $this->success('删除成功', [], 1, 1);
    }

    public function detail(): Json
    {
        $params = (new ServiceOpenCityValidate())->goCheck('detail');
        return $this->data(ServiceOpenCityLogic::detail($params));
    }

    public function updateStatus(): Json
    {
        $params = (new ServiceOpenCityValidate())->post()->goCheck('status');
        ServiceOpenCityLogic::updateStatus($params);
        return $this->success('修改成功', [], 1, 1);
    }

    public function cityOptions(): Json
    {
        return $this->data(ServiceOpenCityLogic::getCityOptions());
    }
}
