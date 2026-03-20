<?php

declare(strict_types=1);

namespace app\common\model\wedding;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class ServiceContentTemplatePage extends BaseModel
{
    use SoftDelete;

    protected $name = 'service_content_template_page';

    protected $deleteTime = 'delete_time';
}
