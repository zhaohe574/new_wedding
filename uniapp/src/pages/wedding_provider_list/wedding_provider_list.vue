<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <w-page-nav />
    <view class="wedding-provider-list-page min-h-screen px-[24rpx] py-[24rpx] box-border">
        <view class="hero-card">
            <view class="hero-card__eyebrow">Provider List</view>
            <view class="hero-card__title">服务人员</view>
            <view class="hero-card__meta">{{ selectionSummary || '请选择地区与服务日期后查看结果' }}</view>
        </view>

        <view class="panel-card mt-[24rpx]">
            <view class="panel-card__head">
                <view>
                    <view class="panel-card__title">筛选结果</view>
                    <view class="panel-card__meta">共 {{ providerList.length }} 位可预约服务人员</view>
                </view>
                <button class="ghost-btn ghost-btn--sm" @click="handleAdjustFilter">调整筛选</button>
            </view>
            <view class="filter-meta">{{ filterSummaryText }}</view>
        </view>

        <scroll-view class="category-scroll mt-[24rpx]" scroll-x enable-flex>
            <view
                v-for="item in categoryList"
                :key="item.id"
                class="category-chip"
                :class="{ 'category-chip--active': tradeQuery.category_id === item.id }"
                @click="handleCategoryChange(item.id)"
            >
                {{ item.name }}
            </view>
        </scroll-view>

        <view v-if="loading" class="state-card mt-[24rpx]">正在加载服务人员列表...</view>
        <view v-else-if="!providerList.length" class="state-card mt-[24rpx]">
            当前筛选下暂无可预约服务人员，试试调整分类、风格或价格排序。
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
    buildWeddingProviderListParams,
    buildWeddingSelectionSummary,
    buildWeddingTradeQueryUrl,
    normalizeWeddingTradeQuery,
    patchWeddingOrderDraft,
    type WeddingTradeQuery
} from '@/utils/wedding'
import { onLoad } from '@dcloudio/uni-app'
import { computed, ref } from 'vue'

const defaultAvatar = '/static/images/user/default_avatar.png'
const categoryList = ref<any[]>([])
const providerList = ref<any[]>([])
const loading = ref(false)
const tradeQuery = ref<WeddingTradeQuery>(normalizeWeddingTradeQuery())

const selectionSummary = computed(() => buildWeddingSelectionSummary(tradeQuery.value))
const activeCategoryName = computed(
    () => categoryList.value.find((item) => item.id === tradeQuery.value.category_id)?.name || '未选择分类'
)
const filterSummaryText = computed(() => {
    const parts = [`分类：${activeCategoryName.value}`]
    if (tradeQuery.value.tag_ids.length) {
        parts.push(`风格：${tradeQuery.value.tag_ids.length} 项`)
    }
    if (tradeQuery.value.keyword) {
        parts.push(`关键词：${tradeQuery.value.keyword}`)
    }
    if (tradeQuery.value.price_sort === 'asc') {
        parts.push('价格升序')
    } else if (tradeQuery.value.price_sort === 'desc') {
        parts.push('价格降序')
    } else {
        parts.push('默认推荐')
    }
    return parts.join(' · ')
})

const getMatchLevelText = (value: string) => {
    const map: Record<string, string> = {
        district: '县区级',
        city: '市级',
        province: '省级'
    }
    return map[value] || '地区'
}

const formatPrice = (value: number) => Number(value || 0).toFixed(2)

const loadProviders = async () => {
    if (!tradeQuery.value.category_id || !tradeQuery.value.district_code || !tradeQuery.value.service_date) {
        providerList.value = []
        return
    }

    loading.value = true
    try {
        providerList.value = await getWeddingProviderLists(buildWeddingProviderListParams(tradeQuery.value))
    } finally {
        loading.value = false
    }
}

const loadCategories = async () => {
    categoryList.value = (await getWeddingCategories()) || []
    if (!categoryList.value.length) {
        providerList.value = []
        return
    }

    const matchedCategory = categoryList.value.find((item) => item.id === tradeQuery.value.category_id)
    tradeQuery.value = {
        ...tradeQuery.value,
        category_id: matchedCategory?.id || categoryList.value[0].id
    }
}

const handleCategoryChange = async (categoryId: number) => {
    if (tradeQuery.value.category_id === categoryId) {
        return
    }

    tradeQuery.value = {
        ...tradeQuery.value,
        category_id: categoryId
    }
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
        category_id: tradeQuery.value.category_id,
        provider_id: providerId,
        package_id: 0
    })
    uni.navigateTo({
        url: buildWeddingTradeQueryUrl(`/pages/wedding_provider_detail/wedding_provider_detail?provider_id=${providerId}`, tradeQuery.value)
    })
}

const handleAdjustFilter = () => {
    uni.navigateTo({
        url: buildWeddingTradeQueryUrl('/pages/wedding_region/wedding_region', tradeQuery.value)
    })
}

onLoad(async (options) => {
    tradeQuery.value = normalizeWeddingTradeQuery((options || {}) as Record<string, any>)
    await loadCategories()
    patchWeddingOrderDraft({
        category_id: tradeQuery.value.category_id,
        provider_id: 0,
        package_id: 0,
        template_form_data: {}
    })
    await loadProviders()
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

.state-card,
.hero-card__meta,
.panel-card__meta {
    margin-top: 16rpx;
    color: #6b7280;
    font-size: 24rpx;
    line-height: 1.8;
}

.hero-card__meta {
    color: #831843;
}

.panel-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20rpx;
}

.panel-card__title {
    color: #111827;
    font-size: 30rpx;
    font-weight: 600;
}

.filter-meta {
    margin-top: 18rpx;
    color: #9ca3af;
    font-size: 22rpx;
    line-height: 1.7;
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

.ghost-btn {
    min-width: 164rpx;
    height: 72rpx;
    padding: 0 22rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    color: #374151;
    font-size: 24rpx;
}

.ghost-btn--sm {
    margin: 0;
}
</style>
