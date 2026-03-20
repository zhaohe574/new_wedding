<?php

declare(strict_types=1);

namespace app\adminapi\controller\wedding;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\wedding\ServiceContentTemplateLists;
use app\adminapi\logic\wedding\ServiceContentTemplateLogic;
use app\adminapi\validate\wedding\ServiceContentTemplateValidate;
use think\response\Json;

class ServiceContentTemplateController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new ServiceContentTemplateLists());
    }

    public function add(): Json
    {
        $params = (new ServiceContentTemplateValidate())->post()->goCheck('add');
        ServiceContentTemplateLogic::add($params);
        return $this->success('新增成功', [], 1, 1);
    }

    public function edit(): Json
    {
        $params = (new ServiceContentTemplateValidate())->post()->goCheck('edit');
        $result = ServiceContentTemplateLogic::edit($params);
        if ($result === true) {
            return $this->success('编辑成功', [], 1, 1);
        }

        return $this->fail(ServiceContentTemplateLogic::getError());
    }

    public function delete(): Json
    {
        $params = (new ServiceContentTemplateValidate())->post()->goCheck('delete');
        ServiceContentTemplateLogic::delete($params);
        return $this->success('删除成功', [], 1, 1);
    }

    public function detail(): Json
    {
        $params = (new ServiceContentTemplateValidate())->goCheck('detail');
        return $this->data(ServiceContentTemplateLogic::detail($params));
    }

    public function updateStatus(): Json
    {
        $params = (new ServiceContentTemplateValidate())->post()->goCheck('status');
        ServiceContentTemplateLogic::updateStatus($params);
        return $this->success('修改成功', [], 1, 1);
    }

    public function categoryOptions(): Json
    {
        return $this->data(ServiceContentTemplateLogic::getCategoryOptions());
    }
}
