<?php

declare(strict_types=1);

namespace app\adminapi\controller\wedding;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\wedding\ServiceProviderLists;
use app\adminapi\logic\wedding\ServiceProviderLogic;
use app\adminapi\validate\wedding\ServiceProviderValidate;
use think\response\Json;

class ServiceProviderController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new ServiceProviderLists());
    }

    public function add(): Json
    {
        $params = (new ServiceProviderValidate())->post()->goCheck('add');
        ServiceProviderLogic::add($params);
        return $this->success('新增成功', [], 1, 1);
    }

    public function edit(): Json
    {
        $params = (new ServiceProviderValidate())->post()->goCheck('edit');
        $result = ServiceProviderLogic::edit($params);
        if ($result === true) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(ServiceProviderLogic::getError());
    }

    public function delete(): Json
    {
        $params = (new ServiceProviderValidate())->post()->goCheck('delete');
        ServiceProviderLogic::delete($params);
        return $this->success('删除成功', [], 1, 1);
    }

    public function detail(): Json
    {
        $params = (new ServiceProviderValidate())->goCheck('detail');
        return $this->data(ServiceProviderLogic::detail($params));
    }

    public function updateStatus(): Json
    {
        $params = (new ServiceProviderValidate())->post()->goCheck('status');
        ServiceProviderLogic::updateStatus($params);
        return $this->success('修改成功', [], 1, 1);
    }

    public function userOptions(): Json
    {
        $keyword = trim((string)$this->request->get('keyword/s', ''));
        return $this->data(ServiceProviderLogic::getUserOptions($keyword));
    }

    public function categoryOptions(): Json
    {
        return $this->data(ServiceProviderLogic::getCategoryOptions());
    }

    public function tagOptions(): Json
    {
        return $this->data(ServiceProviderLogic::getTagOptions());
    }
}
