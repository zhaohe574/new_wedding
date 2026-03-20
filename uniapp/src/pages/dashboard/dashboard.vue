<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <view class="dashboard-page min-h-screen px-[24rpx] py-[24rpx] box-border">
        <view class="dashboard-hero">
            <view class="dashboard-hero__eyebrow">经营驾驶舱</view>
            <view class="dashboard-hero__title">婚庆预约业务数据总览</view>
            <view class="dashboard-hero__desc">
                当前页面已完成只读权限基线打通。后续会接入真实订单、支付、退款、改期与评价聚合指标。
            </view>
        </view>

        <view class="stats-grid">
            <view v-for="item in statsList" :key="item.title" class="stat-card">
                <view class="stat-card__label">{{ item.title }}</view>
                <view class="stat-card__value">{{ item.value }}</view>
                <view class="stat-card__hint">{{ item.hint }}</view>
            </view>
        </view>

        <view class="rule-panel">
            <view class="rule-panel__title">当前规则摘要</view>
            <view class="rule-list">
                <view v-for="item in ruleList" :key="item.label" class="rule-item">
                    <view class="rule-item__label">{{ item.label }}</view>
                    <view class="rule-item__value">{{ item.value }}</view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { useAppStore } from '@/stores/app'
import { computed } from 'vue'

const reviewModeLabelMap: Record<string, string> = {
    admin: '管理员审核',
    provider: '服务人员审核',
    provider_then_admin: '服务人员初审后管理员终审'
}

const appStore = useAppStore()
const serviceBusinessConfig = computed(() => appStore.getServiceBusinessConfig || {})

const statsList = computed(() => {
    const config = serviceBusinessConfig.value
    return [
        {
            title: '在线支付',
            value: Number(config.trade?.online_pay_enabled ?? 0) === 1 ? '开启' : '关闭',
            hint: `支付超时 ${config.trade?.pay_timeout_minutes || 30} 分钟`
        },
        {
            title: '线下凭证',
            value: Number(config.trade?.offline_voucher_enabled ?? 0) === 1 ? '开启' : '关闭',
            hint: '后续接入线下支付凭证审核链路'
        },
        {
            title: '站内通知',
            value: Number(config.notice?.system_notice_enabled ?? 0) === 1 ? '开启' : '关闭',
            hint: '用于订单、改期、退款等关键节点提醒'
        },
        {
            title: '企业微信',
            value: Number(config.notice?.work_wechat_notice_enabled ?? 0) === 1 ? '开启' : '关闭',
            hint: '后续用于服务人员关键待办提醒'
        }
    ]
})

const ruleList = computed(() => {
    const config = serviceBusinessConfig.value
    return [
        {
            label: '评价审核模式',
            value: reviewModeLabelMap[config.review?.order_review_mode || 'admin'] || '管理员审核'
        },
        {
            label: '评论审核模式',
            value: reviewModeLabelMap[config.review?.comment_review_mode || 'provider_then_admin'] || '服务人员初审后管理员终审'
        },
        {
            label: '动态发布',
            value: Number(config.interaction?.post_enabled ?? 0) === 1 ? '开启' : '关闭'
        },
        {
            label: '订单评价',
            value: Number(config.interaction?.order_review_enabled ?? 0) === 1 ? '开启' : '关闭'
        }
    ]
})
</script>

<style lang="scss" scoped>
.dashboard-page {
    background:
        radial-gradient(circle at top left, rgba(219, 39, 119, 0.12), transparent 24%),
        radial-gradient(circle at right bottom, rgba(202, 138, 4, 0.12), transparent 24%),
        linear-gradient(180deg, #fffdf9, #f7f3ef 44%, #f6f2ef);
}

.dashboard-hero {
    padding: 36rpx 32rpx;
    border-radius: 28rpx;
    background: rgba(255, 255, 255, 0.94);
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    box-shadow: 0 18rpx 48rpx rgba(31, 41, 55, 0.06);
}

.dashboard-hero__eyebrow {
    color: #9d174d;
    font-size: 22rpx;
    letter-spacing: 4rpx;
}

.dashboard-hero__title {
    margin-top: 16rpx;
    color: #1f2937;
    font-size: 44rpx;
    font-weight: 600;
}

.dashboard-hero__desc {
    margin-top: 16rpx;
    color: #6b7280;
    font-size: 26rpx;
    line-height: 1.8;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20rpx;
    margin-top: 24rpx;
}

.stat-card {
    padding: 28rpx;
    border-radius: 24rpx;
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(202, 138, 4, 0.14);
    box-shadow: 0 18rpx 48rpx rgba(31, 41, 55, 0.05);
}

.stat-card__label {
    color: #9ca3af;
    font-size: 22rpx;
}

.stat-card__value {
    margin-top: 16rpx;
    color: #111827;
    font-size: 48rpx;
    font-weight: 600;
}

.stat-card__hint {
    margin-top: 12rpx;
    color: #6b7280;
    font-size: 24rpx;
    line-height: 1.7;
}

.rule-panel {
    margin-top: 24rpx;
    padding: 28rpx;
    border-radius: 24rpx;
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    box-shadow: 0 18rpx 48rpx rgba(31, 41, 55, 0.05);
}

.rule-panel__title {
    color: #111827;
    font-size: 30rpx;
    font-weight: 600;
}

.rule-list {
    display: grid;
    gap: 18rpx;
    margin-top: 22rpx;
}

.rule-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20rpx 22rpx;
    border-radius: 20rpx;
    background: linear-gradient(180deg, #fff9fb, #fffdf9);
    border: 1rpx solid rgba(202, 138, 4, 0.14);
    gap: 20rpx;
}

.rule-item__label {
    color: #6b7280;
    font-size: 24rpx;
}

.rule-item__value {
    color: #111827;
    font-size: 24rpx;
    font-weight: 600;
    text-align: right;
}
</style>
