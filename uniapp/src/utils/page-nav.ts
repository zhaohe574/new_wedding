export const TABBAR_PAGE_PATHS = ['/pages/index/index', '/pages/news/news', '/pages/user/user']

export const PAGE_NAV_TITLE_MAP: Record<string, string> = {
    // '/pages/index/index': '首页',
    '/pages/index/index': '',
    '/pages/news/news': '动态',
    '/pages/user/user': '我的',
    '/pages/login/login': '登录',
    '/pages/register/register': '注册',
    '/pages/forget_pwd/forget_pwd': '忘记密码',
    '/pages/customer_service/customer_service': '联系客服',
    '/pages/news_detail/news_detail': '详情',
    '/pages/user_set/user_set': '个人设置',
    '/pages/collection/collection': '我的收藏',
    '/pages/as_us/as_us': '关于我们',
    '/pages/agreement/agreement': '协议',
    '/pages/change_password/change_password': '修改密码',
    '/pages/user_data/user_data': '个人资料',
    '/pages/search/search': '搜索',
    '/pages/webview/webview': '网页浏览',
    '/pages/empty/empty': '提示',
    '/pages/bind_mobile/bind_mobile': '绑定手机号',
    '/pages/payment_result/payment_result': '支付结果',
    '/pages/provider_center/provider_center': '服务人员中心',
    '/pages/provider_schedule/provider_schedule': '档期管理',
    '/pages/provider_profile_manage/provider_profile_manage': '资料中心',
    '/pages/provider_content_manage/provider_content_manage': '内容互动中心',
    '/pages/dashboard/dashboard': '经营驾驶舱',
    '/pages/wedding_profile/wedding_profile': '婚礼基础档案',
    '/pages/wedding_region/wedding_region': '地区与档期',
    '/pages/wedding_provider_list/wedding_provider_list': '服务人员列表',
    '/pages/wedding_provider_detail/wedding_provider_detail': '服务人员详情',
    '/pages/provider_public_posts/provider_public_posts': '服务动态',
    '/pages/wedding_template_form/wedding_template_form': '服务内容填写',
    '/pages/wedding_order_preview/wedding_order_preview': '订单预览',
    '/pages/wedding_order_list/wedding_order_list': '我的婚庆订单',
    '/pages/wedding_order_detail/wedding_order_detail': '婚庆订单详情',
    '/pages/wedding_order_review/wedding_order_review': '订单评价',
    '/pages/provider_order_pending/provider_order_pending': '订单工作台',
    '/pages/provider_order_detail/provider_order_detail': '服务订单详情',
    '/pages/notice_center/notice_center': '通知中心',
    '/packages/pages/404/404': '404',
    '/pages/404/404': '404',
    '/packages/pages/user_wallet/user_wallet': '我的钱包',
    '/pages/user_wallet/user_wallet': '我的钱包',
    '/packages/pages/recharge/recharge': '充值',
    '/pages/recharge/recharge': '充值',
    '/packages/pages/recharge_record/recharge_record': '充值记录',
    '/pages/recharge_record/recharge_record': '充值记录',
    '/uni_modules/vk-uview-ui/components/u-avatar-cropper/u-avatar-cropper': '头像裁剪'
}

export const normalizePagePath = (path = '') => {
    if (!path) {
        return ''
    }
    const normalized = path.startsWith('/') ? path : `/${path}`
    return normalized.replace(/\/{2,}/g, '/').replace(/\?.*$/, '')
}

export const resolvePageNavTitle = (path = '') => {
    return PAGE_NAV_TITLE_MAP[normalizePagePath(path)] || ''
}

export const isTabbarPagePath = (path = '') => {
    return TABBAR_PAGE_PATHS.includes(normalizePagePath(path))
}
