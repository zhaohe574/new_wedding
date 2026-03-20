<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | gitee下载：https://gitee.com/likeshop_gitee/likeadmin
// | github下载：https://github.com/likeshop-github/likeadmin
// | 访问官网：https://www.likeadmin.cn
// | likeadmin团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeadminTeam
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\wedding\ServiceProvider;

class ServiceBusinessConfigService
{
    /**
     * @notes 获取默认配置
     * @return array
     */
    public static function getDefaultConfig(): array
    {
        return config('project.service_business', []);
    }

    /**
     * @notes 获取合并后的业务配置
     * @return array
     */
    public static function getConfig(): array
    {
        $defaultConfig = self::getDefaultConfig();

        return self::normalizeConfig([
            'trade' => ConfigService::get('service_business', 'trade', $defaultConfig['trade'] ?? []),
            'review' => ConfigService::get('service_business', 'review', $defaultConfig['review'] ?? []),
            'interaction' => ConfigService::get('service_business', 'interaction', $defaultConfig['interaction'] ?? []),
            'notice' => ConfigService::get('service_business', 'notice', $defaultConfig['notice'] ?? []),
            'display' => ConfigService::get('service_business', 'display', $defaultConfig['display'] ?? []),
            // 旧版 JSON 绑定名单仅做读取兼容，正式权限口径已切换为 service_provider.user_id。
            'provider_users' => ConfigService::get('service_business', 'provider_users', []),
            'dashboard_view_users' => ConfigService::get('service_business', 'dashboard_view_users', $defaultConfig['dashboard_view_users'] ?? []),
        ]);
    }

    /**
     * @notes 保存业务配置
     * @param array $config
     * @return array
     */
    public static function setConfig(array $config): array
    {
        if (!array_key_exists('provider_users', $config)) {
            $config['provider_users'] = ConfigService::get('service_business', 'provider_users', []);
        }

        $config = self::normalizeConfig($config);
        ConfigService::set('service_business', 'trade', $config['trade']);
        ConfigService::set('service_business', 'review', $config['review']);
        ConfigService::set('service_business', 'interaction', $config['interaction']);
        ConfigService::set('service_business', 'notice', $config['notice']);
        ConfigService::set('service_business', 'display', $config['display']);
        ConfigService::set('service_business', 'provider_users', $config['provider_users']);
        ConfigService::set('service_business', 'dashboard_view_users', $config['dashboard_view_users']);
        return $config;
    }

    /**
     * @notes 提供给前端的业务配置
     * @return array
     */
    public static function getFrontendConfig(): array
    {
        $config = self::getConfig();
        return [
            'trade' => $config['trade'],
            'review' => $config['review'],
            'interaction' => $config['interaction'],
            'notice' => $config['notice'],
            'display' => $config['display'],
        ];
    }

    /**
     * @notes 获取用户能力标识
     * @param int $userId
     * @return array
     */
    public static function getUserAbility(int $userId): array
    {
        $config = self::getConfig();
        $provider = ServiceProvider::where([
            'user_id' => $userId,
            'status' => 1,
        ])->whereNull('delete_time')->findOrEmpty();
        $dashboardUsers = array_map('intval', $config['dashboard_view_users'] ?? []);
        $providerCenterEnabled = (int)($config['display']['provider_center_enabled'] ?? 0) === 1;
        $dashboardEnabled = (int)($config['display']['dashboard_enabled'] ?? 0) === 1;

        return [
            'can_enter_provider_center' => $providerCenterEnabled && !$provider->isEmpty(),
            'provider_id' => (int)($provider['id'] ?? 0),
            'can_view_dashboard' => $dashboardEnabled && in_array($userId, $dashboardUsers, true),
            'dashboard_enabled' => $dashboardEnabled ? 1 : 0,
        ];
    }

    /**
     * @notes 规范化配置数据
     * @param array $config
     * @return array
     */
    public static function normalizeConfig(array $config): array
    {
        $default = self::getDefaultConfig();
        $config = array_replace_recursive($default, $config);
        $config['provider_users'] = $config['provider_users'] ?? [];

        foreach (['trade', 'interaction', 'notice', 'display'] as $groupName) {
            foreach ($config[$groupName] as $key => $value) {
                if (str_ends_with($key, '_enabled')) {
                    $config[$groupName][$key] = (int)!empty($value);
                }
            }
        }

        $config['trade']['provider_confirm_timeout_minutes'] = max(1, (int)($config['trade']['provider_confirm_timeout_minutes'] ?? 30));
        $config['trade']['pay_timeout_minutes'] = max(1, (int)($config['trade']['pay_timeout_minutes'] ?? 30));

        $config['provider_users'] = array_values(array_filter(array_map(function ($item) {
            return [
                'user_id' => (int)($item['user_id'] ?? 0),
                'provider_id' => (int)($item['provider_id'] ?? 0),
                'name' => trim((string)($item['name'] ?? '')),
            ];
        }, $config['provider_users'] ?? []), function ($item) {
            return $item['user_id'] > 0;
        }));

        $config['dashboard_view_users'] = array_values(array_filter(array_map('intval', $config['dashboard_view_users'] ?? [])));

        return $config;
    }
}
