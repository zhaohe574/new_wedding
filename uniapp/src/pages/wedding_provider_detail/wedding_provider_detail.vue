<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <view class="wedding-provider-detail-page min-h-screen px-[24rpx] py-[24rpx] box-border">
        <view class="hero-card">
            <view class="hero-card__eyebrow">Provider Detail</view>
            <view class="hero-card__title">服务人员详情与套餐选择</view>
            <view class="hero-card__desc">
                当前页面只展示所选县区与服务日期下可售套餐，未命中地区价格或当前不可约的套餐不会展示。
            </view>
            <view class="hero-card__meta">{{ selectionSummary }}</view>
        </view>

        <view v-if="loading" class="state-card mt-[24rpx]">正在加载服务人员详情...</view>
        <template v-else>
            <view class="profile-card mt-[24rpx]">
                <image class="profile-card__avatar" :src="detail.provider.avatar || defaultAvatar" mode="aspectFill" />
                <view class="profile-card__content">
                    <view class="profile-card__top">
                        <view class="profile-card__title">{{ detail.provider.name || '-' }}</view>
                        <view v-if="detail.provider.recommend" class="profile-card__badge">推荐</view>
                    </view>
                    <view class="profile-card__summary">{{ detail.provider.summary || '暂未补充服务人员简介' }}</view>
                    <view class="profile-card__meta">
                        <text>{{ detail.category.name || '未分类' }}</text>
                        <text v-if="tagSummary"> · {{ tagSummary }}</text>
                    </view>
                </view>
            </view>

            <view class="panel-card mt-[24rpx]">
                <view class="panel-card__title">档期状态</view>
                <view class="schedule-badge" :class="`schedule-badge--${detail.schedule.status || 'unknown'}`">
                    {{ detail.schedule.status_desc || '-' }}
                </view>
                <view class="panel-card__desc">服务日期：{{ selectedServiceDate }}</view>
            </view>

            <view class="panel-card mt-[24rpx]">
                <view class="panel-card__title">可售套餐</view>
                <view v-if="!detail.packages.length" class="panel-card__desc">
                    当前地区与日期下暂无可售套餐，请返回上一步调整条件。
                </view>
                <view v-else class="package-grid">
                    <view
                        v-for="item in detail.packages"
                        :key="item.package_id"
                        class="package-card"
                        :class="{ 'package-card--active': selectedPackageId === item.package_id }"
                        @click="selectedPackageId = item.package_id"
                    >
                        <view class="package-card__top">
                            <view class="package-card__title">{{ item.name }}</view>
                            <view class="package-card__price">￥{{ formatPrice(item.price) }}</view>
                        </view>
                        <view class="package-card__summary">{{ item.summary || '暂未补充套餐简介' }}</view>
                        <view class="package-card__foot">
                            <view>{{ item.service_duration || '时长待补充' }}</view>
                            <view>{{ getMatchLevelText(item.price_match_level) }}命中：{{ item.matched_region_name }}</view>
                        </view>
                    </view>
                </view>
            </view>

            <button
                class="action-btn mt-[24rpx]"
                :disabled="!detail.packages.length || detail.schedule.status !== 'available'"
                @click="handleGoTemplate"
            >
                进入服务内容填写
            </button>
        </template>
    </view>
</template>

<script setup lang="ts">
import { getWeddingProviderDetail } from '@/api/wedding'
import {
    buildWeddingSelectionSummary,
    getSelectedRegion,
    getSelectedServiceDate,
    getWeddingOrderDraft,
    patchWeddingOrderDraft
} from '@/utils/wedding'
import { onLoad } from '@dcloudio/uni-app'
import { computed, reactive, ref } from 'vue'

const defaultAvatar = '/static/images/user/default_avatar.png'
const loading = ref(false)
const providerId = ref(0)
const selectedPackageId = ref(0)
const selectedServiceDate = ref('')
const selectionSummary = ref('')
const selectedRegion = ref<Record<string, any>>({})
const detail = reactive<any>({
    provider: {},
    category: {},
    tags: [],
    schedule: {},
    packages: []
})

const tagSummary = computed(() => (detail.tags || []).map((item: any) => item.name).join('、'))

const formatPrice = (value: number) => Number(value || 0).toFixed(2)

const getMatchLevelText = (value: string) => {
    const map: Record<string, string> = {
        district: '县区级',
        city: '市级',
        province: '省级'
    }
    return map[value] || '地区'
}

const redirectToList = () => {
    uni.redirectTo({
        url: '/pages/wedding_provider_list/wedding_provider_list'
    })
}

const loadDetail = async () => {
    if (!providerId.value || !selectedRegion.value.district_code || !selectedServiceDate.value) {
        uni.showToast({ title: '请先选择地区和服务日期', icon: 'none' })
        setTimeout(() => {
            redirectToList()
        }, 180)
        return
    }

    loading.value = true
    try {
        const data = await getWeddingProviderDetail({
            provider_id: providerId.value,
            district_code: selectedRegion.value.district_code,
            service_date: selectedServiceDate.value
        })
        Object.assign(detail, data || {})
        const draft = getWeddingOrderDraft()
        if (draft.provider_id === providerId.value && detail.packages.some((item: any) => item.package_id === draft.package_id)) {
            selectedPackageId.value = draft.package_id
        } else {
            selectedPackageId.value = detail.packages[0]?.package_id || 0
        }
    } finally {
        loading.value = false
    }
}

const handleGoTemplate = () => {
    if (!selectedPackageId.value) {
        uni.showToast({ title: '请先选择套餐', icon: 'none' })
        return
    }

    patchWeddingOrderDraft({
        category_id: Number(detail.category.id || 0),
        provider_id: providerId.value,
        package_id: selectedPackageId.value
    })
    uni.navigateTo({
        url: '/pages/wedding_template_form/wedding_template_form'
    })
}

onLoad(async (options) => {
    providerId.value = Number(options?.provider_id || 0)
    selectedServiceDate.value = getSelectedServiceDate()
    selectedRegion.value = getSelectedRegion()
    selectionSummary.value = buildWeddingSelectionSummary()
    if (!providerId.value) {
        uni.showToast({ title: '服务人员参数缺失', icon: 'none' })
        setTimeout(() => {
            redirectToList()
        }, 180)
        return
    }
    await loadDetail()
})
</script>

<style lang="scss" scoped>
.wedding-provider-detail-page {
    background:
        radial-gradient(circle at top right, rgba(219, 39, 119, 0.10), transparent 26%),
        linear-gradient(180deg, #fffafc, #f8f3ef 44%, #f7f3f0);
}

.hero-card,
.profile-card,
.panel-card,
.state-card,
.package-card {
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
.hero-card__meta,
.panel-card__desc,
.state-card {
    margin-top: 16rpx;
    color: #6b7280;
    font-size: 24rpx;
    line-height: 1.8;
}

.hero-card__meta {
    color: #831843;
}

.profile-card {
    display: flex;
    gap: 22rpx;
    padding: 24rpx;
}

.profile-card__avatar {
    width: 130rpx;
    height: 130rpx;
    border-radius: 24rpx;
    background: #f3f4f6;
    flex-shrink: 0;
}

.profile-card__content {
    flex: 1;
    min-width: 0;
}

.profile-card__top {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.profile-card__title {
    color: #111827;
    font-size: 32rpx;
    font-weight: 600;
}

.profile-card__badge {
    padding: 6rpx 14rpx;
    border-radius: 999rpx;
    background: rgba(219, 39, 119, 0.10);
    color: #9d174d;
    font-size: 20rpx;
}

.profile-card__summary,
.profile-card__meta {
    margin-top: 12rpx;
    color: #6b7280;
    font-size: 24rpx;
    line-height: 1.7;
}

.panel-card__title {
    color: #111827;
    font-size: 30rpx;
    font-weight: 600;
}

.schedule-badge {
    display: inline-flex;
    margin-top: 18rpx;
    padding: 10rpx 18rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 600;
}

.schedule-badge--available {
    background: rgba(16, 185, 129, 0.12);
    color: #047857;
}

.schedule-badge--locked,
.schedule-badge--occupied,
.schedule-badge--unavailable,
.schedule-badge--unknown {
    background: rgba(190, 24, 93, 0.12);
    color: #9d174d;
}

.package-grid {
    display: grid;
    gap: 18rpx;
    margin-top: 20rpx;
}

.package-card {
    padding: 24rpx;
}

.package-card--active {
    border-color: rgba(202, 138, 4, 0.3);
    box-shadow: 0 16rpx 36rpx rgba(202, 138, 4, 0.14);
}

.package-card__top,
.package-card__foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.package-card__title {
    color: #111827;
    font-size: 28rpx;
    font-weight: 600;
}

.package-card__price {
    color: #be123c;
    font-size: 30rpx;
    font-weight: 600;
}

.package-card__summary {
    margin-top: 10rpx;
    color: #6b7280;
    font-size: 24rpx;
    line-height: 1.7;
}

.package-card__foot {
    margin-top: 14rpx;
    color: #9ca3af;
    font-size: 22rpx;
}

.action-btn {
    width: 100%;
    border-radius: 999rpx;
    background: linear-gradient(135deg, #db2777, #ca8a04);
    color: #ffffff;
    font-size: 28rpx;
    font-weight: 600;
    box-shadow: 0 20rpx 40rpx rgba(219, 39, 119, 0.18);
}

.action-btn[disabled] {
    opacity: 0.45;
}
</style>
