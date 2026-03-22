<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\model\notice\NoticeSetting;
use app\common\model\notice\NoticeWecomLog;
use app\common\model\wedding\ServiceProvider;
use app\common\service\wechat\WeChatConfigService;
use EasyWeChat\Work\Application;

class ServiceWecomNoticeService
{
    public static function send(int $sceneId, int $userId, array $params = [], array $extra = []): ?NoticeWecomLog
    {
        $noticeConfig = ServiceBusinessConfigService::getConfig()['notice'] ?? [];
        if ((int)($noticeConfig['work_wechat_notice_enabled'] ?? 0) !== 1) {
            return null;
        }

        $noticeSetting = NoticeSetting::where('scene_id', $sceneId)->findOrEmpty();
        if ($noticeSetting->isEmpty()) {
            return null;
        }

        $noticeData = $noticeSetting->toArray();
        $workNotice = $noticeData['work_notice'] ?? [];
        if ((int)($workNotice['status'] ?? 0) !== 1) {
            return null;
        }

        $provider = ServiceProvider::where(['user_id' => $userId])
            ->whereNull('delete_time')
            ->findOrEmpty();

        $receiverUserid = (string)($provider['work_wechat_userid'] ?? '');
        $payload = self::buildPayload($receiverUserid, $workNotice, $params, $noticeConfig, $extra);

        return self::dispatch(
            $sceneId,
            $noticeData,
            $userId,
            $extra,
            (int)($provider['id'] ?? 0),
            $receiverUserid,
            (string)($noticeConfig['work_wechat_agent_id'] ?? ''),
            $payload
        );
    }

    private static function buildPayload(
        string $receiverUserid,
        array $workNotice,
        array $params,
        array $noticeConfig,
        array $extra
    ): array {
        $messageType = trim((string)($workNotice['message_type'] ?? 'textcard'));
        $title = self::formatTemplate((string)($workNotice['title'] ?? ($workNotice['name'] ?? '业务通知')), $params);
        $description = self::formatTemplate((string)($workNotice['description'] ?? $workNotice['content'] ?? ''), $params);
        $url = trim((string)($workNotice['url'] ?? ''));

        if ($url === '' && preg_match('/^https?:\\/\\//', trim((string)($extra['path'] ?? ''))) === 1) {
            $url = trim((string)($extra['path'] ?? ''));
        }

        $payload = [
            'touser' => $receiverUserid,
            'msgtype' => $messageType,
            'agentid' => (int)($noticeConfig['work_wechat_agent_id'] ?? 0),
            'enable_id_trans' => 0,
            'enable_duplicate_check' => 0,
        ];

        if ($messageType === 'text') {
            $payload['text'] = [
                'content' => trim($description !== '' ? $description : $title),
            ];
            return $payload;
        }

        $payload['msgtype'] = 'textcard';
        $payload['textcard'] = [
            'title' => $title !== '' ? $title : '业务通知',
            'description' => $description,
            'url' => $url !== '' ? $url : 'https://work.weixin.qq.com/',
            'btntxt' => trim((string)($workNotice['button_text'] ?? '查看详情')),
        ];

        return $payload;
    }

    private static function dispatch(
        int $sceneId,
        array $noticeData,
        int $userId,
        array $extra,
        int $providerId,
        string $receiverUserid,
        string $agentId,
        array $payload
    ): NoticeWecomLog {
        $workConfig = WeChatConfigService::getWorkConfig();
        $responseData = [];
        $sendStatus = 0;
        $errorMessage = '';

        if ($receiverUserid === '') {
            $errorMessage = '服务人员未配置企业微信接收账号';
        } elseif (trim((string)($workConfig['corp_id'] ?? '')) === '' ||
            trim((string)($workConfig['secret'] ?? '')) === '' ||
            trim((string)($workConfig['agent_id'] ?? '')) === '') {
            $errorMessage = '企业微信全局配置不完整';
        } else {
            try {
                $app = new Application($workConfig);
                $response = $app->getClient()->postJson('cgi-bin/message/send', $payload);
                $responseData = method_exists($response, 'toArray') ? $response->toArray(false) : (array)$response;
                $sendStatus = (int)($responseData['errcode'] ?? -1) === 0 ? 1 : 0;
                if ($sendStatus !== 1) {
                    $errorMessage = trim((string)($responseData['errmsg'] ?? '企业微信消息发送失败'));
                }
            } catch (\Throwable $throwable) {
                $errorMessage = $throwable->getMessage();
            }
        }

        return NoticeWecomLog::create([
            'scene_id' => $sceneId,
            'scene_name' => (string)($noticeData['scene_name'] ?? ''),
            'order_id' => (int)($extra['order_id'] ?? 0),
            'user_id' => $userId,
            'provider_id' => $providerId > 0 ? $providerId : (int)($extra['provider_id'] ?? 0),
            'receiver_userid' => $receiverUserid,
            'agent_id' => $agentId,
            'send_status' => $sendStatus,
            'error_message' => trim($errorMessage),
            'request_data' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'response_data' => json_encode($responseData, JSON_UNESCAPED_UNICODE),
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
