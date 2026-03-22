<?php

declare(strict_types=1);

namespace app\adminapi\controller\wedding;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\wedding\ServiceOrderReviewLists;
use app\adminapi\logic\wedding\ServiceOrderReviewLogic;
use app\adminapi\validate\wedding\ServiceOrderReviewValidate;
use think\response\Json;

class ServiceOrderReviewController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new ServiceOrderReviewLists());
    }

    public function detail(): Json
    {
        $params = (new ServiceOrderReviewValidate())->goCheck('detail');
        return $this->data(ServiceOrderReviewLogic::detail($params));
    }

    public function audit(): Json
    {
        $params = (new ServiceOrderReviewValidate())->post()->goCheck('audit');
        $result = ServiceOrderReviewLogic::audit($params, $this->adminId);
        if ($result === true) {
            return $this->success('审核成功', [], 1, 1);
        }
        return $this->fail(ServiceOrderReviewLogic::getError());
    }
}
