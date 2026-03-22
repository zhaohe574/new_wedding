<?php

declare(strict_types=1);

namespace app\adminapi\logic\wedding;

use app\common\logic\BaseLogic;
use app\common\service\WeddingWorkbenchStatService;

class WeddingDashboardLogic extends BaseLogic
{
    public static function overview(): array
    {
        return WeddingWorkbenchStatService::getOverview();
    }
}
