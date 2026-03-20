<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
        <!-- #endif -->
    </page-meta>
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
        <view
            v-if="isLogin && entryCards.length"
            class="entry-panel"
        >
            <view class="entry-panel__header">
                <view class="entry-panel__title">专属工作入口</view>
                <view class="entry-panel__desc">根据当前账号权限与全局业务规则展示可进入的业务空间</view>
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
                    <view class="entry-card__desc">{{ item.desc }}</view>
                </view>
            </view>
            <view class="entry-panel__foot">婚礼基础档案：{{ weddingProfileStatusText }}</view>
            <view v-if="selectionSummaryText" class="entry-panel__foot">当前地区与档期：{{ selectionSummaryText }}</view>
        </view>
        <tabbar />
    </view>
</template>

<script setup lang="ts">
import { getDecorate } from '@/api/shop'
import cache from '@/utils/cache'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { onShow } from '@dcloudio/uni-app'
import { storeToRefs } from 'pinia'
import { computed, reactive, ref } from 'vue'
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
const selectionSummaryText = ref('')
const serviceBusinessConfig = computed(() => appStore.getServiceBusinessConfig || {})
const entryCards = computed(() => {
    const displayConfig = serviceBusinessConfig.value.display || {}
    const cards = []

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
        meta: 'Region & Date',
        title: '地区与档期前置',
        desc: '先选县区和服务日期，为后续筛选做准备',
        url: '/pages/wedding_region/wedding_region',
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

const navigateTo = (url: string) => {
    uni.navigateTo({
        url
    })
}

const refreshSelectionSummary = () => {
    const selectedRegion = cache.get('selected_region') || {}
    const selectedServiceDate = cache.get('selected_service_date') || ''
    const regionText = [selectedRegion.province_name, selectedRegion.city_name, selectedRegion.district_name]
        .filter(Boolean)
        .join(' / ')
    const summaryItems = [regionText, selectedServiceDate].filter(Boolean)
    selectionSummaryText.value = summaryItems.join('，')
}

onShow(() => {
    userStore.getUser()
    refreshSelectionSummary()
})
getData()
</script>

<style lang="scss" scoped>
.entry-panel {
    margin: 20rpx;
    padding: 28rpx;
    border-radius: 28rpx;
    background: rgba(255, 255, 255, 0.94);
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

.entry-panel__desc {
    margin-top: 10rpx;
    color: #6b7280;
    font-size: 24rpx;
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

.entry-card__desc {
    margin-top: 10rpx;
    color: #6b7280;
    font-size: 24rpx;
    line-height: 1.7;
}
</style>
