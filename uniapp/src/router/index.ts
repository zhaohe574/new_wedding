import routes from 'uni-router-routes'
import { createRouter } from 'uniapp-router-next'

import { ClientEnum } from '@/enums/appEnums'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { client } from '@/utils/client'
// #ifdef H5
import wechatOa from '@/utils/wechat'
// #endif
import cache from '@/utils/cache'
import { BACK_URL } from '@/enums/constantEnums'
import { hasWeddingTradeSelection } from '@/utils/wedding'

const router = createRouter({
    routes: [
        ...routes,
        {
            path: '*',
            redirect() {
                return {
                    name: '404'
                }
            }
        }
    ],
    debug: import.meta.env.DEV,
    //@ts-ignore
    platform: process.env.UNI_PLATFORM,
    h5: {}
})

//存储登陆前的页面
let isFirstEach = true
router.beforeEach(async (to, from) => {
    //保存第一次进来时的页面路径（需要登陆才能访问的页面）
    if (isFirstEach) {
        const userStore = useUserStore()
        if (!userStore.isLogin && !to.meta.white) {
            cache.set(BACK_URL, to.fullPath)
        }
        isFirstEach = false
    }
})
router.afterEach((to, from) => {
    const userStore = useUserStore()
    if (!userStore.isLogin && !to.meta.white) {
        cache.set(BACK_URL, to.fullPath)
    }
})

// 登录拦截
router.beforeEach(async (to, from) => {
    const userStore = useUserStore();
    if (!userStore.isLogin && to.meta.auth) {
        return '/pages/login/login'
    }
})

router.beforeEach(async (to) => {
    const appStore = useAppStore()
    const userStore = useUserStore()
    if (!userStore.isLogin) {
        return
    }

    const path = to.path || ''
    const needProviderCenter = path.includes('/pages/provider_center/provider_center')
    const needDashboard = path.includes('/pages/dashboard/dashboard')
    const needWeddingProfile = path.includes('/pages/wedding_profile/wedding_profile')

    if (!needProviderCenter && !needDashboard && !needWeddingProfile) {
        return
    }

    if (!Object.keys(appStore.getServiceBusinessConfig || {}).length) {
        await appStore.getConfig()
    }

    const displayConfig = appStore.getServiceBusinessConfig?.display || {}
    const providerCenterEnabled = Number(displayConfig.provider_center_enabled ?? 0) === 1
    const dashboardEnabled = Number(displayConfig.dashboard_enabled ?? 0) === 1

    if (needProviderCenter && !providerCenterEnabled) {
        uni.showToast({ title: '服务人员中心当前已关闭', icon: 'none' })
        return '/pages/user/user'
    }

    if (needDashboard && !dashboardEnabled) {
        uni.showToast({ title: '经营驾驶舱当前已关闭', icon: 'none' })
        return '/pages/user/user'
    }

    if (needWeddingProfile && Number(displayConfig.wedding_profile_enabled ?? 0) !== 1) {
        uni.showToast({ title: '婚礼基础档案当前已关闭', icon: 'none' })
        return '/pages/user/user'
    }

    if (!userStore.userInfo?.id) {
        await userStore.getUser()
    }

    if (needProviderCenter && !userStore.userInfo?.can_enter_provider_center) {
        uni.showToast({ title: '当前账号未开通服务人员中心', icon: 'none' })
        return '/pages/user/user'
    }

    if (needDashboard && !userStore.userInfo?.can_view_dashboard) {
        uni.showToast({ title: '当前账号无驾驶舱查看权限', icon: 'none' })
        return '/pages/user/user'
    }
})

router.beforeEach(async (to) => {
    const path = to.path || ''
    const needTradeSelection = [
        '/pages/wedding_provider_list/wedding_provider_list',
        '/pages/wedding_provider_detail/wedding_provider_detail',
        '/pages/wedding_template_form/wedding_template_form',
        '/pages/wedding_order_preview/wedding_order_preview'
    ].some((item) => path.includes(item))

    if (!needTradeSelection) {
        return
    }

    if (!hasWeddingTradeSelection()) {
        uni.showToast({ title: '请先选择地区与服务日期', icon: 'none' })
        return '/pages/wedding_region/wedding_region'
    }
})

// #ifdef H5
//用于收集微信公众号的授权的code，并清除路径上微信带的参数
router.beforeEach(async (to, form) => {
    const { code, state, scene } = to.query

    if (code && state && scene) {
        wechatOa.setAuthData({
            code,
            scene
        })
        //收集完删除路径上的参数
        delete to.query.code
        delete to.query.state
        return {
            path: to.path,
            force: true,
            navType: 'reLaunch',
            query: to.query
        }
    }
})
// #endif

// #ifdef H5
router.afterEach((to, from) => {
    setTimeout(async () => {
        if (client == ClientEnum.OA_WEIXIN && !to.meta.webview) {
            // jssdk配置
            await wechatOa.config()
        }
    })
})
// #endif
export default router
