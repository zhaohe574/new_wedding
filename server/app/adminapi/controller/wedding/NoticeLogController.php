<?php

declare(strict_types=1);

namespace app\adminapi\controller\wedding;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\wedding\NoticeMnpLogLists;
use app\adminapi\lists\wedding\NoticeWecomLogLists;
use think\response\Json;

class NoticeLogController extends BaseAdminController
{
    public function mnpLists(): Json
    {
        return $this->dataLists(new NoticeMnpLogLists());
    }

    public function wecomLists(): Json
    {
        return $this->dataLists(new NoticeWecomLogLists());
    }
}
