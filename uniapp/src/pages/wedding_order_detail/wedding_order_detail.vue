<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <view class="wedding-order-detail-page min-h-screen px-[24rpx] py-[24rpx] box-border">
        <view v-if="loading" class="state-card">正在加载订单详情...</view>
        <template v-else>
            <view class="hero-card">
                <view class="hero-card__eyebrow">Order Detail</view>
                <view class="hero-card__title">订单状态：{{ detail.order.order_status_desc || '-' }}</view>
                <view class="hero-card__desc">订单号：{{ detail.order.sn || '-' }}</view>
            </view>

            <view class="panel-card mt-[24rpx]">
                <view class="panel-card__title">基础信息</view>
                <view class="detail-list">
                    <view class="detail-row">
                        <view class="detail-row__label">服务人员</view>
                        <view class="detail-row__value">{{ detail.order.provider_name || '-' }}</view>
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
                        <view class="detail-row__label">服务地区</view>
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
                <view v-if="!detail.latest_change?.id" class="panel-card__desc">当前暂无改期记录。</view>
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
                <view v-if="!detail.review?.id" class="panel-card__desc">当前暂未提交评价。</view>
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

            <view class="panel-card mt-[24rpx]">
                <view class="panel-card__title">线下凭证</view>
                <view v-if="!detail.offline_voucher.id" class="panel-card__desc">当前暂无线下凭证记录。</view>
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

            <view class="action-grid mt-[24rpx]">
                <button v-if="detail.action.can_cancel" class="ghost-btn" @click="handleCancelOrder">取消订单</button>
                <button v-if="detail.action.can_pay_online" class="action-btn" @click="openPayPopup">在线支付</button>
                <button v-if="detail.action.can_upload_voucher" class="action-btn" @click="voucherPopupVisible = true">
                    提交线下凭证
                </button>
                <button v-if="detail.action.can_apply_reschedule" class="action-btn" @click="openReschedulePopup">申请改期</button>
                <button v-if="detail.action.can_apply_refund" class="action-btn" @click="openRefundPopup">申请退款</button>
                <button v-if="detail.action.can_review" class="action-btn" @click="goReviewPage">去评价</button>
                <button class="ghost-btn" @click="goOrderList">返回订单列表</button>
            </view>
        </template>

        <u-popup v-model="voucherPopupVisible" mode="bottom" border-radius="18" safe-area-inset-bottom>
            <view class="popup-panel">
                <view class="popup-panel__title">提交线下凭证</view>
                <view class="voucher-upload-grid">
                    <view
                        v-for="(item, index) in voucherForm.voucher_images"
                        :key="index"
                        class="voucher-upload-grid__item"
                    >
                        <image :src="item" mode="aspectFill" />
                    </view>
                    <view class="voucher-upload-grid__add" @click="handleChooseVoucherImages">添加图片</view>
                </view>
                <textarea
                    v-model="voucherForm.voucher_remark"
                    class="popup-panel__textarea"
                    maxlength="500"
                    placeholder="请补充支付说明（选填）"
                />
                <button class="action-btn" :loading="voucherSubmitting" @click="handleSubmitVoucher">确认提交</button>
            </view>
        </u-popup>

        <u-popup v-model="reschedulePopupVisible" mode="bottom" border-radius="18" safe-area-inset-bottom>
            <view class="popup-panel">
                <view class="popup-panel__title">申请改期</view>
                <view class="popup-panel__desc">当前服务日期：{{ detail.order.service_date || '-' }}</view>
                <picker mode="date" :value="rescheduleForm.new_service_date" @change="handleRescheduleDateChange">
                    <view class="picker-trigger">
                        {{ rescheduleForm.new_service_date || '请选择新的服务日期' }}
                    </view>
                </picker>
                <textarea
                    v-model="rescheduleForm.apply_reason"
                    class="popup-panel__textarea"
                    maxlength="500"
                    placeholder="请填写改期原因"
                />
                <button class="action-btn" :loading="rescheduleSubmitting" @click="handleSubmitReschedule">确认提交</button>
            </view>
        </u-popup>

        <u-popup v-model="refundPopupVisible" mode="bottom" border-radius="18" safe-area-inset-bottom>
            <view class="popup-panel">
                <view class="popup-panel__title">申请退款</view>
                <view class="popup-panel__desc">退款金额将按当前订单实付金额整单退回，请填写退款原因。</view>
                <textarea
                    v-model="refundForm.apply_reason"
                    class="popup-panel__textarea"
                    maxlength="500"
                    placeholder="请填写退款原因"
                />
                <button class="action-btn" :loading="refundSubmitting" @click="handleSubmitRefund">确认提交</button>
            </view>
        </u-popup>

        <payment
            v-model:show="payState.showPay"
            v-model:show-check="payState.showCheck"
            :order-id="payState.orderId"
            :from="payState.from"
            :redirect="payState.redirect"
            @success="handlePaySuccess"
            @fail="handlePayFail"
        />
    </view>
</template>

<script setup lang="ts">
import { uploadImage } from '@/api/app'
import {
    applyWeddingOrderRefund,
    applyWeddingOrderReschedule,
    cancelWeddingOrder,
    getWeddingOrderDetail,
    submitWeddingOfflineVoucher
} from '@/api/wedding'
import { useUserStore } from '@/stores/user'
import { requestWeddingSubscribeMessages } from '@/utils/wedding'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { computed, reactive, ref } from 'vue'

const userStore = useUserStore()
const loading = ref(false)
const voucherSubmitting = ref(false)
const rescheduleSubmitting = ref(false)
const refundSubmitting = ref(false)
const orderId = ref(0)
const voucherPopupVisible = ref(false)
const reschedulePopupVisible = ref(false)
const refundPopupVisible = ref(false)
const detail = reactive<any>({
    order: {},
    snapshot: {},
    offline_voucher: {},
    latest_change: {},
    latest_refund: {},
    review: {},
    action: {},
    pay_from: 'service_order'
})
const voucherForm = reactive({
    voucher_images: [] as string[],
    voucher_remark: ''
})
const rescheduleForm = reactive({
    new_service_date: '',
    apply_reason: ''
})
const refundForm = reactive({
    apply_reason: ''
})

const payState = reactive({
    orderId: 0,
    from: 'service_order',
    showPay: false,
    showCheck: false,
    redirect: ''
})

const templatePages = computed(() => detail.snapshot?.template_snapshot?.pages || [])

const loadDetail = async () => {
    if (!orderId.value) {
        return
    }
    loading.value = true
    try {
        const data = await getWeddingOrderDetail({ order_id: orderId.value })
        Object.assign(detail, data || {})
        payState.orderId = orderId.value
        payState.from = detail.pay_from || 'service_order'
        payState.redirect = `/pages/wedding_order_detail/wedding_order_detail?order_id=${orderId.value}`
    } finally {
        loading.value = false
    }
}

const handleCancelOrder = async () => {
    const modalResult = await uni.showModal({
        title: '确认取消订单？',
        content: '取消后将释放当前档期，请谨慎操作。'
    })
    if (!modalResult.confirm) {
        return
    }
    await cancelWeddingOrder({ order_id: orderId.value })
    await loadDetail()
}

const handleChooseVoucherImages = async () => {
    const chooseResult = await uni.chooseImage({
        count: 6,
        sizeType: ['compressed']
    })
    const filePaths = chooseResult.tempFilePaths || []
    if (!filePaths.length) {
        return
    }
    uni.showLoading({ title: '上传中...' })
    try {
        for (const filePath of filePaths) {
            const uploadResult: any = await uploadImage(filePath, userStore.token || userStore.temToken || '')
            if (uploadResult?.uri) {
                voucherForm.voucher_images.push(uploadResult.uri)
            }
        }
    } finally {
        uni.hideLoading()
    }
}

const handleSubmitVoucher = async () => {
    if (!voucherForm.voucher_images.length) {
        uni.showToast({ title: '请至少上传一张凭证图片', icon: 'none' })
        return
    }
    voucherSubmitting.value = true
    try {
        await submitWeddingOfflineVoucher({
            order_id: orderId.value,
            voucher_images: voucherForm.voucher_images,
            voucher_remark: voucherForm.voucher_remark
        })
        voucherPopupVisible.value = false
        voucherForm.voucher_images = []
        voucherForm.voucher_remark = ''
        await loadDetail()
    } finally {
        voucherSubmitting.value = false
    }
}

const openReschedulePopup = () => {
    rescheduleForm.new_service_date = ''
    rescheduleForm.apply_reason = ''
    reschedulePopupVisible.value = true
}

const openRefundPopup = () => {
    refundForm.apply_reason = ''
    refundPopupVisible.value = true
}

const handleRescheduleDateChange = (event: any) => {
    rescheduleForm.new_service_date = event?.detail?.value || ''
}

const handleSubmitReschedule = async () => {
    if (!rescheduleForm.new_service_date) {
        uni.showToast({ title: '请选择新的服务日期', icon: 'none' })
        return
    }
    if (!rescheduleForm.apply_reason.trim()) {
        uni.showToast({ title: '请填写改期原因', icon: 'none' })
        return
    }
    rescheduleSubmitting.value = true
    try {
        await requestWeddingSubscribeMessages([209])
        await applyWeddingOrderReschedule({
            order_id: orderId.value,
            new_service_date: rescheduleForm.new_service_date,
            apply_reason: rescheduleForm.apply_reason
        })
        reschedulePopupVisible.value = false
        await loadDetail()
    } finally {
        rescheduleSubmitting.value = false
    }
}

const handleSubmitRefund = async () => {
    if (!refundForm.apply_reason.trim()) {
        uni.showToast({ title: '请填写退款原因', icon: 'none' })
        return
    }
    refundSubmitting.value = true
    try {
        await requestWeddingSubscribeMessages([214])
        await applyWeddingOrderRefund({
            order_id: orderId.value,
            apply_reason: refundForm.apply_reason
        })
        refundPopupVisible.value = false
        await loadDetail()
    } finally {
        refundSubmitting.value = false
    }
}

const openPayPopup = () => {
    payState.orderId = orderId.value
    payState.from = detail.pay_from || 'service_order'
    payState.showPay = true
}

const handlePaySuccess = async () => {
    payState.showPay = false
    payState.showCheck = false
    uni.navigateTo({
        url: `/pages/payment_result/payment_result?id=${orderId.value}&from=${payState.from}`
    })
}

const handlePayFail = () => {
    uni.showToast({ title: '支付未完成', icon: 'none' })
}

const goReviewPage = () => {
    uni.navigateTo({
        url: `/pages/wedding_order_review/wedding_order_review?order_id=${orderId.value}`
    })
}

const goOrderList = () => {
    uni.redirectTo({
        url: '/pages/wedding_order_list/wedding_order_list'
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
.wedding-order-detail-page {
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

.voucher-grid,
.voucher-upload-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16rpx;
}

.voucher-grid__item,
.voucher-upload-grid__item,
.voucher-upload-grid__add {
    width: 100%;
    height: 190rpx;
    border-radius: 20rpx;
    overflow: hidden;
}

.voucher-upload-grid__add {
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1rpx dashed rgba(219, 39, 119, 0.18);
    color: #9d174d;
    font-size: 24rpx;
    background: rgba(253, 242, 248, 0.92);
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

.picker-trigger {
    margin-top: 20rpx;
    padding: 24rpx 22rpx;
    border-radius: 18rpx;
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    background: #fff;
    color: #111827;
    font-size: 24rpx;
}
</style>
