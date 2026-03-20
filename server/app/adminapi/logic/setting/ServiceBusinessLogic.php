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

namespace app\adminapi\logic\setting;

use app\common\logic\BaseLogic;
use app\common\service\ServiceBusinessConfigService;

class ServiceBusinessLogic extends BaseLogic
{
    /**
     * @notes 获取婚庆业务配置
     * @return array
     */
    public static function getConfig(): array
    {
        return ServiceBusinessConfigService::getConfig();
    }

    /**
     * @notes 保存婚庆业务配置
     * @param array $params
     * @return array
     */
    public static function setConfig(array $params): array
    {
        return ServiceBusinessConfigService::setConfig($params);
    }
}
