<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar
            :front-color="$theme.navColor"
            background-color="transparent"
        />
        <!-- #endif -->
    </page-meta>
    <w-page-nav variant="tab" :show-back="false" title=" " />
    <view class="home-page w-page-shell w-page-shell--tab">
        <view class="home-hero">
            <view class="home-hero__nav-title">{{ homeHeroTitle }}</view>
            <swiper
                v-if="bannerList.length"
                class="home-hero__swiper"
                circular
                autoplay
                interval="5000"
                duration="500"
                indicator-dots
                indicator-color="rgba(255,255,255,0.45)"
                indicator-active-color="#ffffff"
            >
                <swiper-item v-for="(item, index) in bannerList" :key="index">
                    <view class="home-hero__slide" @click="handleBannerClick(item)">
                        <image
                            class="home-hero__image"
                            :src="getBannerImage(item.image)"
                            mode="aspectFill"
                        />
                    </view>
                </swiper-item>
            </swiper>
            <view v-else class="home-hero__fallback">
                <view class="w-section-caption">Wedding Booking</view>
                <view class="home-hero__fallback-title">婚庆服务预约</view>
            </view>
        </view>

        <view class="home-cta-wrap">
            <button class="home-hero__cta" @click="handleTradeEntry">
                <view class="home-hero__cta-inner">
                    <view class="home-hero__cta-icon"></view>
                    <text class="home-hero__cta-text">查询档期</text>
                    <view class="home-hero__cta-arrow"></view>
                </view>
            </button>
        </view>

        <view class="w-card">
            <view class="w-section-head">
                <view class="w-section-title">快捷入口</view>
            </view>
            <view class="home-entry-grid">
                <view
                    v-for="item in quickEntryCards"
                    :key="item.title"
                    class="home-entry-card"
                    @click="navigateTo(item.url)"
                >
                    <view class="home-entry-card__meta">{{ item.meta }}</view>
                    <view class="home-entry-card__title">{{ item.title }}</view>
                </view>
            </view>
        </view>

        <view v-if="roleEntryCards.length" class="w-card">
            <view class="w-section-head">
                <view class="w-section-title">角色入口</view>
            </view>
            <view class="home-entry-grid">
                <view
                    v-for="item in roleEntryCards"
                    :key="item.title"
                    class="home-entry-card"
                    :class="item.cardClass"
                    @click="navigateTo(item.url)"
                >
                    <view class="home-entry-card__meta">{{ item.meta }}</view>
                    <view class="home-entry-card__title">{{ item.title }}</view>
                </view>
            </view>
        </view>

        <view v-if="categoryList.length" class="w-card">
            <view class="w-section-head">
                <view class="w-section-title">服务分类</view>
            </view>
            <scroll-view class="w-chip-scroll home-category-scroll" scroll-x enable-flex>
                <view
                    v-for="item in categoryList"
                    :key="item.id"
                    class="w-chip home-category-chip"
                    @click="handleCategoryEntry(item)"
                >
                    {{ item.name }}
                </view>
            </scroll-view>
        </view>

        <view v-if="state.article.length" class="w-card home-news-card">
            <view class="w-section-head home-news-card__head">
                <view class="w-section-title">最新动态</view>
                <view class="home-news-card__link" @click="handleOpenNewsTab">查看全部</view>
            </view>
            <news-card
                v-for="item in state.article"
                :key="item.id"
                :news-id="item.id"
                :item="item"
            />
        </view>

        <u-back-top
            :scroll-top="scrollTop"
            :top="100"
            :customStyle="{
                backgroundColor: '#FFF',
                color: '#000',
                boxShadow: '0px 3px 6px rgba(0, 0, 0, 0.1)'
            }"
        >
        </u-back-top>

        <!--  #ifdef H5  -->
        <view class="text-center py-4 mb-12">
            <router-navigate
                class="mx-1 text-xs text-[#495770]"
                :to="{
                    path: '/pages/webview/webview',
                    query: {
                        url: item.value
                    }
                }"
                v-for="item in appStore.getCopyrightConfig"
                :key="item.key"
            >
                {{ item.key }}
            </router-navigate>
        </view>
        <!--  #endif  -->

        <!--  #ifdef MP  -->
        <MpPrivacyPopup></MpPrivacyPopup>
        <!--  #endif  -->

        <tabbar/>
    </view>
</template>

<script setup lang="ts">
import { getIndex } from '@/api/shop'
import { getWeddingCategories } from '@/api/wedding'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { navigateTo as navigateLink } from '@/utils/util'
import { buildWeddingTradeQueryUrl } from '@/utils/wedding'
import { onLoad, onPageScroll, onShow } from '@dcloudio/uni-app'
import { storeToRefs } from 'pinia'
import { computed, reactive, ref } from 'vue'

// #ifdef MP
import MpPrivacyPopup from './component/mp-privacy-popup.vue'
// #endif

type DecorateWidget = {
    name: string
    content?: Record<string, any>
    styles?: Record<string, any>
}

const appStore = useAppStore()
const userStore = useUserStore()
const { userInfo, isLogin } = storeToRefs(userStore)
const state = reactive<{
    pages: DecorateWidget[]
    meta: any[]
    article: any[]
}>({
    pages: [],
    meta: [],
    article: []
})
const categoryList = ref<any[]>([])
const scrollTop = ref(0)

const serviceBusinessConfig = computed(() => appStore.getServiceBusinessConfig || {})
const homeHeroTitle = '首页'
const bannerWidget = computed(() => state.pages.find((item) => item.name === 'banner'))
const bannerList = computed(() =>
    (bannerWidget.value?.content?.data || []).filter((item: Record<string, any>) => String(item?.is_show ?? '1') !== '0')
)

const quickEntryCards = computed(() => {
    const displayConfig = serviceBusinessConfig.value.display || {}
    const cards = [
        {
            meta: 'My Orders',
            title: '我的婚庆订单',
            desc: '查看下单、接单、支付与履约进度',
            url: '/pages/wedding_order_list/wedding_order_list'
        },
        {
            meta: 'Notice Center',
            title: '通知中心',
            desc: '统一查看订单、改期、支付与评价提醒',
            url: '/pages/notice_center/notice_center'
        }
    ]

    if (Number(displayConfig.wedding_profile_enabled ?? 0) === 1) {
        cards.push({
            meta: 'Wedding Profile',
            title: '婚礼基础档案',
            desc: '维护婚礼日期、预算、联系人与宴会地区',
            url: '/pages/wedding_profile/wedding_profile'
        })
    }

    return cards
})

const roleEntryCards = computed(() => {
    const displayConfig = serviceBusinessConfig.value.display || {}
    const cards = [
        {
            meta: 'Wedding Query',
            title: '查询档期',
            desc: '重新选择地区、日期与筛选条件',
            url: '/pages/wedding_region/wedding_region',
            cardClass: ''
        }
    ]

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
            desc: '查看经营指标、规则摘要与关键待办',
            url: '/pages/dashboard/dashboard',
            cardClass: 'home-entry-card--warm'
        })
    }

    return cards
})

const navigateTo = (url: string) => {
    uni.navigateTo({ url })
}

const getBannerImage = (url: string) => appStore.getImageUrl(url || '')

const handleBannerClick = (item: Record<string, any>) => {
    if (!item?.link?.path) {
        return
    }
    navigateLink(item.link)
}

const handleTradeEntry = (categoryId = 0) => {
    navigateTo(
        buildWeddingTradeQueryUrl('/pages/wedding_region/wedding_region', {
            category_id: categoryId
        })
    )
}

const handleCategoryEntry = (item: Record<string, any>) => {
    handleTradeEntry(Number(item.id || 0))
}

const handleOpenNewsTab = () => {
    uni.switchTab({
        url: '/pages/news/news'
    })
}

const parseDecorateJson = (value: string) => {
    try {
        const result = JSON.parse(value || '[]')
        return Array.isArray(result) ? result : []
    } catch (error) {
        console.log('parseDecorateJson error', error)
        return []
    }
}

const loadHomeData = async () => {
    const data = await getIndex()
    state.pages = parseDecorateJson(data?.page?.data)
    state.meta = parseDecorateJson(data?.page?.meta)
    state.article = data?.article || []
    uni.setNavigationBarTitle({
        title: '首页'
    })
}

const loadCategories = async () => {
    try {
        categoryList.value = (await getWeddingCategories()) || []
    } catch (error) {
        console.log('loadCategories error', error)
        categoryList.value = []
    }
}

onPageScroll((event: any) => {
    scrollTop.value = event.scrollTop
})

onLoad(() => {
    loadHomeData()
    loadCategories()
})

onShow(() => {
    if (isLogin.value) {
        userStore.getUser()
    }
})
</script>

<style lang="scss" scoped>
.home-page {
    display: grid;
    gap: 24rpx;
    padding-top: 0;
}

.home-hero {
    position: relative;
    margin-left: -24rpx;
    margin-right: -24rpx;
    margin-top: -24rpx;
}

.home-hero__nav-title {
    position: absolute;
    top: calc(var(--status-bar-height, 0px) + 24rpx);
    left: 24rpx;
    display: flex;
    align-items: center;
    min-height: 64rpx;
    z-index: 3;
    color: #ffffff;
    font-size: 32rpx;
    font-weight: 600;
    line-height: 1;
    text-shadow: 0 4rpx 16rpx rgba(15, 23, 42, 0.24);
    pointer-events: none;
}

.home-hero__swiper {
    height: 920rpx;
    border-radius: 0 0 34rpx 34rpx;
    overflow: hidden;
}

.home-hero__slide {
    width: 100%;
    height: 100%;
}

.home-hero__image {
    width: 100%;
    height: 100%;
    display: block;
}

.home-hero__fallback {
    display: grid;
    align-content: center;
    justify-items: center;
    height: 920rpx;
    border-radius: 0 0 34rpx 34rpx;
    background:
        linear-gradient(135deg, rgba(219, 39, 119, 0.22), rgba(202, 138, 4, 0.18)),
        linear-gradient(180deg, #fff6fb, #fffdf9);
}

.home-hero__fallback-title {
    margin-top: 14rpx;
    color: #ffffff;
    font-size: 42rpx;
    font-weight: 600;
}

.home-cta-wrap {
    margin-top: -2rpx;
}

.home-hero__cta {
    width: 100%;
    min-height: 104rpx;
    padding: 0 34rpx;
    border-radius: 26rpx;
    background: linear-gradient(135deg, #db2777, #ca8a04);
    box-shadow: 0 22rpx 44rpx rgba(219, 39, 119, 0.18);
}

.home-hero__cta-inner {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20rpx;
}

.home-hero__cta-icon {
    position: relative;
    width: 34rpx;
    height: 34rpx;
    border: 3rpx solid rgba(255, 255, 255, 0.94);
    border-radius: 10rpx;
    box-sizing: border-box;
}

.home-hero__cta-icon::before {
    content: '';
    position: absolute;
    top: -8rpx;
    left: 7rpx;
    width: 4rpx;
    height: 10rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.94);
    box-shadow: 12rpx 0 0 rgba(255, 255, 255, 0.94);
}

.home-hero__cta-icon::after {
    content: '';
    position: absolute;
    top: 11rpx;
    left: 6rpx;
    right: 6rpx;
    height: 3rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.7);
}

.home-hero__cta-text {
    color: #ffffff;
    font-size: 42rpx;
    font-weight: 600;
    letter-spacing: 2rpx;
}

.home-hero__cta-arrow {
    width: 14rpx;
    height: 14rpx;
    border-top: 3rpx solid rgba(255, 255, 255, 0.94);
    border-right: 3rpx solid rgba(255, 255, 255, 0.94);
    transform: rotate(45deg);
}

.home-summary__value {
    margin-top: 12rpx;
    color: #111827;
    font-size: 30rpx;
    font-weight: 600;
    line-height: 1.6;
}

.home-entry-grid {
    display: grid;
    gap: 16rpx;
    margin-top: 20rpx;
}

.home-entry-card {
    padding: 24rpx;
    border-radius: 24rpx;
    background: linear-gradient(135deg, #fff8fb, #fffdf9);
    border: 1rpx solid rgba(219, 39, 119, 0.1);
}

.home-entry-card--warm {
    border-color: rgba(202, 138, 4, 0.18);
    background: linear-gradient(135deg, #fffbf2, #fffdf9);
}

.home-entry-card__meta {
    color: #9d174d;
    font-size: 20rpx;
    letter-spacing: 2rpx;
}

.home-entry-card__title {
    margin-top: 10rpx;
    color: #111827;
    font-size: 30rpx;
    font-weight: 600;
}

.home-category-scroll {
    margin-top: 20rpx;
}

.home-category-chip {
    color: #831843;
}

.home-news-card__head {
    margin-bottom: 8rpx;
}

.home-news-card__link {
    color: #9d174d;
    font-size: 24rpx;
}

.home-news-card :deep(.news-card) {
    margin-left: 0;
    margin-right: 0;
}

.home-hero :deep(.uni-swiper-dots) {
    bottom: 26rpx;
}

.home-hero :deep(.uni-swiper-dot) {
    width: 12rpx;
    height: 12rpx;
    margin: 0 8rpx;
}
</style>
