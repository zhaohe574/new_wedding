<?php

declare(strict_types=1);

namespace app\adminapi\logic\wedding;

use app\common\logic\BaseLogic;
use app\common\model\wedding\ProviderPostComment;
use app\common\service\ProviderPostService;

class ProviderPostCommentLogic extends BaseLogic
{
    public static function detail(array $params): array
    {
        $comment = ProviderPostComment::alias('comment')
            ->leftJoin('provider_post post', 'post.id = comment.post_id')
            ->leftJoin('service_provider provider', 'provider.id = comment.provider_id')
            ->leftJoin('user user', 'user.id = comment.user_id')
            ->where('comment.id', (int)$params['id'])
            ->whereNull('comment.delete_time')
            ->field([
                'comment.*',
                'post.title' => 'post_title',
                'provider.name' => 'provider_name',
                'user.nickname' => 'user_nickname',
            ])
            ->append(['audit_status_desc', 'audit_role_desc'])
            ->findOrEmpty();

        return $comment->isEmpty() ? [] : $comment->toArray();
    }

    public static function audit(array $params, int $adminId): bool
    {
        try {
            ProviderPostService::auditCommentByAdmin(
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
