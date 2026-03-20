<?php

declare(strict_types=1);

namespace app\common\model\wedding;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class WeddingProfile extends BaseModel
{
    use SoftDelete;

    protected $name = 'wedding_profile';

    protected $deleteTime = 'delete_time';
}
