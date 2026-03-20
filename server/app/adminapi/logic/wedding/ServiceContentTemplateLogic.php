<?php

declare(strict_types=1);

namespace app\adminapi\logic\wedding;

use app\common\logic\BaseLogic;
use app\common\model\wedding\ServiceContentTemplate;
use app\common\service\ServiceContentTemplateService;

class ServiceContentTemplateLogic extends BaseLogic
{
    public static function add(array $params): void
    {
        ServiceContentTemplateService::saveTemplate($params);
    }

    public static function edit(array $params): bool
    {
        try {
            ServiceContentTemplateService::saveTemplate($params, (int)$params['id']);
            return true;
        } catch (\Throwable $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    public static function delete(array $params): void
    {
        $id = (int)$params['id'];
        ServiceContentTemplateService::clearTemplateChildren($id);
        ServiceContentTemplate::destroy($id);
    }

    public static function detail(array $params): array
    {
        return ServiceContentTemplateService::getTemplateDetail((int)$params['id']);
    }

    public static function updateStatus(array $params): bool
    {
        ServiceContentTemplate::update([
            'id' => (int)$params['id'],
            'status' => (int)$params['status'],
            'update_time' => time(),
        ]);
        return true;
    }

    public static function getCategoryOptions(): array
    {
        return ServiceContentTemplateService::getCategoryOptions();
    }
}
