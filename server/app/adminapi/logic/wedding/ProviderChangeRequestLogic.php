<?php

declare(strict_types=1);

namespace app\adminapi\logic\wedding;

use app\common\logic\BaseLogic;
use app\common\service\ProviderProfileChangeService;

class ProviderChangeRequestLogic extends BaseLogic
{
    public static function detail(array $params): array
    {
        return ProviderProfileChangeService::getChangeRequestDetail((int)$params['id']);
    }

    public static function audit(array $params, int $adminId): bool
    {
        try {
            ProviderProfileChangeService::audit(
                (int)$params['id'],
                $adminId,
                (int)$params['audit_status'],
                trim((string)($params['audit_remark'] ?? ''))
            );
            return true;
        } catch (\Throwable $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
