<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <view class="wedding-order-list-page min-h-screen px-[24rpx] py-[24rpx] box-border">
        <view class="hero-card">
            <view class="hero-card__eyebrow">Wedding Orders</view>
            <view class="hero-card__title">我的婚庆订单</view>
            <view class="hero-card__desc">统一查看下单、接单、支付与线下凭证审核进度。</view>
        </view>

        <scroll-view class="status-scroll mt-[24rpx]" scroll-x enable-flex>
            <view
                v-for="item in statusTabs"
                :key="item.value"
                class="status-chip"
                :class="{ 'status-chip--active': activeStatus === item.value }"
                @click="handleStatusChange(item.value)"
            >
                {{ item.label }}
            </view>
        </scroll-view>

        <view v-if="loading" class="state-card mt-[24rpx]">正在加载订单...</view>
        <view v-else-if="!orderLists.length" class="state-card mt-[24rpx]">
            暂无订单，去挑选服务人员创建第一笔预约吧。
            <button class="ghost-btn mt-[16rpx]" @click="goRegionSelect">去预约</button>
        </view>
        <view v-else class="order-grid mt-[24rpx]">
            <view v-for="item in orderLists" :key="item.id" class="order-card" @click="openDetail(item.id)">
                <view class="order-card__top">
                    <view class="order-card__sn">订单号：{{ item.sn }}</view>
                    <view class="order-card__status">{{ item.order_status_desc }}</view>
                </view>
                <view class="order-card__title">{{ item.provider_name }} / {{ item.package_name }}</view>
                <view class="order-card__meta">
                    <view>{{ item.service_date }}</view>
                    <view>{{ item.payment_type_desc }} · {{ item.pay_status_desc }}</view>
                </view>
                <view class="order-card__price">￥{{ Number(item.order_amount || 0).toFixed(2) }}</view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getWeddingOrderLists } from '@/api/wedding'
import { onShow } from '@dcloudio/uni-app'
import { ref } from 'vue'

const loading = ref(false)
const activeStatus = ref<number | ''>('')
const orderLists = ref<any[]>([])

const statusTabs = [
    { label: '全部', value: '' },
    { label: '待确认', value: 10 },
    { label: '待支付', value: 20 },
    { label: '待凭证审核', value: 21 },
    { label: '待履约', value: 30 },
    { label: '退款中', value: 80 },
    { label: '已退款', value: 81 },
    { label: '已取消', value: 70 }
]

const loadOrders = async () => {
    loading.value = true
    try {
        const data = await getWeddingOrderLists({
            order_status: activeStatus.value,
            page_no: 1,
            page_size: 20
        })
        orderLists.value = data?.lists || []
    } finally {
        loading.value = false
    }
}

const handleStatusChange = async (status: number | '') => {
    if (activeStatus.value === status) {
        return
    }
    activeStatus.value = status
    await loadOrders()
}

const openDetail = (orderId: number) => {
    uni.navigateTo({
        url: `/pages/wedding_order_detail/wedding_order_detail?order_id=${orderId}`
    })
}

const goRegionSelect = () => {
    uni.navigateTo({
        url: '/pages/wedding_region/wedding_region'
    })
}

onShow(async () => {
    await loadOrders()
})
</script>

<style lang="scss" scoped>
.wedding-order-list-page {
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

.status-scroll {
    white-space: nowrap;
}

.status-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-right: 16rpx;
    padding: 18rpx 28rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.88);
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    color: #6b7280;
    font-size: 24rpx;
}

.status-chip--active {
    background: linear-gradient(135deg, #db2777, #ca8a04);
    color: #ffffff;
    border-color: transparent;
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

.ghost-btn {
    border-radius: 999rpx;
    color: #9d174d;
    border: 1rpx solid rgba(219, 39, 119, 0.3);
    background: rgba(255, 255, 255, 0.8);
}
</style>
