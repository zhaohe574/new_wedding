<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <view class="wedding-order-preview-page min-h-screen px-[24rpx] py-[24rpx] box-border">
        <view class="hero-card">
            <view class="hero-card__eyebrow">Order Preview</view>
            <view class="hero-card__title">订单预览</view>
            <view class="hero-card__desc">
                当前价格与命中层级全部来自服务端重算结果。本批仅提供只读预览，不执行真实创建订单。
            </view>
        </view>

        <view v-if="loading" class="state-card mt-[24rpx]">正在生成订单预览...</view>
        <template v-else>
            <view class="panel-card mt-[24rpx]">
                <view class="panel-card__title">预订摘要</view>
                <view class="summary-grid">
                    <view class="summary-item">
                        <view class="summary-item__label">服务人员</view>
                        <view class="summary-item__value">{{ preview.provider.name || '-' }}</view>
                    </view>
                    <view class="summary-item">
                        <view class="summary-item__label">套餐</view>
                        <view class="summary-item__value">{{ preview.package.name || '-' }}</view>
                    </view>
                    <view class="summary-item">
                        <view class="summary-item__label">服务日期</view>
                        <view class="summary-item__value">{{ preview.service_date || '-' }}</view>
                    </view>
                    <view class="summary-item">
                        <view class="summary-item__label">最终价格</view>
                        <view class="summary-item__value summary-item__value--price">￥{{ formatPrice(preview.pricing.price) }}</view>
                    </view>
                </view>
            </view>

            <view class="panel-card mt-[24rpx]">
                <view class="panel-card__title">地区与价格命中</view>
                <view class="detail-list">
                    <view class="detail-row">
                        <view class="detail-row__label">服务地区</view>
                        <view class="detail-row__value">{{ regionSummary }}</view>
                    </view>
                    <view class="detail-row">
                        <view class="detail-row__label">命中层级</view>
                        <view class="detail-row__value">{{ getMatchLevelText(preview.pricing.price_match_level) }}</view>
                    </view>
                    <view class="detail-row">
                        <view class="detail-row__label">命中地区</view>
                        <view class="detail-row__value">{{ preview.pricing.matched_region_name || '-' }}</view>
                    </view>
                </view>
            </view>

            <view class="panel-card mt-[24rpx]">
                <view class="panel-card__title">婚礼基础档案摘要</view>
                <view class="detail-list">
                    <view class="detail-row">
                        <view class="detail-row__label">婚礼日期</view>
                        <view class="detail-row__value">{{ preview.profile_summary.wedding_date || '-' }}</view>
                    </view>
                    <view class="detail-row">
                        <view class="detail-row__label">宴会场地</view>
                        <view class="detail-row__value">{{ preview.profile_summary.banquet_hotel || '-' }}</view>
                    </view>
                    <view class="detail-row">
                        <view class="detail-row__label">预算范围</view>
                        <view class="detail-row__value">
                            ￥{{ formatPrice(preview.profile_summary.budget_min) }} - ￥{{ formatPrice(preview.profile_summary.budget_max) }}
                        </view>
                    </view>
                    <view class="detail-row">
                        <view class="detail-row__label">联系人</view>
                        <view class="detail-row__value">
                            {{ preview.profile_summary.contact_name || '-' }} / {{ preview.profile_summary.contact_mobile || '-' }}
                        </view>
                    </view>
                </view>
            </view>

            <view class="panel-card mt-[24rpx]">
                <view class="panel-card__title">服务内容摘要</view>
                <view v-if="!(preview.template_summary.pages || []).length" class="panel-card__desc">暂无可展示的模板填写结果。</view>
                <view v-else class="template-page-list">
                    <view v-for="(page, pageIndex) in preview.template_summary.pages" :key="pageIndex" class="template-page-card">
                        <view class="template-page-card__title">{{ page.title || `第 ${pageIndex + 1} 页` }}</view>
                        <view v-if="page.description" class="template-page-card__desc">{{ page.description }}</view>
                        <view class="detail-list">
                            <view v-for="field in page.fields || []" :key="field.field_key" class="detail-row">
                                <view class="detail-row__label">{{ field.label }}</view>
                                <view class="detail-row__value">{{ field.display_value || '-' }}</view>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <view class="footer-note mt-[24rpx]">
                当前仅开放订单预览，创建订单、锁档写库与订单快照持久化将在下一批接入。
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
import { previewWeddingOrder } from '@/api/wedding'
import { getSelectedRegion, getSelectedServiceDate, getWeddingOrderDraft } from '@/utils/wedding'
import { onLoad } from '@dcloudio/uni-app'
import { computed, reactive, ref } from 'vue'

const loading = ref(false)
const preview = reactive<any>({
    provider: {},
    package: {},
    pricing: {},
    region: {},
    profile_summary: {},
    template_summary: {
        pages: []
    }
})

const regionSummary = computed(() =>
    [preview.region.province_name, preview.region.city_name, preview.region.district_name].filter(Boolean).join(' / ')
)

const formatPrice = (value: number) => Number(value || 0).toFixed(2)

const getMatchLevelText = (value: string) => {
    const map: Record<string, string> = {
        district: '县区级命中',
        city: '市级命中',
        province: '省级命中'
    }
    return map[value] || '-'
}

const redirectBack = () => {
    const draft = getWeddingOrderDraft()
    if (draft.provider_id) {
        uni.redirectTo({
            url: `/pages/wedding_provider_detail/wedding_provider_detail?provider_id=${draft.provider_id}`
        })
        return
    }

    uni.redirectTo({
        url: '/pages/wedding_provider_list/wedding_provider_list'
    })
}

const loadPreview = async () => {
    const draft = getWeddingOrderDraft()
    const selectedRegion = getSelectedRegion()
    const selectedServiceDate = getSelectedServiceDate()
    if (!draft.provider_id || !draft.package_id || !draft.category_id) {
        uni.showToast({ title: '请先完成套餐与模板填写', icon: 'none' })
        setTimeout(() => {
            redirectBack()
        }, 180)
        return
    }

    loading.value = true
    try {
        const data = await previewWeddingOrder({
            provider_id: draft.provider_id,
            package_id: draft.package_id,
            district_code: selectedRegion.district_code,
            service_date: selectedServiceDate,
            template_form_data: draft.template_form_data
        })
        Object.assign(preview, data || {})
    } catch (error) {
        setTimeout(() => {
            redirectBack()
        }, 180)
    } finally {
        loading.value = false
    }
}

onLoad(async () => {
    await loadPreview()
})
</script>

<style lang="scss" scoped>
.wedding-order-preview-page {
    background:
        radial-gradient(circle at top right, rgba(219, 39, 119, 0.10), transparent 26%),
        linear-gradient(180deg, #fffafc, #f8f3ef 44%, #f7f3f0);
}

.hero-card,
.panel-card,
.state-card,
.template-page-card {
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
.footer-note {
    margin-top: 16rpx;
    color: #6b7280;
    font-size: 24rpx;
    line-height: 1.8;
}

.panel-card__title,
.template-page-card__title {
    color: #111827;
    font-size: 30rpx;
    font-weight: 600;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16rpx;
    margin-top: 22rpx;
}

.summary-item {
    padding: 22rpx;
    border-radius: 22rpx;
    background: linear-gradient(180deg, #fff9fb, #fffdf9);
    border: 1rpx solid rgba(202, 138, 4, 0.14);
}

.summary-item__label {
    color: #9ca3af;
    font-size: 22rpx;
}

.summary-item__value {
    margin-top: 12rpx;
    color: #111827;
    font-size: 28rpx;
    font-weight: 600;
    line-height: 1.6;
}

.summary-item__value--price {
    color: #be123c;
}

.detail-list {
    display: grid;
    gap: 18rpx;
    margin-top: 22rpx;
}

.detail-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20rpx;
}

.detail-row__label {
    color: #9ca3af;
    font-size: 24rpx;
    flex-shrink: 0;
}

.detail-row__value {
    color: #111827;
    font-size: 24rpx;
    line-height: 1.7;
    text-align: right;
}

.template-page-list {
    display: grid;
    gap: 18rpx;
    margin-top: 20rpx;
}

.template-page-card {
    padding: 24rpx;
}

.template-page-card__desc {
    margin-top: 10rpx;
    color: #6b7280;
    font-size: 22rpx;
}

.footer-note {
    padding: 22rpx 24rpx;
    border-radius: 22rpx;
    border: 1rpx dashed rgba(202, 138, 4, 0.22);
    background: rgba(255, 255, 255, 0.84);
}
</style>
