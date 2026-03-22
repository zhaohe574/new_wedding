<?php

declare(strict_types=1);

namespace app\adminapi\controller\wedding;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\wedding\ProviderChangeRequestLists;
use app\adminapi\logic\wedding\ProviderChangeRequestLogic;
use app\adminapi\validate\wedding\ProviderChangeRequestValidate;
use think\response\Json;

class ProviderChangeRequestController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new ProviderChangeRequestLists());
    }

    public function detail(): Json
    {
        $params = (new ProviderChangeRequestValidate())->goCheck('detail');
        return $this->data(ProviderChangeRequestLogic::detail($params));
    }

    public function audit(): Json
    {
        $params = (new ProviderChangeRequestValidate())->post()->goCheck('audit');
        $result = ProviderChangeRequestLogic::audit($params, $this->adminId);
        if ($result === true) {
            return $this->success('审核成功', [], 1, 1);
        }

        return $this->fail(ProviderChangeRequestLogic::getError());
    }
}
