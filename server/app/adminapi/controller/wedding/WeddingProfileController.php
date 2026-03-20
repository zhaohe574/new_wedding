<?php

declare(strict_types=1);

namespace app\adminapi\controller\wedding;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\wedding\WeddingProfileLists;
use app\adminapi\logic\wedding\WeddingProfileLogic;
use app\adminapi\validate\wedding\WeddingProfileValidate;
use think\response\Json;

class WeddingProfileController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new WeddingProfileLists());
    }

    public function detail(): Json
    {
        $params = (new WeddingProfileValidate())->goCheck('detail');
        return $this->data(WeddingProfileLogic::detail($params));
    }
}
