<template>
    <!-- #ifdef MP-WEIXIN -->
    <view class="w-page-nav" :class="pageNavClassName">
        <u-navbar
            :is-back="false"
            :is-fixed="true"
            :immersive="isTabVariant"
            :title="''"
            :border-bottom="false"
            :background="navbarBackground"
        >
            <view v-if="isTabVariant" class="w-page-nav__title w-page-nav__title--tab">
                {{ resolvedTitle }}
            </view>
            <view v-else class="w-page-nav__head">
                <view
                    class="w-page-nav__back"
                    :class="{ 'w-page-nav__back--hidden': !resolvedShowBack }"
                    @tap.stop="handleBackTap"
                >
                    <view v-if="resolvedShowBack" class="w-page-nav__back-icon"></view>
                </view>
                <view class="w-page-nav__title w-page-nav__title--default">
                    {{ resolvedTitle }}
                </view>
            </view>
        </u-navbar>
    </view>
    <!-- #endif -->
</template>

<script setup lang="ts">
import { useAppStore } from '@/stores/app'
import { isTabbarPagePath, normalizePagePath, resolvePageNavTitle } from '@/utils/page-nav'
import { computed } from 'vue'
import { useRoute } from 'uniapp-router-next'

const props = withDefaults(
    defineProps<{
        title?: string
        showBack?: boolean
        variant?: 'default' | 'tab'
    }>(),
    {
        title: '',
        variant: 'default'
    }
)

const route = useRoute()
const appStore = useAppStore()

const currentPageState = computed(() => {
    const pages = getCurrentPages()
    const current = pages[pages.length - 1]
    return {
        path: normalizePagePath((current as any)?.route || route.path || ''),
        stackSize: pages.length
    }
})

const currentPath = computed(() => currentPageState.value.path)

const resolvedTitle = computed(() => {
    return (
        props.title ||
        resolvePageNavTitle(currentPath.value) ||
        appStore.getWebsiteConfig.shop_name ||
        '婚庆服务'
    )
})

const resolvedShowBack = computed(() => {
    if (typeof props.showBack === 'boolean') {
        return props.showBack
    }
    return !isTabVariant.value && currentPageState.value.stackSize > 1
})

const isTabVariant = computed(() => props.variant === 'tab')

const navbarBackground = computed(() => ({
    background: isTabVariant.value ? 'transparent' : '#ffffff'
}))

const pageNavClassName = computed(() => (isTabVariant.value ? 'w-page-nav--tab' : 'w-page-nav--default'))

const handleBack = () => {
    const pages = getCurrentPages()
    if (pages.length > 1) {
        uni.navigateBack()
        return
    }

    if (isTabbarPagePath(currentPath.value)) {
        uni.switchTab({
            url: currentPath.value
        })
        return
    }

    uni.reLaunch({
        url: '/pages/index/index'
    })
}

const handleBackTap = () => {
    if (!resolvedShowBack.value) {
        return
    }
    handleBack()
}
</script>

<style lang="scss" scoped>
.w-page-nav--default :deep(.u-navbar) {
    background: rgba(255, 255, 255, 0.98) !important;
    border-bottom: 1rpx solid rgba(219, 39, 119, 0.08);
    box-shadow: 0 10rpx 28rpx rgba(15, 23, 42, 0.04);
}

.w-page-nav--tab :deep(.u-navbar) {
    background: transparent !important;
    box-shadow: none;
}

.w-page-nav :deep(.u-slot-content) {
    min-width: 0;
}

.w-page-nav__title {
    color: #111827;
    font-size: 32rpx;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.w-page-nav__title--tab {
    padding-left: 24rpx;
    line-height: 60rpx;
}

.w-page-nav__title--default {
    flex: 1;
    min-width: 0;
    line-height: 72rpx;
}

.w-page-nav__head {
    display: flex;
    align-items: center;
    width: 100%;
    min-width: 0;
    padding-left: 12rpx;
}

.w-page-nav__back {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 72rpx;
    height: 72rpx;
    flex-shrink: 0;
}

.w-page-nav__back--hidden {
    opacity: 0;
    pointer-events: none;
}

.w-page-nav__back-icon {
    width: 18rpx;
    height: 18rpx;
    box-sizing: border-box;
    border-top: 4rpx solid #111827;
    border-left: 4rpx solid #111827;
    transform: rotate(-45deg);
}
</style>
