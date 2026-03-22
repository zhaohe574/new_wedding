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
                    <view class="detail-row">
                        <view class="detail-row__label">支付方式</view>
                        <view class="detail-row__value">{{ detail.order.payment_type_desc || '-' }}</view>
                    </view>
                    <view class="detail-row">
                        <view class="detail-row__label">支付状态</view>
                        <view class="detail-row__value">{{ detail.order.pay_status_desc || '-' }}</view>
                    </view>
                </view>
                <view class="order-amount">￥{{ Number(detail.order.order_amount || 0).toFixed(2) }}</view>
            </view>

            <view class="panel-card mt-[24rpx]">
                <view class="panel-card__title">最近改期申请</view>
                <view v-if="!detail.latest_change?.id" class="panel-card__desc">当前暂未收到改期申请。</view>
                <template v-else>
                    <view class="detail-list">
                        <view class="detail-row">
                            <view class="detail-row__label">处理状态</view>
                            <view class="detail-row__value">{{ detail.latest_change.status_desc || '-' }}</view>
                        </view>
                        <view class="detail-row">
                            <view class="detail-row__label">原日期</view>
                            <view class="detail-row__value">{{ detail.latest_change.old_service_date || '-' }}</view>
                        </view>
                        <view class="detail-row">
                            <view class="detail-row__label">新日期</view>
                            <view class="detail-row__value">{{ detail.latest_change.new_service_date || '-' }}</view>
                        </view>
                        <view class="detail-row">
                            <view class="detail-row__label">申请原因</view>
                            <view class="detail-row__value">{{ detail.latest_change.apply_reason || '-' }}</view>
                        </view>
                        <view class="detail-row">
                            <view class="detail-row__label">处理备注</view>
                            <view class="detail-row__value">{{ detail.latest_change.handle_remark || '-' }}</view>
                        </view>
                    </view>
                </template>
            </view>

            <view class="panel-card mt-[24rpx]">
                <view class="panel-card__title">退款进度</view>
                <view v-if="!detail.latest_refund?.id" class="panel-card__desc">当前暂无退款记录。</view>
                <template v-else>
                    <view class="detail-list">
                        <view class="detail-row">
                            <view class="detail-row__label">处理状态</view>
                            <view class="detail-row__value">{{ detail.latest_refund.status_desc || '-' }}</view>
                        </view>
                        <view class="detail-row">
                            <view class="detail-row__label">退款金额</view>
                            <view class="detail-row__value">￥{{ Number(detail.latest_refund.refund_amount || 0).toFixed(2) }}</view>
                        </view>
                        <view class="detail-row">
                            <view class="detail-row__label">申请原因</view>
                            <view class="detail-row__value">{{ detail.latest_refund.apply_reason || '-' }}</view>
                        </view>
                        <view class="detail-row">
                            <view class="detail-row__label">处理备注</view>
                            <view class="detail-row__value">{{ detail.latest_refund.handle_remark || '-' }}</view>
                        </view>
                    </view>
                </template>
            </view>

            <view class="panel-card mt-[24rpx]">
                <view class="panel-card__title">订单评价</view>
                <view v-if="!detail.review?.id" class="panel-card__desc">当前暂未收到评价记录。</view>
                <template v-else>
                    <view class="detail-list">
                        <view class="detail-row">
                            <view class="detail-row__label">评分</view>
                            <view class="detail-row__value">{{ Number(detail.review.score || 0) }} 分</view>
                        </view>
                        <view class="detail-row">
                            <view class="detail-row__label">审核状态</view>
                            <view class="detail-row__value">{{ detail.review.audit_status_desc || '-' }}</view>
                        </view>
                        <view class="detail-row">
                            <view class="detail-row__label">审核归属</view>
                            <view class="detail-row__value">{{ detail.review.audit_role_desc || '-' }}</view>
                        </view>
                        <view class="detail-row">
                            <view class="detail-row__label">评价内容</view>
                            <view class="detail-row__value">{{ detail.review.content || '-' }}</view>
                        </view>
                    </view>
                    <view v-if="detail.review.images?.length" class="voucher-grid mt-[16rpx]">
                        <image
                            v-for="(item, index) in detail.review.images"
                            :key="index"
                            class="voucher-grid__item"
                            :src="item"
                            mode="aspectFill"
                        />
                    </view>
                </template>
            </view>

            <view class="panel-card mt-[24rpx]">
                <view class="panel-card__title">线下凭证</view>
                <view v-if="!detail.offline_voucher?.id" class="panel-card__desc">当前暂无线下凭证记录。</view>
                <template v-else>
                    <view class="detail-list">
                        <view class="detail-row">
                            <view class="detail-row__label">审核状态</view>
                            <view class="detail-row__value">{{ detail.offline_voucher.audit_status_desc || '-' }}</view>
                        </view>
                        <view class="detail-row">
                            <view class="detail-row__label">提交说明</view>
                            <view class="detail-row__value">{{ detail.offline_voucher.remark || '-' }}</view>
                        </view>
                    </view>
                    <view class="voucher-grid mt-[16rpx]">
                        <image
                            v-for="(item, index) in detail.offline_voucher.voucher_images || []"
                            :key="index"
                            class="voucher-grid__item"
                            :src="item"
                            mode="aspectFill"
                        />
                    </view>
                </template>
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
                <button v-if="detail.action.can_handle_reschedule" class="action-btn" @click="openRescheduleHandlePopup">
                    处理改期申请
                </button>
                <button v-if="detail.action.can_complete_service" class="action-btn" :loading="serviceCompleting" @click="handleCompleteService">
                    完成履约
                </button>
                <button v-if="detail.action.can_audit_review" class="action-btn" @click="openReviewAuditPopup">
                    审核评价
                </button>
                <button class="ghost-btn" @click="goWorkbench">返回订单工作台</button>
            </view>
        </template>

        <u-popup v-model="rejectPopupVisible" mode="bottom" border-radius="18" safe-area-inset-bottom>
            <view class="popup-panel">
                <view class="popup-panel__title">填写拒单原因</view>
                <textarea
                    v-model="rejectReason"
                    class="popup-panel__textarea"
                    maxlength="500"
                    placeholder="请填写拒单原因，最多500字"
                />
                <button class="action-btn" :loading="rejecting" @click="handleReject">确认拒单</button>
            </view>
        </u-popup>

        <u-popup v-model="reschedulePopupVisible" mode="bottom" border-radius="18" safe-area-inset-bottom>
            <view class="popup-panel">
                <view class="popup-panel__title">处理改期申请</view>
                <view class="popup-panel__desc">
                    {{ detail.latest_change?.old_service_date || '-' }} -> {{ detail.latest_change?.new_service_date || '-' }}
                </view>
                <view class="radio-grid">
                    <view
                        class="radio-card"
                        :class="{ 'radio-card--active': rescheduleForm.audit_status === 1 }"
                        @click="rescheduleForm.audit_status = 1"
                    >
                        通过
                    </view>
                    <view
                        class="radio-card"
                        :class="{ 'radio-card--active': rescheduleForm.audit_status === 2 }"
                        @click="rescheduleForm.audit_status = 2"
                    >
                        驳回
                    </view>
                </view>
                <textarea
                    v-model="rescheduleForm.audit_remark"
                    class="popup-panel__textarea"
                    maxlength="500"
                    placeholder="请填写处理说明"
                />
                <button class="action-btn" :loading="rescheduleSubmitting" @click="handleReschedule">确认处理</button>
            </view>
        </u-popup>

        <u-popup v-model="reviewAuditPopupVisible" mode="bottom" border-radius="18" safe-area-inset-bottom>
            <view class="popup-panel">
                <view class="popup-panel__title">审核用户评价</view>
                <view class="popup-panel__desc">当前评分：{{ Number(detail.review?.score || 0) }} 分</view>
                <view class="radio-grid">
                    <view
                        class="radio-card"
                        :class="{ 'radio-card--active': reviewAuditForm.audit_status === 1 }"
                        @click="reviewAuditForm.audit_status = 1"
                    >
                        通过
                    </view>
                    <view
                        class="radio-card"
                        :class="{ 'radio-card--active': reviewAuditForm.audit_status === 2 }"
                        @click="reviewAuditForm.audit_status = 2"
                    >
                        驳回
                    </view>
                </view>
                <textarea
                    v-model="reviewAuditForm.audit_remark"
                    class="popup-panel__textarea"
                    maxlength="500"
                    placeholder="请填写审核说明"
                />
                <button class="action-btn" :loading="reviewAuditing" @click="handleReviewAudit">确认审核</button>
            </view>
        </u-popup>
    </view>
</template>

<script setup lang="ts">
import {
    acceptProviderOrder,
    auditProviderOrderReview,
    completeProviderOrderService,
    getProviderOrderDetail,
    handleProviderOrderReschedule,
    rejectProviderOrder
} from '@/api/wedding'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { computed, reactive, ref } from 'vue'

const loading = ref(false)
const accepting = ref(false)
const rejecting = ref(false)
const rescheduleSubmitting = ref(false)
const serviceCompleting = ref(false)
const reviewAuditing = ref(false)
const orderId = ref(0)
const rejectPopupVisible = ref(false)
const reschedulePopupVisible = ref(false)
const reviewAuditPopupVisible = ref(false)
const rejectReason = ref('')
const detail = reactive<any>({
    order: {},
    snapshot: {},
    offline_voucher: {},
    latest_change: {},
    latest_refund: {},
    review: {},
    action: {}
})

const rescheduleForm = reactive({
    change_id: 0,
    audit_status: 1,
    audit_remark: ''
})

const reviewAuditForm = reactive({
    order_id: 0,
    audit_status: 1,
    audit_remark: ''
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

const openRescheduleHandlePopup = () => {
    rescheduleForm.change_id = Number(detail.latest_change?.id || 0)
    rescheduleForm.audit_status = 1
    rescheduleForm.audit_remark = ''
    reschedulePopupVisible.value = true
}

const handleReschedule = async () => {
    if (!rescheduleForm.change_id) {
        uni.showToast({ title: '改期申请不存在', icon: 'none' })
        return
    }
    if (!rescheduleForm.audit_remark.trim()) {
        uni.showToast({ title: '请填写处理说明', icon: 'none' })
        return
    }
    rescheduleSubmitting.value = true
    try {
        await handleProviderOrderReschedule({
            change_id: rescheduleForm.change_id,
            audit_status: rescheduleForm.audit_status,
            audit_remark: rescheduleForm.audit_remark
        })
        reschedulePopupVisible.value = false
        await loadDetail()
    } finally {
        rescheduleSubmitting.value = false
    }
}

const handleCompleteService = async () => {
    const modalResult = await uni.showModal({
        title: '确认已完成履约？',
        content: '完成后订单会进入待评价或已完成状态，请确认服务已实际完成。'
    })
    if (!modalResult.confirm) {
        return
    }
    serviceCompleting.value = true
    try {
        await completeProviderOrderService({ order_id: orderId.value })
        await loadDetail()
    } finally {
        serviceCompleting.value = false
    }
}

const openReviewAuditPopup = () => {
    reviewAuditForm.order_id = orderId.value
    reviewAuditForm.audit_status = 1
    reviewAuditForm.audit_remark = ''
    reviewAuditPopupVisible.value = true
}

const handleReviewAudit = async () => {
    if (!reviewAuditForm.audit_remark.trim()) {
        uni.showToast({ title: '请填写审核说明', icon: 'none' })
        return
    }
    reviewAuditing.value = true
    try {
        await auditProviderOrderReview({
            order_id: reviewAuditForm.order_id,
            audit_status: reviewAuditForm.audit_status,
            audit_remark: reviewAuditForm.audit_remark
        })
        reviewAuditPopupVisible.value = false
        await loadDetail()
    } finally {
        reviewAuditing.value = false
    }
}

const goWorkbench = () => {
    uni.redirectTo({
        url: '/pages/provider_order_pending/provider_order_pending'
    })
}

onLoad(async (options) => {
    orderId.value = Number(options?.order_id || 0)
    await loadDetail()
})

onShow(async () => {
    if (orderId.value) {
        await loadDetail()
    }
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
.state-card,
.popup-panel__desc {
    margin-top: 16rpx;
    color: #6b7280;
    font-size: 24rpx;
    line-height: 1.8;
}

.panel-card__title,
.template-page-card__title,
.popup-panel__title {
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

.voucher-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16rpx;
}

.voucher-grid__item {
    width: 100%;
    height: 190rpx;
    border-radius: 20rpx;
    overflow: hidden;
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

.popup-panel {
    padding: 30rpx;
}

.popup-panel__textarea {
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

.radio-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16rpx;
    margin-top: 20rpx;
}

.radio-card {
    padding: 22rpx 0;
    border-radius: 18rpx;
    text-align: center;
    font-size: 26rpx;
    color: #6b7280;
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    background: rgba(255, 255, 255, 0.92);
}

.radio-card--active {
    color: #9d174d;
    border-color: rgba(219, 39, 119, 0.28);
    background: rgba(253, 242, 248, 0.95);
}
</style>
