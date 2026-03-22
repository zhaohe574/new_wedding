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
        $config = ServiceBusinessConfigService::getConfig();
        if ((int)($config['notice']['system_notice_enabled'] ?? 0) !== 1) {
            return null;
        }

        $noticeSetting = NoticeSetting::where('scene_id', $sceneId)->findOrEmpty();
        if ($noticeSetting->isEmpty()) {
            return null;
        }

        $noticeData = $noticeSetting->toArray();
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
