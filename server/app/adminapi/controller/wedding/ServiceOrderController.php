<?php

declare(strict_types=1);

namespace app\adminapi\controller\wedding;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\wedding\ServiceOrderLists;
use app\adminapi\logic\wedding\ServiceOrderLogic;
use app\adminapi\validate\wedding\ServiceOrderValidate;
use think\response\Json;

class ServiceOrderController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new ServiceOrderLists());
    }

    public function detail(): Json
    {
        $params = (new ServiceOrderValidate())->goCheck('detail');
        return $this->data(ServiceOrderLogic::detail($params));
    }

    public function offlineVoucherAudit(): Json
    {
        $params = (new ServiceOrderValidate())->post()->goCheck('offlineVoucherAudit');
        $result = ServiceOrderLogic::offlineVoucherAudit($params, $this->adminId);
        if ($result === true) {
            return $this->success('审核成功', [], 1, 1);
        }
        return $this->fail(ServiceOrderLogic::getError());
    }
}

