<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <view class="wedding-order-review-page min-h-screen px-[24rpx] py-[24rpx] box-border">
        <view v-if="loading" class="state-card">正在加载评价信息...</view>
        <template v-else>
            <view class="hero-card">
                <view class="hero-card__eyebrow">Order Review</view>
                <view class="hero-card__title">提交服务评价</view>
                <view class="hero-card__desc">
                    {{ detail.order.provider_name || '-' }} / {{ detail.order.package_name || '-' }} / {{ detail.order.service_date || '-' }}
                </view>
            </view>

            <view class="panel-card mt-[24rpx]">
                <view class="panel-card__title">评分</view>
                <view class="rate-wrap">
                    <u-rate v-model="form.review_score" active-color="#ca8a04" inactive-color="#fbcfe8" :size="46" />
                    <view class="rate-text">{{ form.review_score }} 分</view>
                </view>
            </view>

            <view class="panel-card mt-[24rpx]">
                <view class="panel-card__title">文字评价</view>
                <textarea
                    v-model="form.review_content"
                    class="review-textarea"
                    maxlength="1000"
                    placeholder="请写下本次服务体验，帮助后续用户更好了解服务质量。"
                />
            </view>

            <view class="panel-card mt-[24rpx]">
                <view class="panel-card__title">评价图片</view>
                <view class="panel-card__desc">可上传最多 6 张图片，展示现场效果或服务细节。</view>
                <view class="image-grid mt-[20rpx]">
                    <view v-for="(item, index) in form.review_images" :key="index" class="image-card">
                        <image class="image-card__img" :src="item" mode="aspectFill" />
                        <view class="image-card__remove" @click.stop="removeImage(index)">删除</view>
                    </view>
                    <view v-if="form.review_images.length < 6" class="image-card image-card--add" @click="handleChooseImages">
                        添加图片
                    </view>
                </view>
            </view>

            <view v-if="detail.review?.id" class="panel-card mt-[24rpx]">
                <view class="panel-card__title">最近一次评价记录</view>
                <view class="detail-list">
                    <view class="detail-row">
                        <view class="detail-row__label">审核状态</view>
                        <view class="detail-row__value">{{ detail.review.audit_status_desc || '-' }}</view>
                    </view>
                    <view class="detail-row">
                        <view class="detail-row__label">审核备注</view>
                        <view class="detail-row__value">{{ detail.review.audit_remark || '-' }}</view>
                    </view>
                </view>
            </view>

            <view class="action-grid mt-[24rpx]">
                <button class="action-btn" :loading="submitting" @click="handleSubmit">提交评价</button>
                <button class="ghost-btn" @click="goBack">返回订单详情</button>
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
import { uploadImage } from '@/api/app'
import { getWeddingOrderDetail, submitWeddingOrderReview } from '@/api/wedding'
import { useUserStore } from '@/stores/user'
import { onLoad } from '@dcloudio/uni-app'
import { reactive, ref } from 'vue'

const userStore = useUserStore()
const loading = ref(false)
const submitting = ref(false)
const orderId = ref(0)
const detail = reactive<any>({
    order: {},
    review: {},
    action: {}
})

const form = reactive({
    review_score: 5,
    review_content: '',
    review_images: [] as string[]
})

const loadDetail = async () => {
    if (!orderId.value) {
        return
    }
    loading.value = true
    try {
        const data = await getWeddingOrderDetail({ order_id: orderId.value })
        Object.assign(detail, data || {})
        if (detail.review?.id) {
            form.review_score = Number(detail.review.score || 5)
            form.review_content = detail.review.content || ''
            form.review_images = []
        }
    } finally {
        loading.value = false
    }
}

const handleChooseImages = async () => {
    const remainCount = Math.max(0, 6 - form.review_images.length)
    if (!remainCount) {
        return
    }
    const chooseResult = await uni.chooseImage({
        count: remainCount,
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
                form.review_images.push(uploadResult.uri)
            }
        }
    } finally {
        uni.hideLoading()
    }
}

const removeImage = (index: number) => {
    form.review_images.splice(index, 1)
}

const handleSubmit = async () => {
    if (!detail.action?.can_review) {
        uni.showToast({ title: '当前订单不可评价', icon: 'none' })
        return
    }
    submitting.value = true
    try {
        await submitWeddingOrderReview({
            order_id: orderId.value,
            review_score: form.review_score,
            review_content: form.review_content,
            review_images: form.review_images
        })
        uni.showToast({ title: '评价提交成功', icon: 'success' })
        setTimeout(() => {
            goBack()
        }, 500)
    } finally {
        submitting.value = false
    }
}

const goBack = () => {
    if (getCurrentPages().length > 1) {
        uni.navigateBack()
        return
    }
    uni.redirectTo({
        url: `/pages/wedding_order_detail/wedding_order_detail?order_id=${orderId.value}`
    })
}

onLoad(async (options) => {
    orderId.value = Number(options?.order_id || 0)
    await loadDetail()
})
</script>

<style lang="scss" scoped>
.wedding-order-review-page {
    background:
        radial-gradient(circle at top right, rgba(219, 39, 119, 0.1), transparent 26%),
        linear-gradient(180deg, #fffafc, #f8f3ef 44%, #f7f3f0);
}

.hero-card,
.panel-card,
.state-card {
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

.panel-card__title {
    color: #111827;
    font-size: 30rpx;
    font-weight: 600;
}

.rate-wrap {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20rpx;
    margin-top: 24rpx;
}

.rate-text {
    color: #b45309;
    font-size: 28rpx;
    font-weight: 600;
}

.review-textarea {
    margin-top: 20rpx;
    width: 100%;
    min-height: 220rpx;
    box-sizing: border-box;
    padding: 18rpx 20rpx;
    border-radius: 18rpx;
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    background: #fff;
    font-size: 24rpx;
    color: #111827;
}

.image-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16rpx;
}

.image-card {
    position: relative;
    width: 100%;
    height: 190rpx;
    border-radius: 20rpx;
    overflow: hidden;
}

.image-card__img {
    width: 100%;
    height: 100%;
}

.image-card__remove {
    position: absolute;
    right: 12rpx;
    bottom: 12rpx;
    padding: 8rpx 14rpx;
    border-radius: 999rpx;
    background: rgba(17, 24, 39, 0.58);
    color: #fff;
    font-size: 20rpx;
}

.image-card--add {
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1rpx dashed rgba(219, 39, 119, 0.18);
    background: rgba(253, 242, 248, 0.92);
    color: #9d174d;
    font-size: 24rpx;
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
</style>
