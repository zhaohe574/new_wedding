<?php

declare(strict_types=1);

namespace app\adminapi\controller\wedding;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\wedding\WeddingDashboardLogic;
use think\response\Json;

class WeddingDashboardController extends BaseAdminController
{
    public function overview(): Json
    {
        return $this->data(WeddingDashboardLogic::overview());
    }
}
