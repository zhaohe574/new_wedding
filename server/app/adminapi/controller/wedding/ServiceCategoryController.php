<?php

declare(strict_types=1);

namespace app\adminapi\controller\wedding;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\wedding\ServiceCategoryLists;
use app\adminapi\logic\wedding\ServiceCategoryLogic;
use app\adminapi\validate\wedding\ServiceCategoryValidate;
use think\response\Json;

class ServiceCategoryController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new ServiceCategoryLists());
    }

    public function add(): Json
    {
        $params = (new ServiceCategoryValidate())->post()->goCheck('add');
        ServiceCategoryLogic::add($params);
        return $this->success('新增成功', [], 1, 1);
    }

    public function edit(): Json
    {
        $params = (new ServiceCategoryValidate())->post()->goCheck('edit');
        $result = ServiceCategoryLogic::edit($params);
        if ($result === true) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(ServiceCategoryLogic::getError());
    }

    public function delete(): Json
    {
        $params = (new ServiceCategoryValidate())->post()->goCheck('delete');
        ServiceCategoryLogic::delete($params);
        return $this->success('删除成功', [], 1, 1);
    }

    public function detail(): Json
    {
        $params = (new ServiceCategoryValidate())->goCheck('detail');
        return $this->data(ServiceCategoryLogic::detail($params));
    }

    public function updateStatus(): Json
    {
        $params = (new ServiceCategoryValidate())->post()->goCheck('status');
        ServiceCategoryLogic::updateStatus($params);
        return $this->success('修改成功', [], 1, 1);
    }

    public function all(): Json
    {
        return $this->data(ServiceCategoryLogic::getAllData());
    }
}
