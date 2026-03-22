<?php

declare(strict_types=1);

namespace app\adminapi\controller\wedding;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\wedding\ProviderPostCommentLists;
use app\adminapi\logic\wedding\ProviderPostCommentLogic;
use app\adminapi\validate\wedding\ProviderPostCommentValidate;
use think\response\Json;

class ProviderPostCommentController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new ProviderPostCommentLists());
    }

    public function detail(): Json
    {
        $params = (new ProviderPostCommentValidate())->goCheck('detail');
        return $this->data(ProviderPostCommentLogic::detail($params));
    }

    public function audit(): Json
    {
        $params = (new ProviderPostCommentValidate())->post()->goCheck('audit');
        $result = ProviderPostCommentLogic::audit($params, $this->adminId);
        if ($result === true) {
            return $this->success('审核成功', [], 1, 1);
        }

        return $this->fail(ProviderPostCommentLogic::getError());
    }
}
