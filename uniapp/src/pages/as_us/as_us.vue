<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
        <!-- #endif -->
    </page-meta>
    <w-page-nav />
    <view class="as-us w-page-shell">
        <view class="about-card">
            <image :src="appStore.getWebsiteConfig.shop_logo" mode="" class="img"></image>
            <view class="about-card__title">{{ appStore.getWebsiteConfig.shop_name || '婚庆服务预约' }}</view>
            <view class="about-card__version">当前版本 {{ appStore.config.version }}</view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { useAppStore } from '@/stores/app'
const appStore = useAppStore()
</script>

<style lang="scss" scoped>
.as-us {
    min-height: 100vh;
}

.about-card {
    display: grid;
    justify-items: center;
    gap: 18rpx;
    margin-top: 120rpx;
    padding: 36rpx 28rpx;
    border-radius: 32rpx;
    background: rgba(255, 255, 255, 0.94);
    border: 1rpx solid rgba(219, 39, 119, 0.1);
    box-shadow: 0 18rpx 48rpx rgba(31, 41, 55, 0.06);
}

.img {
    width: 160rpx;
    height: 160rpx;
    border-radius: 28rpx;
}

.about-card__title {
    color: #111827;
    font-size: 34rpx;
    font-weight: 600;
}

.about-card__version {
    color: #6b7280;
    font-size: 24rpx;
}
</style>
