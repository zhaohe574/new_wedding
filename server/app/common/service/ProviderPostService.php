<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\wedding\ServiceOrderReviewEnum;
use app\common\model\user\User;
use app\common\model\wedding\ProviderPost;
use app\common\model\wedding\ProviderPostComment;
use app\common\model\wedding\ServiceProvider;

class ProviderPostService
{
    public static function getProviderPostLists(int $userId, array $params = []): array
    {
        $provider = self::getProviderByUserId($userId);
        $pageNo = max(1, (int)($params['page_no'] ?? 1));
        $pageSize = min(20, max(1, (int)($params['page_size'] ?? 10)));
        $offset = ($pageNo - 1) * $pageSize;

        $query = ProviderPost::where(['provider_id' => (int)$provider['id']])
            ->whereNull('delete_time');

        if (($params['audit_status'] ?? '') !== '') {
            $query->where('audit_status', (int)$params['audit_status']);
        }

        $count = (clone $query)->count();
        $lists = $query->order(['id' => 'desc'])
            ->limit($offset, $pageSize)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['comment_count'] = ProviderPostComment::where(['post_id' => (int)$item['id']])
                ->whereNull('delete_time')
                ->count();
        }

        return [
            'lists' => $lists,
            'count' => $count,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ];
    }

    public static function savePost(int $userId, array $params): array
    {
        $provider = self::getProviderByUserId($userId);
        $config = ServiceBusinessConfigService::getConfig();
        $isCreate = (int)($params['id'] ?? 0) <= 0;
        if ($isCreate && (int)($config['interaction']['post_enabled'] ?? 0) !== 1) {
            throw new \RuntimeException('当前已关闭动态发布');
        }

        $existing = null;
        if (!$isCreate) {
            $existing = ProviderPost::where([
                'id' => (int)$params['id'],
                'provider_id' => (int)$provider['id'],
            ])->whereNull('delete_time')->findOrEmpty();
            if ($existing->isEmpty()) {
                throw new \RuntimeException('动态不存在');
            }
        }

        $saveData = self::buildPostSaveData((int)$provider['id'], $userId, $params, $config['review']['post_review_mode'] ?? 'admin');
        if ($isCreate) {
            $saveData['create_time'] = time();
            $post = ProviderPost::create($saveData);
        } else {
            $saveData['id'] = (int)$existing['id'];
            ProviderPost::update($saveData);
            $post = ProviderPost::find((int)$existing['id']);
        }

        return $post ? $post->toArray() : [];
    }

    public static function deletePost(int $userId, int $postId): bool
    {
        $provider = self::getProviderByUserId($userId);
        $post = ProviderPost::where([
            'id' => $postId,
            'provider_id' => (int)$provider['id'],
        ])->whereNull('delete_time')->findOrEmpty();

        if ($post->isEmpty()) {
            throw new \RuntimeException('动态不存在');
        }

        ProviderPost::destroy($postId);
        return true;
    }

    public static function getProviderPendingCommentLists(int $userId, array $params = []): array
    {
        $provider = self::getProviderByUserId($userId);
        $pageNo = max(1, (int)($params['page_no'] ?? 1));
        $pageSize = min(20, max(1, (int)($params['page_size'] ?? 10)));
        $offset = ($pageNo - 1) * $pageSize;

        $query = ProviderPostComment::alias('comment')
            ->leftJoin('provider_post post', 'post.id = comment.post_id')
            ->leftJoin('user user', 'user.id = comment.user_id')
            ->where('comment.provider_id', (int)$provider['id'])
            ->whereNull('comment.delete_time')
            ->field([
                'comment.id',
                'comment.post_id',
                'comment.content',
                'comment.audit_status',
                'comment.audit_role',
                'comment.create_time',
                'post.title' => 'post_title',
                'user.nickname' => 'user_nickname',
            ]);

        $onlyPending = (int)($params['only_pending'] ?? 1) === 1;
        if ($onlyPending) {
            $query->where('comment.audit_status', ServiceOrderReviewEnum::AUDIT_PENDING)
                ->where('comment.audit_role', ServiceOrderReviewEnum::AUDIT_ROLE_PROVIDER);
        }

        $count = (clone $query)->count();
        $lists = $query->order(['comment.id' => 'desc'])
            ->limit($offset, $pageSize)
            ->select()
            ->toArray();

        return [
            'lists' => $lists,
            'count' => $count,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ];
    }

    public static function auditCommentByProvider(int $userId, int $commentId, int $auditStatus, string $remark = ''): bool
    {
        if (!in_array($auditStatus, [
            ServiceOrderReviewEnum::AUDIT_APPROVED,
            ServiceOrderReviewEnum::AUDIT_REJECTED,
        ], true)) {
            throw new \RuntimeException('审核结果不正确');
        }

        $provider = self::getProviderByUserId($userId);
        $comment = ProviderPostComment::where([
            'id' => $commentId,
            'provider_id' => (int)$provider['id'],
        ])->whereNull('delete_time')->findOrEmpty();
        if ($comment->isEmpty()) {
            throw new \RuntimeException('评论不存在');
        }
        if ((int)$comment['audit_status'] !== ServiceOrderReviewEnum::AUDIT_PENDING ||
            (string)$comment['audit_role'] !== ServiceOrderReviewEnum::AUDIT_ROLE_PROVIDER) {
            throw new \RuntimeException('当前评论无需服务人员处理');
        }

        $mode = ServiceBusinessConfigService::getConfig()['review']['comment_review_mode'] ?? 'provider_then_admin';
        $saveData = [
            'id' => $commentId,
            'provider_audit_status' => $auditStatus,
            'provider_audit_by' => (int)$provider['id'],
            'provider_audit_time' => time(),
            'provider_audit_remark' => trim($remark),
            'update_time' => time(),
        ];

        if ($auditStatus === ServiceOrderReviewEnum::AUDIT_REJECTED) {
            $saveData['audit_status'] = ServiceOrderReviewEnum::AUDIT_REJECTED;
            $saveData['audit_role'] = ServiceOrderReviewEnum::AUDIT_ROLE_PROVIDER;
            $saveData['audit_by'] = (int)$provider['id'];
            $saveData['audit_time'] = time();
            $saveData['audit_remark'] = trim($remark);
        } elseif ($mode === 'provider_then_admin') {
            $saveData['audit_status'] = ServiceOrderReviewEnum::AUDIT_PENDING;
            $saveData['audit_role'] = ServiceOrderReviewEnum::AUDIT_ROLE_ADMIN;
            $saveData['audit_by'] = 0;
            $saveData['audit_time'] = 0;
            $saveData['audit_remark'] = '';
            $saveData['admin_audit_status'] = ServiceOrderReviewEnum::AUDIT_PENDING;
            $saveData['admin_audit_by'] = 0;
            $saveData['admin_audit_time'] = 0;
            $saveData['admin_audit_remark'] = '';
        } else {
            $saveData['audit_status'] = ServiceOrderReviewEnum::AUDIT_APPROVED;
            $saveData['audit_role'] = ServiceOrderReviewEnum::AUDIT_ROLE_PROVIDER;
            $saveData['audit_by'] = (int)$provider['id'];
            $saveData['audit_time'] = time();
            $saveData['audit_remark'] = trim($remark);
        }

        ProviderPostComment::update($saveData);
        return true;
    }

    public static function getPublicPostLists(int $providerId, array $params = []): array
    {
        $pageNo = max(1, (int)($params['page_no'] ?? 1));
        $pageSize = min(10, max(1, (int)($params['page_size'] ?? 10)));
        $offset = ($pageNo - 1) * $pageSize;

        $query = ProviderPost::where([
            'provider_id' => $providerId,
            'status' => 1,
            'audit_status' => ServiceOrderReviewEnum::AUDIT_APPROVED,
        ])->whereNull('delete_time');

        $count = (clone $query)->count();
        $lists = $query->order(['id' => 'desc'])
            ->limit($offset, $pageSize)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['comments'] = self::getApprovedCommentsByPostId((int)$item['id']);
        }

        return [
            'lists' => $lists,
            'count' => $count,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ];
    }

    public static function createComment(int $userId, int $postId, string $content, int $parentId = 0): array
    {
        $config = ServiceBusinessConfigService::getConfig();
        if ((int)($config['interaction']['comment_enabled'] ?? 0) !== 1) {
            throw new \RuntimeException('当前已关闭评论功能');
        }

        $post = ProviderPost::where([
            'id' => $postId,
            'status' => 1,
            'audit_status' => ServiceOrderReviewEnum::AUDIT_APPROVED,
        ])->whereNull('delete_time')->findOrEmpty();
        if ($post->isEmpty()) {
            throw new \RuntimeException('动态不存在或暂不可评论');
        }

        $content = trim($content);
        if ($content === '') {
            throw new \RuntimeException('请填写评论内容');
        }

        $saveData = self::buildCommentSaveData((int)$post['provider_id'], $userId, $content, $parentId, $config['review']['comment_review_mode'] ?? 'provider_then_admin');
        $saveData['post_id'] = $postId;
        $saveData['create_time'] = time();
        $saveData['update_time'] = time();

        $comment = ProviderPostComment::create($saveData);
        return $comment->toArray();
    }

    public static function getAdminPostLists(array $params = []): array
    {
        $pageNo = max(1, (int)($params['page_no'] ?? 1));
        $pageSize = min(20, max(1, (int)($params['page_size'] ?? 10)));
        $offset = ($pageNo - 1) * $pageSize;

        $query = ProviderPost::alias('post')
            ->leftJoin('service_provider provider', 'provider.id = post.provider_id')
            ->whereNull('post.delete_time')
            ->field([
                'post.*',
                'provider.name' => 'provider_name',
            ]);

        if (($params['audit_status'] ?? '') !== '') {
            $query->where('post.audit_status', (int)$params['audit_status']);
        }

        if (($params['only_pending'] ?? '') !== '') {
            if ((int)$params['only_pending'] === 1) {
                $query->where('post.audit_status', ServiceOrderReviewEnum::AUDIT_PENDING)
                    ->where('post.audit_role', ServiceOrderReviewEnum::AUDIT_ROLE_ADMIN);
            }
        }

        $count = (clone $query)->count();
        $lists = $query->order(['post.id' => 'desc'])
            ->limit($offset, $pageSize)
            ->select()
            ->toArray();

        return [
            'lists' => $lists,
            'count' => $count,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ];
    }

    public static function auditPostByAdmin(int $postId, int $auditStatus, int $adminId, string $remark = ''): bool
    {
        if (!in_array($auditStatus, [
            ServiceOrderReviewEnum::AUDIT_APPROVED,
            ServiceOrderReviewEnum::AUDIT_REJECTED,
        ], true)) {
            throw new \RuntimeException('审核结果不正确');
        }

        $post = ProviderPost::where(['id' => $postId])->whereNull('delete_time')->findOrEmpty();
        if ($post->isEmpty()) {
            throw new \RuntimeException('动态不存在');
        }
        if ((int)$post['audit_status'] !== ServiceOrderReviewEnum::AUDIT_PENDING ||
            (string)$post['audit_role'] !== ServiceOrderReviewEnum::AUDIT_ROLE_ADMIN) {
            throw new \RuntimeException('当前动态无需管理员处理');
        }

        ProviderPost::update([
            'id' => $postId,
            'audit_status' => $auditStatus,
            'audit_role' => ServiceOrderReviewEnum::AUDIT_ROLE_ADMIN,
            'audit_by' => $adminId,
            'audit_time' => time(),
            'audit_remark' => trim($remark),
            'admin_audit_status' => $auditStatus,
            'admin_audit_by' => $adminId,
            'admin_audit_time' => time(),
            'admin_audit_remark' => trim($remark),
            'update_time' => time(),
        ]);

        return true;
    }

    public static function getAdminCommentLists(array $params = []): array
    {
        $pageNo = max(1, (int)($params['page_no'] ?? 1));
        $pageSize = min(20, max(1, (int)($params['page_size'] ?? 10)));
        $offset = ($pageNo - 1) * $pageSize;

        $query = ProviderPostComment::alias('comment')
            ->leftJoin('provider_post post', 'post.id = comment.post_id')
            ->leftJoin('service_provider provider', 'provider.id = comment.provider_id')
            ->leftJoin('user user', 'user.id = comment.user_id')
            ->whereNull('comment.delete_time')
            ->field([
                'comment.*',
                'post.title' => 'post_title',
                'provider.name' => 'provider_name',
                'user.nickname' => 'user_nickname',
            ]);

        if (($params['only_pending'] ?? '') !== '') {
            if ((int)$params['only_pending'] === 1) {
                $query->where('comment.audit_status', ServiceOrderReviewEnum::AUDIT_PENDING)
                    ->where('comment.audit_role', ServiceOrderReviewEnum::AUDIT_ROLE_ADMIN);
            }
        }

        if (($params['audit_status'] ?? '') !== '') {
            $query->where('comment.audit_status', (int)$params['audit_status']);
        }

        $count = (clone $query)->count();
        $lists = $query->order(['comment.id' => 'desc'])
            ->limit($offset, $pageSize)
            ->select()
            ->toArray();

        return [
            'lists' => $lists,
            'count' => $count,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ];
    }

    public static function auditCommentByAdmin(int $commentId, int $auditStatus, int $adminId, string $remark = ''): bool
    {
        if (!in_array($auditStatus, [
            ServiceOrderReviewEnum::AUDIT_APPROVED,
            ServiceOrderReviewEnum::AUDIT_REJECTED,
        ], true)) {
            throw new \RuntimeException('审核结果不正确');
        }

        $comment = ProviderPostComment::where(['id' => $commentId])->whereNull('delete_time')->findOrEmpty();
        if ($comment->isEmpty()) {
            throw new \RuntimeException('评论不存在');
        }
        if ((int)$comment['audit_status'] !== ServiceOrderReviewEnum::AUDIT_PENDING ||
            (string)$comment['audit_role'] !== ServiceOrderReviewEnum::AUDIT_ROLE_ADMIN) {
            throw new \RuntimeException('当前评论无需管理员处理');
        }

        ProviderPostComment::update([
            'id' => $commentId,
            'audit_status' => $auditStatus,
            'audit_role' => ServiceOrderReviewEnum::AUDIT_ROLE_ADMIN,
            'audit_by' => $adminId,
            'audit_time' => time(),
            'audit_remark' => trim($remark),
            'admin_audit_status' => $auditStatus,
            'admin_audit_by' => $adminId,
            'admin_audit_time' => time(),
            'admin_audit_remark' => trim($remark),
            'update_time' => time(),
        ]);

        return true;
    }

    private static function buildPostSaveData(int $providerId, int $userId, array $params, string $reviewMode): array
    {
        $title = trim((string)($params['title'] ?? ''));
        if ($title === '') {
            throw new \RuntimeException('请填写动态标题');
        }

        $content = trim((string)($params['content'] ?? ''));
        if ($content === '') {
            throw new \RuntimeException('请填写动态内容');
        }

        $images = array_values(array_filter(array_map('strval', $params['images'] ?? [])));
        $state = self::buildPostAuditState($reviewMode);

        return array_merge([
            'provider_id' => $providerId,
            'user_id' => $userId,
            'title' => $title,
            'content' => $content,
            'cover' => trim((string)($params['cover'] ?? '')),
            'images' => $images,
            'status' => (int)($params['status'] ?? 1) === 1 ? 1 : 0,
            'update_time' => time(),
        ], $state);
    }

    private static function buildCommentSaveData(int $providerId, int $userId, string $content, int $parentId, string $reviewMode): array
    {
        $state = self::buildCommentAuditState($reviewMode);

        return array_merge([
            'provider_id' => $providerId,
            'user_id' => $userId,
            'parent_id' => max(0, $parentId),
            'content' => $content,
            'status' => 1,
        ], $state);
    }

    private static function buildPostAuditState(string $reviewMode): array
    {
        if ($reviewMode === 'provider') {
            return [
                'audit_status' => ServiceOrderReviewEnum::AUDIT_APPROVED,
                'audit_role' => ServiceOrderReviewEnum::AUDIT_ROLE_PROVIDER,
                'audit_by' => 0,
                'audit_time' => 0,
                'audit_remark' => '',
                'provider_audit_status' => ServiceOrderReviewEnum::AUDIT_APPROVED,
                'provider_audit_by' => 0,
                'provider_audit_time' => time(),
                'provider_audit_remark' => '发布即通过',
                'admin_audit_status' => 0,
                'admin_audit_by' => 0,
                'admin_audit_time' => 0,
                'admin_audit_remark' => '',
            ];
        }

        $providerApproved = $reviewMode === 'provider_then_admin'
            ? ServiceOrderReviewEnum::AUDIT_APPROVED
            : 0;

        return [
            'audit_status' => ServiceOrderReviewEnum::AUDIT_PENDING,
            'audit_role' => ServiceOrderReviewEnum::AUDIT_ROLE_ADMIN,
            'audit_by' => 0,
            'audit_time' => 0,
            'audit_remark' => '',
            'provider_audit_status' => $providerApproved,
            'provider_audit_by' => 0,
            'provider_audit_time' => $providerApproved === ServiceOrderReviewEnum::AUDIT_APPROVED ? time() : 0,
            'provider_audit_remark' => $providerApproved === ServiceOrderReviewEnum::AUDIT_APPROVED ? '发布人提交后自动通过服务人员初审' : '',
            'admin_audit_status' => ServiceOrderReviewEnum::AUDIT_PENDING,
            'admin_audit_by' => 0,
            'admin_audit_time' => 0,
            'admin_audit_remark' => '',
        ];
    }

    private static function buildCommentAuditState(string $reviewMode): array
    {
        if ($reviewMode === 'admin') {
            return [
                'audit_status' => ServiceOrderReviewEnum::AUDIT_PENDING,
                'audit_role' => ServiceOrderReviewEnum::AUDIT_ROLE_ADMIN,
                'audit_by' => 0,
                'audit_time' => 0,
                'audit_remark' => '',
                'provider_audit_status' => 0,
                'provider_audit_by' => 0,
                'provider_audit_time' => 0,
                'provider_audit_remark' => '',
                'admin_audit_status' => ServiceOrderReviewEnum::AUDIT_PENDING,
                'admin_audit_by' => 0,
                'admin_audit_time' => 0,
                'admin_audit_remark' => '',
            ];
        }

        return [
            'audit_status' => ServiceOrderReviewEnum::AUDIT_PENDING,
            'audit_role' => ServiceOrderReviewEnum::AUDIT_ROLE_PROVIDER,
            'audit_by' => 0,
            'audit_time' => 0,
            'audit_remark' => '',
            'provider_audit_status' => ServiceOrderReviewEnum::AUDIT_PENDING,
            'provider_audit_by' => 0,
            'provider_audit_time' => 0,
            'provider_audit_remark' => '',
            'admin_audit_status' => $reviewMode === 'provider_then_admin' ? ServiceOrderReviewEnum::AUDIT_PENDING : 0,
            'admin_audit_by' => 0,
            'admin_audit_time' => 0,
            'admin_audit_remark' => '',
        ];
    }

    private static function getApprovedCommentsByPostId(int $postId): array
    {
        $lists = ProviderPostComment::alias('comment')
            ->leftJoin('user user', 'user.id = comment.user_id')
            ->where('comment.post_id', $postId)
            ->where('comment.status', 1)
            ->where('comment.audit_status', ServiceOrderReviewEnum::AUDIT_APPROVED)
            ->whereNull('comment.delete_time')
            ->field([
                'comment.id',
                'comment.parent_id',
                'comment.content',
                'comment.create_time',
                'user.nickname' => 'user_nickname',
                'user.avatar' => 'user_avatar',
            ])
            ->order(['comment.id' => 'asc'])
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['user_avatar'] = trim((string)($item['user_avatar'] ?? ''));
        }

        return $lists;
    }

    private static function getProviderByUserId(int $userId): ServiceProvider
    {
        $provider = ServiceProvider::where([
            'user_id' => $userId,
            'status' => 1,
        ])->whereNull('delete_time')->findOrEmpty();
        if ($provider->isEmpty()) {
            throw new \RuntimeException('当前账号未绑定服务人员');
        }

        return $provider;
    }
}
