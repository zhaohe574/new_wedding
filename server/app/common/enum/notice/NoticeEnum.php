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
namespace app\common\enum\notice;

/**
 * 通知枚举
 * Class NoticeEnum
 * @package app\common\enum
 */
class NoticeEnum
{
    /**
     * 通知类型
     */
    const SYSTEM = 1;
    const SMS = 2;
    const OA = 3;
    const MNP = 4;
    const WORK = 5;


    /**
     * 短信验证码场景
     */
    const LOGIN_CAPTCHA = 101;
    const BIND_MOBILE_CAPTCHA = 102;
    const CHANGE_MOBILE_CAPTCHA = 103;
    const FIND_LOGIN_PASSWORD_CAPTCHA = 104;


    /**
     * 验证码场景
     */
    const SMS_SCENE = [
        self::LOGIN_CAPTCHA,
        self::BIND_MOBILE_CAPTCHA,
        self::CHANGE_MOBILE_CAPTCHA,
        self::FIND_LOGIN_PASSWORD_CAPTCHA,
    ];


    //通知类型
    const BUSINESS_NOTIFICATION = 1;//业务通知
    const VERIFICATION_CODE = 2;//验证码


    /**
     * @notes 通知类型
     * @param bool $value
     * @return string|string[]
     * @author ljj
     * @date 2022/2/17 2:49 下午
     */
    public static function getTypeDesc($value = true)
    {
        $data = [
            self::BUSINESS_NOTIFICATION => '业务通知',
            self::VERIFICATION_CODE => '验证码'
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value];
    }


    /**
     * @notes 获取场景描述
     * @param $sceneId
     * @param false $flag
     * @return string|string[]
     * @author 段誉
     * @date 2022/3/29 11:33
     */
    public static function getSceneDesc($sceneId, $flag = false)
    {
        $desc = [
            self::LOGIN_CAPTCHA => '登录验证码',
            self::BIND_MOBILE_CAPTCHA => '绑定手机验证码',
            self::CHANGE_MOBILE_CAPTCHA => '变更手机验证码',
            self::FIND_LOGIN_PASSWORD_CAPTCHA => '找回登录密码验证码',
            201 => '婚庆下单成功',
            202 => '婚庆待支付提醒',
            203 => '婚庆接单成功',
            204 => '婚庆订单拒单',
            205 => '婚庆支付成功',
            206 => '婚庆线下凭证已提交',
            207 => '婚庆线下凭证审核通过',
            208 => '婚庆线下凭证审核驳回',
            209 => '婚庆改期申请提交',
            210 => '婚庆改期处理结果',
            211 => '婚庆待评价提醒',
            212 => '婚庆评价已提交',
            213 => '婚庆评价审核结果',
            214 => '婚庆退款申请已提交',
            215 => '婚庆退款处理结果',
        ];

        if ($flag) {
            return $desc;
        }

        return $desc[$sceneId] ?? '';
    }


    /**
     * @notes 更具标记获取场景
     * @param $tag
     * @return int|string
     * @author 段誉
     * @date 2022/9/15 15:08
     */
    public static function getSceneByTag($tag)
    {
        $scene = [
            // 手机验证码登录
            'YZMDL' => self::LOGIN_CAPTCHA,
            // 绑定手机号验证码
            'BDSJHM' => self::BIND_MOBILE_CAPTCHA,
            // 变更手机号验证码
            'BGSJHM' => self::CHANGE_MOBILE_CAPTCHA,
            // 找回登录密码
            'ZHDLMM' => self::FIND_LOGIN_PASSWORD_CAPTCHA,
        ];
        return $scene[$tag] ?? '';
    }


    /**
     * @notes 获取场景变量
     * @param $sceneId
     * @param false $flag
     * @return array|string[]
     * @author 段誉
     * @date 2022/3/29 11:33
     */
    public static function getVars($sceneId, $flag = false)
    {
        $desc = [
            self::LOGIN_CAPTCHA => '验证码:code',
            self::BIND_MOBILE_CAPTCHA => '验证码:code',
            self::CHANGE_MOBILE_CAPTCHA => '验证码:code',
            self::FIND_LOGIN_PASSWORD_CAPTCHA => '验证码:code',
            201 => '订单号:order_sn，服务人员:provider_name，套餐:package_name，服务日期:service_date，订单金额:order_amount，订单状态:order_status_desc',
            202 => '订单号:order_sn，服务日期:service_date，订单金额:order_amount',
            203 => '订单号:order_sn，服务人员:provider_name，服务日期:service_date',
            204 => '订单号:order_sn，服务人员:provider_name',
            205 => '订单号:order_sn，订单金额:order_amount，服务日期:service_date',
            206 => '订单号:order_sn，服务日期:service_date',
            207 => '订单号:order_sn，服务日期:service_date',
            208 => '订单号:order_sn',
            209 => '订单号:order_sn，服务日期:service_date',
            210 => '订单号:order_sn，改期结果:reschedule_result',
            211 => '订单号:order_sn，服务人员:provider_name',
            212 => '订单号:order_sn，服务日期:service_date',
            213 => '订单号:order_sn，审核结果:review_result',
            214 => '订单号:order_sn，退款金额:refund_amount',
            215 => '订单号:order_sn，退款结果:refund_result',
        ];

        if ($flag) {
            return $desc;
        }

        return isset($desc[$sceneId]) ? ['可选变量 ' . $desc[$sceneId]] : [];
    }


    /**
     * @notes 获取系统通知示例
     * @param $sceneId
     * @param false $flag
     * @return array|string[]
     * @author 段誉
     * @date 2022/3/29 11:33
     */
    public static function getSystemExample($sceneId, $flag = false)
    {
        $desc = [];

        if ($flag) {
            return $desc;
        }

        return isset($desc[$sceneId]) ? [$desc[$sceneId]] : [];
    }


    /**
     * @notes 获取短信通知示例
     * @param $sceneId
     * @param false $flag
     * @return array|string[]
     * @author 段誉
     * @date 2022/3/29 11:33
     */
    public static function getSmsExample($sceneId, $flag = false)
    {
        $desc = [
            self::LOGIN_CAPTCHA => '您正在登录，验证码${code}，切勿将验证码泄露于他人，本条验证码有效期5分钟。',
            self::BIND_MOBILE_CAPTCHA => '您正在绑定手机号，验证码${code}，切勿将验证码泄露于他人，本条验证码有效期5分钟。',
            self::CHANGE_MOBILE_CAPTCHA => '您正在变更手机号，验证码${code}，切勿将验证码泄露于他人，本条验证码有效期5分钟。',
            self::FIND_LOGIN_PASSWORD_CAPTCHA => '您正在找回登录密码，验证码${code}，切勿将验证码泄露于他人，本条验证码有效期5分钟。',
        ];

        if ($flag) {
            return $desc;
        }

        return isset($desc[$sceneId]) ? ['示例：' . $desc[$sceneId]] : [];
    }


    /**
     * @notes 获取公众号模板消息示例
     * @param $sceneId
     * @param false $flag
     * @return array|string[]|\string[][]
     * @author 段誉
     * @date 2022/3/29 11:33
     */
    public static function getOaExample($sceneId, $flag = false)
    {
        $desc = [];

        if ($flag) {
            return $desc;
        }

        return $desc[$sceneId] ?? [];
    }


    /**
     * @notes 获取小程序订阅消息示例
     * @param $sceneId
     * @param false $flag
     * @return array|mixed
     * @author 段誉
     * @date 2022/3/29 11:33
     */
    public static function getMnpExample($sceneId, $flag = false)
    {
        $desc = [
            201 => [
                '建议模板：订单提交成功通知',
                '字段映射示例：[{"key":"thing1","value":"订单 {order_sn}"},{"key":"name2","value":"{provider_name}"},{"key":"date3","value":"{service_date}"}]',
                '默认跳转：/pages/wedding_order_detail/wedding_order_detail?order_id={order_id}',
            ],
            202 => [
                '建议模板：待支付提醒',
                '字段映射示例：[{"key":"thing1","value":"订单 {order_sn}"},{"key":"amount2","value":"￥{order_amount}"},{"key":"date3","value":"{service_date}"}]',
                '默认跳转：/pages/wedding_order_detail/wedding_order_detail?order_id={order_id}',
            ],
            203 => [
                '建议模板：服务人员接单通知',
                '字段映射示例：[{"key":"thing1","value":"订单 {order_sn}"},{"key":"name2","value":"{provider_name}"},{"key":"date3","value":"{service_date}"}]',
                '默认跳转：/pages/wedding_order_detail/wedding_order_detail?order_id={order_id}',
            ],
            204 => [
                '建议模板：订单拒单通知',
                '字段映射示例：[{"key":"thing1","value":"订单 {order_sn}"},{"key":"thing2","value":"{provider_name} 已拒单"}]',
                '默认跳转：/pages/wedding_order_detail/wedding_order_detail?order_id={order_id}',
            ],
            205 => [
                '建议模板：支付成功通知',
                '字段映射示例：[{"key":"thing1","value":"订单 {order_sn}"},{"key":"amount2","value":"￥{order_amount}"},{"key":"date3","value":"{service_date}"}]',
                '默认跳转：/pages/wedding_order_detail/wedding_order_detail?order_id={order_id}',
            ],
            207 => [
                '建议模板：线下凭证审核通过通知',
                '字段映射示例：[{"key":"thing1","value":"订单 {order_sn}"},{"key":"thing2","value":"线下凭证审核通过"}]',
                '默认跳转：/pages/wedding_order_detail/wedding_order_detail?order_id={order_id}',
            ],
            208 => [
                '建议模板：线下凭证审核驳回通知',
                '字段映射示例：[{"key":"thing1","value":"订单 {order_sn}"},{"key":"thing2","value":"线下凭证审核未通过"}]',
                '默认跳转：/pages/wedding_order_detail/wedding_order_detail?order_id={order_id}',
            ],
            210 => [
                '建议模板：改期处理结果通知',
                '字段映射示例：[{"key":"thing1","value":"订单 {order_sn}"},{"key":"thing2","value":"{reschedule_result}"}]',
                '默认跳转：/pages/wedding_order_detail/wedding_order_detail?order_id={order_id}',
            ],
            211 => [
                '建议模板：待评价提醒',
                '字段映射示例：[{"key":"thing1","value":"订单 {order_sn}"},{"key":"name2","value":"{provider_name}"}]',
                '默认跳转：/pages/wedding_order_detail/wedding_order_detail?order_id={order_id}',
            ],
            213 => [
                '建议模板：评价审核结果通知',
                '字段映射示例：[{"key":"thing1","value":"订单 {order_sn}"},{"key":"thing2","value":"{review_result}"}]',
                '默认跳转：/pages/wedding_order_detail/wedding_order_detail?order_id={order_id}',
            ],
            214 => [
                '建议模板：退款申请提交成功通知',
                '字段映射示例：[{"key":"thing1","value":"订单 {order_sn}"},{"key":"amount2","value":"￥{refund_amount}"}]',
                '默认跳转：/pages/wedding_order_detail/wedding_order_detail?order_id={order_id}',
            ],
            215 => [
                '建议模板：退款处理结果通知',
                '字段映射示例：[{"key":"thing1","value":"订单 {order_sn}"},{"key":"thing2","value":"{refund_result}"}]',
                '默认跳转：/pages/wedding_order_detail/wedding_order_detail?order_id={order_id}',
            ],
        ];

        if ($flag) {
            return $desc;
        }

        return $desc[$sceneId] ?? [];
    }

    /**
     * @notes 获取企业微信应用消息示例
     * @param $sceneId
     * @param false $flag
     * @return array|mixed
     */
    public static function getWorkExample($sceneId, $flag = false)
    {
        $desc = [
            201 => [
                '消息标题示例：收到新婚庆订单',
                '消息描述示例：订单 {order_sn} 已提交，请尽快确认是否接单。服务日期：{service_date}。',
                '默认跳转：服务人员订单详情页或企业微信 H5 订单详情链接',
            ],
            205 => [
                '消息标题示例：订单已支付',
                '消息描述示例：订单 {order_sn} 已支付成功，请按服务日期推进履约安排。',
                '默认跳转：服务人员订单详情页或企业微信 H5 订单详情链接',
            ],
            206 => [
                '消息标题示例：收到线下支付凭证',
                '消息描述示例：订单 {order_sn} 已提交线下支付凭证，请尽快核验处理。',
                '默认跳转：服务人员订单详情页或企业微信 H5 订单详情链接',
            ],
            209 => [
                '消息标题示例：收到改期申请',
                '消息描述示例：订单 {order_sn} 已提交改期申请，请确认新的服务日期。',
                '默认跳转：服务人员订单详情页或企业微信 H5 订单详情链接',
            ],
            212 => [
                '消息标题示例：收到订单评价',
                '消息描述示例：订单 {order_sn} 已收到用户评价，请按审核规则及时处理。',
                '默认跳转：服务人员订单详情页或企业微信 H5 订单详情链接',
            ],
        ];

        if ($flag) {
            return $desc;
        }

        return $desc[$sceneId] ?? [];
    }


    /**
     * @notes 提示
     * @param $type
     * @param $sceneId
     * @return array|string|string[]|\string[][]
     * @author 段誉
     * @date 2022/3/29 11:33
     */
    public static function getOperationTips($type, $sceneId)
    {
        // 场景变量
        $vars = self::getVars($sceneId);
        // 其他提示
        $other = [];
        // 示例
        switch ($type) {
            case self::SYSTEM:
                $example = self::getSystemExample($sceneId);
                break;
            case self::SMS:
                $other[] = '生效条件：1、管理后台完成短信设置。 2、第三方短信平台申请模板。';
                $example = self::getSmsExample($sceneId);
                break;
            case self::OA:
                $other[] = '配置路径：公众号后台 > 广告与服务 > 模板消息';
                $other[] = '推荐行业：主营行业：IT科技/互联网|电子商务 副营行业：消费品/消费品';
                $example = self::getOaExample($sceneId);
                break;
            case self::MNP:
                $other[] = '配置路径：小程序后台 > 功能 > 订阅消息';
                $example = self::getMnpExample($sceneId);
                break;
            case self::WORK:
                $other[] = '配置路径：企业微信后台 > 应用管理 > 自建应用';
                $other[] = '接收账号取自服务人员主档 work_wechat_userid 字段';
                $example = self::getWorkExample($sceneId);
                break;
        }
        $tips = array_merge($vars, $example, $other);

        return $tips;
    }
}
