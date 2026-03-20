<?php

declare(strict_types=1);

namespace app\adminapi\controller\wedding;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\wedding\ServiceTagLists;
use app\adminapi\logic\wedding\ServiceTagLogic;
use app\adminapi\validate\wedding\ServiceTagValidate;
use think\response\Json;

class ServiceTagController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new ServiceTagLists());
    }

    public function add(): Json
    {
        $params = (new ServiceTagValidate())->post()->goCheck('add');
        ServiceTagLogic::add($params);
        return $this->success('新增成功', [], 1, 1);
    }

    public function edit(): Json
    {
        $params = (new ServiceTagValidate())->post()->goCheck('edit');
        $result = ServiceTagLogic::edit($params);
        if ($result === true) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(ServiceTagLogic::getError());
    }

    public function delete(): Json
    {
        $params = (new ServiceTagValidate())->post()->goCheck('delete');
        ServiceTagLogic::delete($params);
        return $this->success('删除成功', [], 1, 1);
    }

    public function detail(): Json
    {
        $params = (new ServiceTagValidate())->goCheck('detail');
        return $this->data(ServiceTagLogic::detail($params));
    }

    public function updateStatus(): Json
    {
        $params = (new ServiceTagValidate())->post()->goCheck('status');
        ServiceTagLogic::updateStatus($params);
        return $this->success('修改成功', [], 1, 1);
    }

    public function all(): Json
    {
        return $this->data(ServiceTagLogic::getAllData());
    }
}
