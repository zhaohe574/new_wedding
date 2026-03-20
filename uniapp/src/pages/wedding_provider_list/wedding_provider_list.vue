<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <view class="wedding-provider-list-page min-h-screen px-[24rpx] py-[24rpx] box-border">
        <view class="hero-card">
            <view class="hero-card__eyebrow">Provider List</view>
            <view class="hero-card__title">按地区与日期筛选服务人员</view>
            <view class="hero-card__desc">
                当前列表只展示在所选县区下存在可售套餐、且服务日期仍可预约的服务人员。
            </view>
            <view class="hero-card__meta">{{ selectionSummary || '请先返回上一步选择地区与日期' }}</view>
        </view>

        <scroll-view class="category-scroll mt-[24rpx]" scroll-x enable-flex>
            <view
                v-for="item in categoryList"
                :key="item.id"
                class="category-chip"
                :class="{ 'category-chip--active': activeCategoryId === item.id }"
                @click="handleCategoryChange(item.id)"
            >
                {{ item.name }}
            </view>
        </scroll-view>

        <view class="panel-card mt-[24rpx]">
            <view class="panel-card__title">筛选结果</view>
            <view class="panel-card__desc">切换分类时会自动重置已选服务人员与套餐，避免沿用失效草稿。</view>
        </view>

        <view v-if="loading" class="state-card mt-[24rpx]">正在加载服务人员列表...</view>
        <view v-else-if="!providerList.length" class="state-card mt-[24rpx]">
            当前地区与日期暂无可预约服务人员，请切换分类或返回上一步调整条件。
        </view>
        <view v-else class="provider-grid mt-[24rpx]">
            <view
                v-for="item in providerList"
                :key="item.provider_id"
                class="provider-card"
                @click="handleOpenDetail(item.provider_id)"
            >
                <image class="provider-card__avatar" :src="item.avatar || defaultAvatar" mode="aspectFill" />
                <view class="provider-card__content">
                    <view class="provider-card__top">
                        <view class="provider-card__title">{{ item.name }}</view>
                        <view v-if="item.recommend" class="provider-card__badge">推荐</view>
                    </view>
                    <view class="provider-card__summary">{{ item.summary || '暂未补充服务人员简介' }}</view>
                    <view class="provider-card__foot">
                        <view class="provider-card__price">￥{{ formatPrice(item.min_price) }} 起</view>
                        <view class="provider-card__hint">
                            {{ getMatchLevelText(item.price_match_level) }}命中，共 {{ item.matched_package_count }} 个可售套餐
                        </view>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getWeddingCategories, getWeddingProviderLists } from '@/api/wedding'
import {
    buildWeddingSelectionSummary,
    getSelectedRegion,
    getSelectedServiceDate,
    getWeddingOrderDraft,
    patchWeddingOrderDraft
} from '@/utils/wedding'
import { onShow } from '@dcloudio/uni-app'
import { ref } from 'vue'

const defaultAvatar = '/static/images/user/default_avatar.png'
const categoryList = ref<any[]>([])
const providerList = ref<any[]>([])
const loading = ref(false)
const activeCategoryId = ref(0)
const selectionSummary = ref('')
const region = ref<Record<string, any>>({})
const serviceDate = ref('')

const getMatchLevelText = (value: string) => {
    const map: Record<string, string> = {
        district: '县区级',
        city: '市级',
        province: '省级'
    }
    return map[value] || '地区'
}

const formatPrice = (value: number) => Number(value || 0).toFixed(2)

const syncSelection = () => {
    region.value = getSelectedRegion()
    serviceDate.value = getSelectedServiceDate()
    selectionSummary.value = buildWeddingSelectionSummary()
}

const loadProviders = async () => {
    if (!activeCategoryId.value || !region.value.district_code || !serviceDate.value) {
        providerList.value = []
        return
    }

    loading.value = true
    try {
        providerList.value = await getWeddingProviderLists({
            category_id: activeCategoryId.value,
            district_code: region.value.district_code,
            service_date: serviceDate.value
        })
    } finally {
        loading.value = false
    }
}

const loadCategories = async () => {
    const categories = await getWeddingCategories()
    categoryList.value = categories || []
    if (!categoryList.value.length) {
        providerList.value = []
        return
    }

    const draft = getWeddingOrderDraft()
    const matchedCategory = categoryList.value.find((item) => item.id === draft.category_id)
    activeCategoryId.value = matchedCategory?.id || categoryList.value[0].id
    patchWeddingOrderDraft({
        category_id: activeCategoryId.value,
        provider_id: draft.provider_id,
        package_id: draft.package_id
    })
    await loadProviders()
}

const handleCategoryChange = async (categoryId: number) => {
    if (activeCategoryId.value === categoryId) {
        return
    }

    activeCategoryId.value = categoryId
    patchWeddingOrderDraft({
        category_id: categoryId,
        provider_id: 0,
        package_id: 0,
        template_form_data: {}
    })
    await loadProviders()
}

const handleOpenDetail = (providerId: number) => {
    patchWeddingOrderDraft({
        category_id: activeCategoryId.value,
        provider_id: providerId,
        package_id: 0
    })
    uni.navigateTo({
        url: `/pages/wedding_provider_detail/wedding_provider_detail?provider_id=${providerId}`
    })
}

onShow(async () => {
    syncSelection()
    await loadCategories()
})
</script>

<style lang="scss" scoped>
.wedding-provider-list-page {
    background:
        radial-gradient(circle at top right, rgba(219, 39, 119, 0.10), transparent 26%),
        linear-gradient(180deg, #fffafc, #f8f3ef 44%, #f7f3f0);
}

.hero-card,
.panel-card,
.state-card,
.provider-card {
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    border-radius: 28rpx;
    box-shadow: 0 18rpx 48rpx rgba(31, 41, 55, 0.06);
}

.hero-card,
.panel-card,
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
.panel-card__desc,
.state-card,
.hero-card__meta {
    margin-top: 16rpx;
    color: #6b7280;
    font-size: 24rpx;
    line-height: 1.8;
}

.hero-card__meta {
    color: #831843;
}

.panel-card__title {
    color: #111827;
    font-size: 30rpx;
    font-weight: 600;
}

.category-scroll {
    white-space: nowrap;
}

.category-chip {
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

.category-chip--active {
    background: linear-gradient(135deg, #db2777, #ca8a04);
    color: #ffffff;
    border-color: transparent;
}

.provider-grid {
    display: grid;
    gap: 20rpx;
}

.provider-card {
    display: flex;
    gap: 22rpx;
    padding: 24rpx;
}

.provider-card__avatar {
    width: 124rpx;
    height: 124rpx;
    border-radius: 24rpx;
    background: #f3f4f6;
    flex-shrink: 0;
}

.provider-card__content {
    flex: 1;
    min-width: 0;
}

.provider-card__top {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.provider-card__title {
    color: #111827;
    font-size: 30rpx;
    font-weight: 600;
}

.provider-card__badge {
    padding: 6rpx 14rpx;
    border-radius: 999rpx;
    background: rgba(219, 39, 119, 0.10);
    color: #9d174d;
    font-size: 20rpx;
}

.provider-card__summary {
    margin-top: 12rpx;
    color: #6b7280;
    font-size: 24rpx;
    line-height: 1.7;
}

.provider-card__foot {
    margin-top: 18rpx;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16rpx;
}

.provider-card__price {
    color: #be123c;
    font-size: 32rpx;
    font-weight: 600;
}

.provider-card__hint {
    color: #9ca3af;
    font-size: 22rpx;
    text-align: right;
    line-height: 1.6;
}
</style>
