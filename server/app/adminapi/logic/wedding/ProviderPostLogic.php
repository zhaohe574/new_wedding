<?php

declare(strict_types=1);

namespace app\adminapi\logic\wedding;

use app\common\logic\BaseLogic;
use app\common\model\wedding\ProviderPost;
use app\common\service\ProviderPostService;

class ProviderPostLogic extends BaseLogic
{
    public static function detail(array $params): array
    {
        $post = ProviderPost::alias('post')
            ->leftJoin('service_provider provider', 'provider.id = post.provider_id')
            ->where('post.id', (int)$params['id'])
            ->whereNull('post.delete_time')
            ->field([
                'post.*',
                'provider.name' => 'provider_name',
            ])
            ->append(['audit_status_desc', 'audit_role_desc'])
            ->findOrEmpty();

        return $post->isEmpty() ? [] : $post->toArray();
    }

    public static function audit(array $params, int $adminId): bool
    {
        try {
            ProviderPostService::auditPostByAdmin(
                (int)$params['id'],
                (int)$params['audit_status'],
                $adminId,
                trim((string)($params['audit_remark'] ?? ''))
            );
            return true;
        } catch (\Throwable $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
