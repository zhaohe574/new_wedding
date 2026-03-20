<?php

declare(strict_types=1);

namespace app\adminapi\controller\wedding;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\wedding\ProviderPackageLists;
use app\adminapi\logic\wedding\ProviderPackageLogic;
use app\adminapi\validate\wedding\ProviderPackageValidate;
use think\response\Json;

class ProviderPackageController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new ProviderPackageLists());
    }

    public function add(): Json
    {
        $params = (new ProviderPackageValidate())->post()->goCheck('add');
        ProviderPackageLogic::add($params);
        return $this->success('新增成功', [], 1, 1);
    }

    public function edit(): Json
    {
        $params = (new ProviderPackageValidate())->post()->goCheck('edit');
        $result = ProviderPackageLogic::edit($params);
        if ($result === true) {
            return $this->success('编辑成功', [], 1, 1);
        }

        return $this->fail(ProviderPackageLogic::getError());
    }

    public function delete(): Json
    {
        $params = (new ProviderPackageValidate())->post()->goCheck('delete');
        ProviderPackageLogic::delete($params);
        return $this->success('删除成功', [], 1, 1);
    }

    public function detail(): Json
    {
        $params = (new ProviderPackageValidate())->goCheck('detail');
        return $this->data(ProviderPackageLogic::detail($params));
    }

    public function updateStatus(): Json
    {
        $params = (new ProviderPackageValidate())->post()->goCheck('status');
        ProviderPackageLogic::updateStatus($params);
        return $this->success('修改成功', [], 1, 1);
    }

    public function providerOptions(): Json
    {
        return $this->data(ProviderPackageLogic::getProviderOptions());
    }

    public function openRegionOptions(): Json
    {
        return $this->data(ProviderPackageLogic::getOpenRegionOptions());
    }
}
