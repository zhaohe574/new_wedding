<?php

declare(strict_types=1);

namespace app\adminapi\logic\wedding;

use app\common\logic\BaseLogic;
use app\common\model\wedding\WeddingProfile;
use app\common\service\WeddingProfileService;

class WeddingProfileLogic extends BaseLogic
{
    public static function detail(array $params): array
    {
        $profile = WeddingProfile::findOrEmpty((int)$params['id']);
        if ($profile->isEmpty()) {
            return [];
        }

        return WeddingProfileService::formatProfile($profile->toArray());
    }
}
