<?php

declare(strict_types=1);

namespace app\adminapi\controller\wedding;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\wedding\ServiceOrderChangeLists;
use app\adminapi\logic\wedding\ServiceOrderChangeLogic;
use app\adminapi\validate\wedding\ServiceOrderChangeValidate;
use think\response\Json;

class ServiceOrderChangeController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new ServiceOrderChangeLists());
    }

    public function detail(): Json
    {
        $params = (new ServiceOrderChangeValidate())->goCheck('detail');
        return $this->data(ServiceOrderChangeLogic::detail($params));
    }

    public function handle(): Json
    {
        $params = (new ServiceOrderChangeValidate())->post()->goCheck('handle');
        $result = ServiceOrderChangeLogic::handle($params, $this->adminId);
        if ($result === true) {
            return $this->success('处理成功', [], 1, 1);
        }
        return $this->fail(ServiceOrderChangeLogic::getError());
    }
}
