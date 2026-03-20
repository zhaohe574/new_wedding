<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <view class="provider-order-pending-page min-h-screen px-[24rpx] py-[24rpx] box-border">
        <view class="hero-card">
            <view class="hero-card__eyebrow">Provider Orders</view>
            <view class="hero-card__title">待确认订单</view>
            <view class="hero-card__desc">请在超时前完成接单或拒单，避免影响档期周转。</view>
        </view>

        <view v-if="loading" class="state-card mt-[24rpx]">正在加载待确认订单...</view>
        <view v-else-if="!orderLists.length" class="state-card mt-[24rpx]">当前暂无待确认订单。</view>
        <view v-else class="order-grid mt-[24rpx]">
            <view v-for="item in orderLists" :key="item.id" class="order-card" @click="openDetail(item.id)">
                <view class="order-card__top">
                    <view class="order-card__sn">订单号：{{ item.sn }}</view>
                    <view class="order-card__status">{{ item.order_status_desc }}</view>
                </view>
                <view class="order-card__title">{{ item.user_nickname || '用户' }} / {{ item.package_name }}</view>
                <view class="order-card__meta">
                    <view>{{ item.service_date }}</view>
                    <view>{{ item.province_name }} / {{ item.city_name }} / {{ item.district_name }}</view>
                </view>
                <view class="order-card__price">￥{{ Number(item.order_amount || 0).toFixed(2) }}</view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getProviderPendingOrderLists } from '@/api/wedding'
import { onShow } from '@dcloudio/uni-app'
import { ref } from 'vue'

const loading = ref(false)
const orderLists = ref<any[]>([])

const loadLists = async () => {
    loading.value = true
    try {
        const data = await getProviderPendingOrderLists({
            page_no: 1,
            page_size: 20
        })
        orderLists.value = data?.lists || []
    } finally {
        loading.value = false
    }
}

const openDetail = (orderId: number) => {
    uni.navigateTo({
        url: `/pages/provider_order_detail/provider_order_detail?order_id=${orderId}`
    })
}

onShow(async () => {
    await loadLists()
})
</script>

<style lang="scss" scoped>
.provider-order-pending-page {
    background:
        radial-gradient(circle at top right, rgba(219, 39, 119, 0.1), transparent 26%),
        linear-gradient(180deg, #fffafc, #f8f3ef 44%, #f7f3f0);
}

.hero-card,
.state-card,
.order-card {
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    border-radius: 28rpx;
    box-shadow: 0 18rpx 48rpx rgba(31, 41, 55, 0.06);
}

.hero-card,
.state-card {
    padding: 28rpx 30rpx;
}

.hero-card__eyebrow {
    color: #9d174d;
    font-size: 22rpx;
    letter-spacing: 4rpx;
}

.hero-card__title {
    margin-top: 16rpx;
    color: #1f2937;
    font-size: 42rpx;
    font-weight: 600;
}

.hero-card__desc,
.state-card {
    margin-top: 16rpx;
    color: #6b7280;
    font-size: 24rpx;
    line-height: 1.8;
}

.order-grid {
    display: grid;
    gap: 18rpx;
}

.order-card {
    padding: 24rpx;
}

.order-card__top,
.order-card__meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.order-card__sn {
    color: #9ca3af;
    font-size: 22rpx;
}

.order-card__status {
    color: #9d174d;
    font-size: 22rpx;
}

.order-card__title {
    margin-top: 12rpx;
    color: #111827;
    font-size: 28rpx;
    font-weight: 600;
}

.order-card__meta {
    margin-top: 12rpx;
    color: #6b7280;
    font-size: 22rpx;
}

.order-card__price {
    margin-top: 14rpx;
    color: #be123c;
    font-size: 32rpx;
    font-weight: 600;
}
</style>

