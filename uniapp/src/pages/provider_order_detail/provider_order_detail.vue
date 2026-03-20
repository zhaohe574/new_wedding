<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <view class="provider-order-detail-page min-h-screen px-[24rpx] py-[24rpx] box-border">
        <view v-if="loading" class="state-card">正在加载订单详情...</view>
        <template v-else>
            <view class="hero-card">
                <view class="hero-card__eyebrow">Provider Handle</view>
                <view class="hero-card__title">订单状态：{{ detail.order.order_status_desc || '-' }}</view>
                <view class="hero-card__desc">订单号：{{ detail.order.sn || '-' }}</view>
            </view>

            <view class="panel-card mt-[24rpx]">
                <view class="panel-card__title">订单信息</view>
                <view class="detail-list">
                    <view class="detail-row">
                        <view class="detail-row__label">用户</view>
                        <view class="detail-row__value">{{ detail.order.user_nickname || '-' }} / {{ detail.order.user_mobile || '-' }}</view>
                    </view>
                    <view class="detail-row">
                        <view class="detail-row__label">套餐</view>
                        <view class="detail-row__value">{{ detail.order.package_name || '-' }}</view>
                    </view>
                    <view class="detail-row">
                        <view class="detail-row__label">服务日期</view>
                        <view class="detail-row__value">{{ detail.order.service_date || '-' }}</view>
                    </view>
                    <view class="detail-row">
                        <view class="detail-row__label">地区</view>
                        <view class="detail-row__value">
                            {{ [detail.order.province_name, detail.order.city_name, detail.order.district_name].filter(Boolean).join(' / ') || '-' }}
                        </view>
                    </view>
                </view>
                <view class="order-amount">￥{{ Number(detail.order.order_amount || 0).toFixed(2) }}</view>
            </view>

            <view class="panel-card mt-[24rpx]">
                <view class="panel-card__title">服务内容快照</view>
                <view v-if="!templatePages.length" class="panel-card__desc">暂无模板快照信息。</view>
                <view v-else class="template-page-list">
                    <view v-for="(page, pageIndex) in templatePages" :key="pageIndex" class="template-page-card">
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

            <view class="action-grid mt-[24rpx]">
                <button
                    v-if="Number(detail.order.order_status) === 10"
                    class="action-btn"
                    :loading="accepting"
                    @click="handleAccept"
                >
                    确认接单
                </button>
                <button
                    v-if="Number(detail.order.order_status) === 10"
                    class="ghost-btn"
                    @click="rejectPopupVisible = true"
                >
                    拒绝接单
                </button>
                <button class="ghost-btn" @click="goPendingList">返回待确认列表</button>
            </view>
        </template>

        <u-popup v-model="rejectPopupVisible" mode="bottom" border-radius="18" safe-area-inset-bottom>
            <view class="reject-popup">
                <view class="reject-popup__title">填写拒单原因</view>
                <textarea
                    v-model="rejectReason"
                    class="reject-popup__textarea"
                    maxlength="500"
                    placeholder="请填写拒单原因，最多500字"
                />
                <button class="action-btn" :loading="rejecting" @click="handleReject">确认拒单</button>
            </view>
        </u-popup>
    </view>
</template>

<script setup lang="ts">
import { acceptProviderOrder, getProviderOrderDetail, rejectProviderOrder } from '@/api/wedding'
import { onLoad } from '@dcloudio/uni-app'
import { computed, reactive, ref } from 'vue'

const loading = ref(false)
const accepting = ref(false)
const rejecting = ref(false)
const orderId = ref(0)
const rejectPopupVisible = ref(false)
const rejectReason = ref('')
const detail = reactive<any>({
    order: {},
    snapshot: {},
    offline_voucher: {},
    action: {}
})

const templatePages = computed(() => detail.snapshot?.template_snapshot?.pages || [])

const loadDetail = async () => {
    if (!orderId.value) {
        return
    }
    loading.value = true
    try {
        const data = await getProviderOrderDetail({ order_id: orderId.value })
        Object.assign(detail, data || {})
    } finally {
        loading.value = false
    }
}

const handleAccept = async () => {
    accepting.value = true
    try {
        await acceptProviderOrder({ order_id: orderId.value })
        await loadDetail()
    } finally {
        accepting.value = false
    }
}

const handleReject = async () => {
    if (!rejectReason.value.trim()) {
        uni.showToast({ title: '请填写拒单原因', icon: 'none' })
        return
    }
    rejecting.value = true
    try {
        await rejectProviderOrder({
            order_id: orderId.value,
            reject_reason: rejectReason.value
        })
        rejectPopupVisible.value = false
        rejectReason.value = ''
        await loadDetail()
    } finally {
        rejecting.value = false
    }
}

const goPendingList = () => {
    uni.redirectTo({
        url: '/pages/provider_order_pending/provider_order_pending'
    })
}

onLoad(async (options) => {
    orderId.value = Number(options?.order_id || 0)
    await loadDetail()
})
</script>

<style lang="scss" scoped>
.provider-order-detail-page {
    background:
        radial-gradient(circle at top right, rgba(219, 39, 119, 0.1), transparent 26%),
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
    font-size: 38rpx;
    font-weight: 600;
}

.hero-card__desc,
.panel-card__desc,
.state-card {
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

.detail-list {
    display: grid;
    gap: 16rpx;
    margin-top: 20rpx;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    gap: 20rpx;
}

.detail-row__label {
    color: #9ca3af;
    font-size: 24rpx;
}

.detail-row__value {
    color: #111827;
    font-size: 24rpx;
    text-align: right;
    line-height: 1.7;
}

.order-amount {
    margin-top: 16rpx;
    color: #be123c;
    font-size: 40rpx;
    font-weight: 600;
}

.template-page-list {
    display: grid;
    gap: 16rpx;
    margin-top: 20rpx;
}

.template-page-card {
    padding: 20rpx;
}

.template-page-card__desc {
    margin-top: 8rpx;
    color: #6b7280;
    font-size: 22rpx;
}

.action-grid {
    display: grid;
    gap: 16rpx;
}

.action-btn,
.ghost-btn {
    width: 100%;
    border-radius: 999rpx;
    font-size: 28rpx;
    font-weight: 600;
}

.action-btn {
    background: linear-gradient(135deg, #db2777, #ca8a04);
    color: #ffffff;
    box-shadow: 0 20rpx 40rpx rgba(219, 39, 119, 0.18);
}

.ghost-btn {
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(219, 39, 119, 0.14);
    color: #6b7280;
}

.reject-popup {
    padding: 30rpx;
}

.reject-popup__title {
    color: #111827;
    font-size: 30rpx;
    font-weight: 600;
}

.reject-popup__textarea {
    margin-top: 20rpx;
    width: 100%;
    min-height: 180rpx;
    box-sizing: border-box;
    padding: 18rpx 20rpx;
    border-radius: 18rpx;
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    background: #fff;
    font-size: 24rpx;
    color: #111827;
}
</style>

