<template>
    <u-tabbar
        v-if="showTabbar"
        v-model="current"
        v-bind="tabbarStyle"
        :list="tabbarList"
        @change="handleChange"
        :hide-tab-bar="true"
    ></u-tabbar>
</template>

<script lang="ts" setup>
import { useAppStore } from '@/stores/app'
import { navigateTo } from '@/utils/util'
import { computed, ref, watchEffect } from 'vue'
const current = ref(0)
const appStore = useAppStore()

const defaultTabbarList = [
    {
        iconPath: '/static/images/tabbar/home.png',
        selectedIconPath: '/static/images/tabbar/home_s.png',
        text: '首页',
        link: {
            path: '/pages/index/index',
            name: '首页',
            type: 'shop'
        },
        pagePath: '/pages/index/index'
    },
    {
        iconPath: '/static/images/tabbar/news.png',
        selectedIconPath: '/static/images/tabbar/news_s.png',
        text: '动态',
        link: {
            path: '/pages/news/news',
            name: '动态',
            type: 'shop'
        },
        pagePath: '/pages/news/news'
    },
    {
        iconPath: '/static/images/tabbar/user.png',
        selectedIconPath: '/static/images/tabbar/user_s.png',
        text: '我的',
        link: {
            path: '/pages/user/user',
            name: '我的',
            type: 'shop'
        },
        pagePath: '/pages/user/user'
    }
]

const tabbarList = computed(() => {
    const remoteTabbarList = appStore.getTabbarConfig
        ?.filter((item: any) => item.is_show == 1)
        .map((item: any) => {
            const path = item?.link?.path || ''
            const fallback = defaultTabbarList.find((tab) => tab.pagePath === path)
            return {
                iconPath: item.unselected || fallback?.iconPath,
                selectedIconPath: item.selected || fallback?.selectedIconPath,
                text: item.name || fallback?.text,
                link: item.link || fallback?.link,
                pagePath: path || fallback?.pagePath
            }
        })

    if (remoteTabbarList?.length) {
        return remoteTabbarList
    }

    return defaultTabbarList
})

const getCurrentTabIndex = () => {
    const currentPages = getCurrentPages()
    const currentPage = currentPages[currentPages.length - 1]
    if (!currentPage) {
        return -1
    }

    return tabbarList.value.findIndex((item: any) => item.pagePath === '/' + currentPage.route)
}

watchEffect(() => {
    const index = getCurrentTabIndex()
    if (index >= 0) {
        current.value = index
    }
})

const showTabbar = computed(() => {
    return getCurrentTabIndex() >= 0
})

const tabbarStyle = computed(() => ({
    activeColor: appStore.getStyleConfig.selected_color || '#db2777',
    inactiveColor: appStore.getStyleConfig.default_color || '#9ca3af'
}))

const nativeTabbar = [
    '/pages/index/index',
    '/pages/news/news',
    '/pages/user/user'
]
const handleChange = (index: number) => {
    const selectTab = tabbarList.value[index]
    if (!selectTab?.link?.path) {
        return
    }
    const navigateType = nativeTabbar.includes(selectTab.link.path) ? 'switchTab' : 'reLaunch'
    navigateTo(selectTab.link, navigateType)
}
</script>
