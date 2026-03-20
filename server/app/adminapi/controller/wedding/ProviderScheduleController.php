<?php

declare(strict_types=1);

namespace app\adminapi\controller\wedding;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\wedding\ProviderScheduleLists;
use app\adminapi\logic\wedding\ProviderScheduleLogic;
use app\adminapi\validate\wedding\ProviderScheduleValidate;
use think\response\Json;

class ProviderScheduleController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new ProviderScheduleLists());
    }

    public function add(): Json
    {
        $params = (new ProviderScheduleValidate())->post()->goCheck('add');
        ProviderScheduleLogic::add($params);
        return $this->success('新增成功', [], 1, 1);
    }

    public function edit(): Json
    {
        $params = (new ProviderScheduleValidate())->post()->goCheck('edit');
        $result = ProviderScheduleLogic::edit($params);
        if ($result === true) {
            return $this->success('编辑成功', [], 1, 1);
        }

        return $this->fail(ProviderScheduleLogic::getError());
    }

    public function delete(): Json
    {
        $params = (new ProviderScheduleValidate())->post()->goCheck('delete');
        ProviderScheduleLogic::delete($params);
        return $this->success('删除成功', [], 1, 1);
    }

    public function detail(): Json
    {
        $params = (new ProviderScheduleValidate())->goCheck('detail');
        return $this->data(ProviderScheduleLogic::detail($params));
    }

    public function providerOptions(): Json
    {
        return $this->data(ProviderScheduleLogic::getProviderOptions());
    }
}
