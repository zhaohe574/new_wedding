<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar
            :front-color="$theme.navColor"
            background-color="transparent"
        />
        <!-- #endif -->
    </page-meta>
    <w-page-nav variant="tab" :show-back="false" :title="userNavTitle" />
    <view class="user">
        <view v-for="(item, index) in state.pages" :key="index">
            <template v-if="item.name == 'user-info'">
                <w-user-info
                    :pageMeta="state.meta"
                    :content="item.content"
                    :styles="item.styles"
                    :user="userInfo"
                    :is-login="isLogin"
                />
            </template>
            <template v-if="item.name == 'my-service'">
                <w-my-service :content="item.content" :styles="item.styles" />
            </template>
            <template v-if="item.name == 'user-banner'">
                <w-user-banner :content="item.content" :styles="item.styles" />
            </template>
        </view>
        <view v-if="isLogin && entryCards.length" class="entry-panel">
            <view class="entry-panel__header">
                <view class="entry-panel__title">我的业务空间</view>
            </view>
            <view class="entry-panel__list">
                <view
                    v-for="item in entryCards"
                    :key="item.title"
                    class="entry-card"
                    :class="item.cardClass"
                    @click="navigateTo(item.url)"
                >
                    <view class="entry-card__meta">{{ item.meta }}</view>
                    <view class="entry-card__title">{{ item.title }}</view>
                </view>
            </view>
            <view class="entry-panel__foot">婚礼基础档案 {{ weddingProfileStatusText }}</view>
        </view>
        <tabbar />
    </view>
</template>

<script setup lang="ts">
import { getDecorate } from '@/api/shop'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { buildWeddingTradeQueryUrl } from '@/utils/wedding'
import { onShow } from '@dcloudio/uni-app'
import { storeToRefs } from 'pinia'
import { computed, reactive } from 'vue'
const state = reactive<{
    meta: any[]
    pages: any[]
}>({
    meta: [],
    pages: []
})
const getData = async () => {
    const data = await getDecorate({ id: 2 })
    state.meta = JSON.parse(data.meta)
    state.pages = JSON.parse(data.data)
    uni.setNavigationBarTitle({
        title: state.meta[0].content.title
    })
}
const appStore = useAppStore()
const userStore = useUserStore()
const { userInfo, isLogin } = storeToRefs(userStore)
const serviceBusinessConfig = computed(() => appStore.getServiceBusinessConfig || {})
const entryCards = computed(() => {
    const displayConfig = serviceBusinessConfig.value.display || {}
    const cards = [
        {
            meta: 'My Orders',
            title: '我的婚庆订单',
            desc: '查看下单、接单、支付与凭证审核进度',
            url: '/pages/wedding_order_list/wedding_order_list',
            cardClass: ''
        },
        {
            meta: 'Notice Center',
            title: '通知中心',
            desc: '统一查看订单、改期、支付与评价的站内提醒',
            url: '/pages/notice_center/notice_center',
            cardClass: ''
        }
    ]

    if (Number(displayConfig.wedding_profile_enabled ?? 0) === 1) {
        cards.push({
            meta: 'Wedding Profile',
            title: '婚礼基础档案',
            desc: '维护婚礼日期、宴会地区、预算与联系人',
            url: '/pages/wedding_profile/wedding_profile',
            cardClass: ''
        })
    }

    cards.push({
        meta: 'Wedding Query',
        title: '查询档期',
        desc: '重新选择地区、日期与筛选条件',
        url: buildWeddingTradeQueryUrl('/pages/wedding_region/wedding_region'),
        cardClass: ''
    })

    if (userInfo.value?.can_enter_provider_center && Number(displayConfig.provider_center_enabled ?? 0) === 1) {
        cards.push({
            meta: 'Provider Center',
            title: '服务人员中心',
            desc: '处理资料、档期、订单与内容互动',
            url: '/pages/provider_center/provider_center',
            cardClass: ''
        })
    }

    if (userInfo.value?.can_view_dashboard && Number(displayConfig.dashboard_enabled ?? 0) === 1) {
        cards.push({
            meta: 'Dashboard',
            title: '经营驾驶舱',
            desc: '查看核心经营指标、规则摘要与关键待办',
            url: '/pages/dashboard/dashboard',
            cardClass: 'entry-card--warm'
        })
    }

    return cards
})
const weddingProfileStatusText = computed(() => {
    return Number(serviceBusinessConfig.value?.display?.wedding_profile_enabled ?? 0) === 1 ? '已启用' : '已关闭'
})
const userNavTitle = computed(() => state.meta[0]?.content?.title || '我的')

const navigateTo = (url: string) => {
    uni.navigateTo({
        url
    })
}

onShow(() => {
    if (isLogin.value) {
        userStore.getUser()
    }
})
getData()
</script>

<style lang="scss" scoped>
.user {
    min-height: 100vh;
    padding-top: var(--w-page-nav-height);
    padding-bottom: calc(150rpx + env(safe-area-inset-bottom));
    background:
        radial-gradient(circle at top right, rgba(219, 39, 119, 0.08), transparent 24%),
        linear-gradient(180deg, #fffafc, #f8f3ef 44%, #f7f3f0);
}

.entry-panel {
    margin: 20rpx;
    padding: 28rpx;
    border-radius: 28rpx;
    background: rgba(255, 255, 255, 0.94);
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    box-shadow: 0 18rpx 48rpx rgba(31, 41, 55, 0.06);
}

.entry-panel__header {
    margin-bottom: 20rpx;
}

.entry-panel__title {
    color: #1f2937;
    font-size: 32rpx;
    font-weight: 600;
}

.entry-panel__list {
    display: grid;
    gap: 18rpx;
}

.entry-panel__foot {
    margin-top: 18rpx;
    color: #9ca3af;
    font-size: 22rpx;
}

.entry-card {
    padding: 26rpx;
    border-radius: 24rpx;
    background: linear-gradient(135deg, #fff7fb, #fffdfb);
    border: 1rpx solid rgba(219, 39, 119, 0.12);
}

.entry-card--warm {
    border-color: rgba(202, 138, 4, 0.18);
    background: linear-gradient(135deg, #fffbf2, #fffdf9);
}

.entry-card__meta {
    color: #9d174d;
    font-size: 20rpx;
    letter-spacing: 2rpx;
}

.entry-card__title {
    margin-top: 12rpx;
    color: #111827;
    font-size: 30rpx;
    font-weight: 600;
}
</style>
