<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\notice\NoticeEnum;
use app\common\enum\user\UserTerminalEnum;
use app\common\model\notice\NoticeMnpLog;
use app\common\model\notice\NoticeSetting;
use app\common\model\user\UserAuth;
use app\common\service\wechat\WeChatMnpService;

class ServiceMnpNoticeService
{
    public static function send(int $sceneId, int $userId, array $params = [], array $extra = []): ?NoticeMnpLog
    {
        $noticeConfig = ServiceBusinessConfigService::getConfig()['notice'] ?? [];
        if ((int)($noticeConfig['mnp_notice_enabled'] ?? 0) !== 1) {
            return null;
        }

        $noticeSetting = NoticeSetting::where('scene_id', $sceneId)->findOrEmpty();
        if ($noticeSetting->isEmpty()) {
            return null;
        }

        $noticeData = $noticeSetting->toArray();
        $mnpNotice = $noticeData['mnp_notice'] ?? [];
        if ((int)($mnpNotice['status'] ?? 0) !== 1) {
            return null;
        }

        $openid = (string)UserAuth::where([
            'user_id' => $userId,
            'terminal' => UserTerminalEnum::WECHAT_MMP,
        ])->value('openid');

        $payload = self::buildPayload($openid, $mnpNotice, $params, $extra);

        return self::dispatch(
            $sceneId,
            $noticeData,
            $userId,
            $extra,
            $openid,
            $payload,
            (string)($mnpNotice['template_id'] ?? ''),
            (string)($mnpNotice['template_sn'] ?? '')
        );
    }

    private static function buildPayload(string $openid, array $mnpNotice, array $params, array $extra): array
    {
        $payload = [
            'touser' => $openid,
            'template_id' => trim((string)($mnpNotice['template_id'] ?? '')),
            'page' => trim((string)($mnpNotice['page'] ?? ($extra['path'] ?? ''))),
            'data' => self::buildTemplateData($mnpNotice['tpl'] ?? [], $params),
        ];

        if ($payload['page'] === '') {
            unset($payload['page']);
        }

        return $payload;
    }

    private static function dispatch(
        int $sceneId,
        array $noticeData,
        int $userId,
        array $extra,
        string $openid,
        array $payload,
        string $templateId,
        string $templateSn
    ): NoticeMnpLog {
        $errorMessage = '';
        $responseData = [];
        $sendStatus = 0;

        if ($openid === '') {
            $errorMessage = '缺少小程序 openid';
        } elseif ($templateId === '') {
            $errorMessage = '缺少订阅消息模板配置';
        } elseif (empty($payload['data'])) {
            $errorMessage = '缺少订阅消息字段映射';
        } else {
            try {
                $responseData = (new WeChatMnpService())->sendSubscribeMessage($payload);
                $sendStatus = (int)($responseData['errcode'] ?? -1) === 0 ? 1 : 0;
                if ($sendStatus !== 1) {
                    $errorMessage = trim((string)($responseData['errmsg'] ?? '订阅消息发送失败'));
                }
            } catch (\Throwable $throwable) {
                $errorMessage = $throwable->getMessage();
            }
        }

        return NoticeMnpLog::create([
            'scene_id' => $sceneId,
            'scene_name' => (string)($noticeData['scene_name'] ?? ''),
            'order_id' => (int)($extra['order_id'] ?? 0),
            'user_id' => $userId,
            'provider_id' => (int)($extra['provider_id'] ?? 0),
            'openid' => $openid,
            'template_id' => $templateId,
            'template_sn' => $templateSn,
            'send_status' => $sendStatus,
            'error_message' => trim($errorMessage),
            'request_data' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'response_data' => json_encode($responseData, JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
            'update_time' => time(),
        ]);
    }

    private static function buildTemplateData(array $tplConfig, array $params): array
    {
        $data = [];

        foreach ($tplConfig as $item) {
            if (!is_array($item)) {
                continue;
            }

            $key = trim((string)($item['key'] ?? $item['field'] ?? ''));
            $valueTemplate = (string)($item['value'] ?? $item['content'] ?? '');
            if ($key === '' || $valueTemplate === '') {
                continue;
            }

            $data[$key] = [
                'value' => self::formatTemplate($valueTemplate, $params),
            ];
        }

        return $data;
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
