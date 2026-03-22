<?php

declare(strict_types=1);

namespace app\adminapi\controller\wedding;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\wedding\ProviderPostLists;
use app\adminapi\logic\wedding\ProviderPostLogic;
use app\adminapi\validate\wedding\ProviderPostValidate;
use think\response\Json;

class ProviderPostController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new ProviderPostLists());
    }

    public function detail(): Json
    {
        $params = (new ProviderPostValidate())->goCheck('detail');
        return $this->data(ProviderPostLogic::detail($params));
    }

    public function audit(): Json
    {
        $params = (new ProviderPostValidate())->post()->goCheck('audit');
        $result = ProviderPostLogic::audit($params, $this->adminId);
        if ($result === true) {
            return $this->success('审核成功', [], 1, 1);
        }

        return $this->fail(ProviderPostLogic::getError());
    }
}
