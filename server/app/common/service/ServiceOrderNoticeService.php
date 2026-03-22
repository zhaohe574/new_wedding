<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\notice\NoticeEnum;
use app\common\enum\YesNoEnum;
use app\common\model\notice\NoticeRecord;
use app\common\model\notice\NoticeSetting;

class ServiceOrderNoticeService
{
    public static function sendByScene(int $sceneId, int $userId, array $params = [], array $extra = []): ?NoticeRecord
    {
        $noticeSetting = NoticeSetting::where('scene_id', $sceneId)->findOrEmpty();
        if ($noticeSetting->isEmpty()) {
            return null;
        }

        $config = ServiceBusinessConfigService::getConfig();
        $noticeData = $noticeSetting->toArray();
        $record = self::sendSystemNotice($sceneId, $userId, $params, $extra, $noticeData, $config);

        $targetRole = trim((string)($extra['target_role'] ?? ''));
        if ($targetRole === 'user') {
            ServiceMnpNoticeService::send($sceneId, $userId, $params, $extra);
        } elseif ($targetRole === 'provider') {
            ServiceWecomNoticeService::send($sceneId, $userId, $params, $extra);
        }

        return $record;
    }

    private static function sendSystemNotice(
        int $sceneId,
        int $userId,
        array $params,
        array $extra,
        array $noticeData,
        array $config
    ): ?NoticeRecord {
        if ((int)($config['notice']['system_notice_enabled'] ?? 0) !== 1) {
            return null;
        }

        $systemNotice = $noticeData['system_notice'] ?? [];
        if ((int)($systemNotice['status'] ?? 0) !== 1) {
            return null;
        }

        $title = self::formatTemplate((string)($systemNotice['title'] ?? ''), $params);
        $content = self::formatTemplate((string)($systemNotice['content'] ?? ''), $params);
        if ($title === '' && $content === '') {
            return null;
        }

        return NoticeRecord::create([
            'user_id' => $userId,
            'title' => $title,
            'content' => $content,
            'scene_id' => $sceneId,
            'read' => YesNoEnum::NO,
            'recipient' => (int)($noticeData['recipient'] ?? 1),
            'send_type' => NoticeEnum::SYSTEM,
            'notice_type' => NoticeEnum::BUSINESS_NOTIFICATION,
            'extra' => empty($extra) ? '' : json_encode($extra, JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
            'update_time' => time(),
        ]);
    }

    private static function formatTemplate(string $template, array $params): string
    {
        $content = trim($template);
        foreach ($params as $key => $value) {
            $content = str_replace('{' . $key . '}', (string)$value, $content);
        }
        return $content;
    }
}
