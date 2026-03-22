<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <w-page-nav />
    <view class="provider-center min-h-screen px-[24rpx] py-[24rpx] box-border">
        <view class="hero-card">
            <view class="hero-card__eyebrow">服务人员工作台</view>
            <view class="hero-card__title">服务人员中心</view>
            <view class="hero-card__meta">服务人员 ID：{{ userInfo.provider_id || '-' }}</view>
        </view>

        <view class="panel-card mt-[24rpx]">
            <view class="panel-card__title">当前规则概览</view>
            <view class="summary-grid">
                <view v-for="item in ruleSummary" :key="item.label" class="summary-item">
                    <view class="summary-item__label">{{ item.label }}</view>
                    <view class="summary-item__value">{{ item.value }}</view>
                </view>
            </view>
        </view>

        <view class="panel-card mt-[24rpx]">
            <view class="panel-card__title">能力概览</view>
            <view class="capability-grid">
                <view
                    v-for="item in capabilityList"
                    :key="item.title"
                    class="capability-item"
                    :class="{ 'capability-item--disabled': !item.enabled }"
                >
                    <view class="capability-item__title">{{ item.title }}</view>
                    <view class="capability-item__status">{{ item.status }}</view>
                </view>
            </view>
        </view>

        <view class="panel-card mt-[24rpx]">
            <view class="panel-card__title">快捷入口</view>
            <view class="entry-grid">
                <view class="entry-item" @click="navigateTo('/pages/provider_schedule/provider_schedule')">
                    <view class="entry-item__title">档期管理</view>
                </view>
                <view class="entry-item" @click="navigateTo('/pages/provider_profile_manage/provider_profile_manage')">
                    <view class="entry-item__title">资料中心</view>
                </view>
                <view class="entry-item" @click="navigateTo('/pages/provider_order_pending/provider_order_pending')">
                    <view class="entry-item__title">订单工作台</view>
                </view>
                <view class="entry-item" @click="navigateTo('/pages/provider_content_manage/provider_content_manage')">
                    <view class="entry-item__title">内容互动</view>
                </view>
                <view class="entry-item" @click="navigateTo('/pages/notice_center/notice_center')">
                    <view class="entry-item__title">通知中心</view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { storeToRefs } from 'pinia'
import { computed } from 'vue'

const reviewModeLabelMap: Record<string, string> = {
    admin: '管理员审核',
    provider: '服务人员审核',
    provider_then_admin: '服务人员初审后管理员终审'
}

const appStore = useAppStore()
const userStore = useUserStore()
const { userInfo } = storeToRefs(userStore)
const serviceBusinessConfig = computed(() => appStore.getServiceBusinessConfig || {})

const ruleSummary = computed(() => {
    const config = serviceBusinessConfig.value
    return [
        {
            label: '资料审核',
            value: reviewModeLabelMap[config.review?.provider_profile_review_mode || 'admin'] || '管理员审核'
        },
        {
            label: '动态发布',
            value: Number(config.interaction?.post_enabled ?? 0) === 1 ? '开启' : '关闭'
        },
        {
            label: '站内通知',
            value: Number(config.notice?.system_notice_enabled ?? 0) === 1 ? '开启' : '关闭'
        },
        {
            label: '订阅消息',
            value: Number(config.notice?.mnp_notice_enabled ?? 0) === 1 ? '开启' : '关闭'
        }
    ]
})

const capabilityList = computed(() => {
    const config = serviceBusinessConfig.value
    const interactionEnabled =
        Number(config.interaction?.post_enabled ?? 0) === 1 ||
        Number(config.interaction?.comment_enabled ?? 0) === 1 ||
        Number(config.interaction?.order_review_enabled ?? 0) === 1

    return [
        {
            title: '资料维护',
            status: `审核模式：${reviewModeLabelMap[config.review?.provider_profile_review_mode || 'admin'] || '管理员审核'}`,
            desc: '服务人员资料、证书、作品、套餐维护页将挂接到此入口。',
            enabled: true
        },
        {
            title: '档期管理',
            status: '自然日月视图已上线',
            desc: '支持维护本人可预约与不可服务，已锁定与已占用保持只读展示。',
            enabled: true
        },
        {
            title: '订单处理',
            status: `待确认超时 ${config.trade?.provider_confirm_timeout_minutes || 30} 分钟`,
            desc: '待确认、改期处理、履约跟进将在此集中处理。',
            enabled: true
        },
        {
            title: '内容互动',
            status: interactionEnabled ? '已开放至少一项互动能力' : '当前全部关闭',
            desc: '动态发布、评论处理与评价查看将统一归口。',
            enabled: interactionEnabled
        }
    ]
})

const navigateTo = (url: string) => {
    uni.navigateTo({
        url
    })
}
</script>

<style lang="scss" scoped>
.provider-center {
    background:
        radial-gradient(circle at top right, rgba(219, 39, 119, 0.10), transparent 28%),
        linear-gradient(180deg, #fffafc, #f8f3ef 42%, #f7f3f0);
}

.hero-card,
.panel-card {
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    border-radius: 28rpx;
    box-shadow: 0 18rpx 48rpx rgba(31, 41, 55, 0.06);
}

.hero-card {
    padding: 36rpx 32rpx;
}

.hero-card__eyebrow {
    color: #9d174d;
    font-size: 22rpx;
    letter-spacing: 4rpx;
}

.hero-card__title {
    margin-top: 16rpx;
    color: #1f2937;
    font-size: 44rpx;
    font-weight: 600;
}

.hero-card__meta {
    margin-top: 24rpx;
    color: #831843;
    font-size: 24rpx;
}

.panel-card {
    padding: 28rpx;
}

.panel-card__title {
    color: #111827;
    font-size: 30rpx;
    font-weight: 600;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18rpx;
    margin-top: 24rpx;
}

.summary-item {
    padding: 22rpx 24rpx;
    border-radius: 22rpx;
    background: linear-gradient(180deg, #fff9fb, #fffdf9);
    border: 1rpx solid rgba(219, 39, 119, 0.12);
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
}

.capability-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20rpx;
    margin-top: 24rpx;
}

.capability-item {
    padding: 24rpx;
    border-radius: 24rpx;
    background: linear-gradient(180deg, #fff8fb, #fffdf9);
    border: 1rpx solid rgba(202, 138, 4, 0.14);
}

.capability-item--disabled {
    opacity: 0.7;
}

.capability-item__title {
    color: #1f2937;
    font-size: 28rpx;
    font-weight: 600;
}

.capability-item__status {
    margin-top: 10rpx;
    color: #9d174d;
    font-size: 22rpx;
}

.entry-grid {
    margin-top: 22rpx;
    display: grid;
    gap: 16rpx;
}

.entry-item {
    padding: 22rpx;
    border-radius: 22rpx;
    border: 1rpx solid rgba(202, 138, 4, 0.14);
    background: linear-gradient(180deg, #fff9fb, #fffdf9);
}

.entry-item__title {
    color: #1f2937;
    font-size: 28rpx;
    font-weight: 600;
}

</style>
