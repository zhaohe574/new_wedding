<?php

declare(strict_types=1);

namespace app\adminapi\controller\wedding;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\wedding\ServiceOrderRefundLists;
use app\adminapi\logic\wedding\ServiceOrderRefundLogic;
use app\adminapi\validate\wedding\ServiceOrderRefundValidate;
use think\response\Json;

class ServiceOrderRefundController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new ServiceOrderRefundLists());
    }

    public function detail(): Json
    {
        $params = (new ServiceOrderRefundValidate())->goCheck('detail');
        return $this->data(ServiceOrderRefundLogic::detail($params));
    }

    public function handle(): Json
    {
        $params = (new ServiceOrderRefundValidate())->post()->goCheck('handle');
        $result = ServiceOrderRefundLogic::handle($params, $this->adminId);
        if ($result === true) {
            return $this->success('处理成功', [], 1, 1);
        }
        return $this->fail(ServiceOrderRefundLogic::getError());
    }

    public function manualRefund(): Json
    {
        $params = (new ServiceOrderRefundValidate())->post()->goCheck('manualRefund');
        $result = ServiceOrderRefundLogic::manualRefund($params, $this->adminId);
        if ($result === true) {
            return $this->success('后台退款已发起', [], 1, 1);
        }
        return $this->fail(ServiceOrderRefundLogic::getError());
    }
}
