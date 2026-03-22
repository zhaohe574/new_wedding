<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <w-page-nav />
    <view class="provider-order-workbench-page min-h-screen px-[24rpx] py-[24rpx] box-border">
        <view class="hero-card">
            <view class="hero-card__eyebrow">Provider Orders</view>
            <view class="hero-card__title">服务订单工作台</view>
            <view class="hero-card__meta">{{ tabList[current]?.name || '全部订单' }}</view>
        </view>

        <view class="tabs-card mt-[24rpx]">
            <u-tabs
                :list="tabList"
                :is-scroll="false"
                activeColor="#9d174d"
                inactiveColor="#6b7280"
                bgColor="transparent"
                v-model="current"
                @change="changeTab"
            />
        </view>

        <view v-if="loading" class="state-card mt-[24rpx]">正在加载订单工作台...</view>
        <view v-else-if="!orderLists.length" class="state-card mt-[24rpx]">当前筛选下暂无订单。</view>
        <view v-else class="order-grid mt-[24rpx]">
            <view v-for="item in orderLists" :key="item.id" class="order-card" @click="openDetail(item.id)">
                <view class="order-card__top">
                    <view class="order-card__sn">订单号：{{ item.sn }}</view>
                    <view class="order-card__status">{{ item.order_status_desc }}</view>
                </view>
                <view class="order-card__title">{{ item.user_nickname || '用户' }} / {{ item.package_name }}</view>
                <view class="order-card__meta">
                    <view>{{ item.service_date }}</view>
                    <view>{{ [item.province_name, item.city_name, item.district_name].filter(Boolean).join(' / ') || '-' }}</view>
                </view>
                <view class="order-card__badges">
                    <view v-if="item.has_pending_reschedule" class="order-badge order-badge--rose">改期待处理</view>
                    <view v-if="item.has_pending_review" class="order-badge order-badge--gold">评价待审</view>
                    <view v-if="Number(item.order_status) === 10" class="order-badge">待确认</view>
                    <view v-if="Number(item.order_status) === 30" class="order-badge">待履约</view>
                </view>
                <view class="order-card__price">￥{{ Number(item.order_amount || 0).toFixed(2) }}</view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getProviderOrderLists } from '@/api/wedding'
import { onShow } from '@dcloudio/uni-app'
import { computed, ref } from 'vue'

const tabList = ref([
    {
        name: '全部订单',
        view_tab: 'all',
        desc: '统一查看全部婚庆订单，快速进入接单、改期、履约与评价处理。'
    },
    {
        name: '待确认',
        view_tab: 'pending_confirm',
        desc: '请尽快完成接单或拒单，避免超时释放当前档期。'
    },
    {
        name: '待履约',
        view_tab: 'wait_service',
        desc: '已支付或凭证通过的订单会进入待履约，请按服务日期完成履约。'
    },
    {
        name: '改期待处理',
        view_tab: 'reschedule_pending',
        desc: '优先处理用户发起的改期申请，避免服务日期冲突长期挂起。'
    },
    {
        name: '评价待审',
        view_tab: 'review_pending',
        desc: '当评价审核归属到服务人员时，请在这里尽快完成审核。'
    }
])

const current = ref(0)
const loading = ref(false)
const orderLists = ref<any[]>([])

const loadLists = async () => {
    loading.value = true
    try {
        const currentTab = tabList.value[current.value]
        const data = await getProviderOrderLists({
            page_no: 1,
            page_size: 50,
            view_tab: currentTab?.view_tab || 'all'
        })
        orderLists.value = data?.lists || []
    } finally {
        loading.value = false
    }
}

const changeTab = async (index: number) => {
    current.value = index
    await loadLists()
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
.provider-order-workbench-page {
    background:
        radial-gradient(circle at top right, rgba(219, 39, 119, 0.1), transparent 26%),
        linear-gradient(180deg, #fffafc, #f8f3ef 44%, #f7f3f0);
}

.hero-card,
.tabs-card,
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

.tabs-card {
    padding: 8rpx 10rpx;
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

.hero-card__meta,
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

.order-card__badges {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-top: 16rpx;
}

.order-badge {
    padding: 8rpx 18rpx;
    border-radius: 999rpx;
    background: rgba(219, 39, 119, 0.08);
    color: #9d174d;
    font-size: 20rpx;
}

.order-badge--rose {
    background: rgba(219, 39, 119, 0.12);
}

.order-badge--gold {
    background: rgba(202, 138, 4, 0.12);
    color: #b45309;
}

.order-card__price {
    margin-top: 14rpx;
    color: #be123c;
    font-size: 32rpx;
    font-weight: 600;
}
</style>
