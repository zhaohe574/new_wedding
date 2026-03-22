<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <view class="dashboard-page min-h-screen px-[24rpx] py-[24rpx] box-border">
        <view class="dashboard-hero">
            <view class="dashboard-hero__eyebrow">经营驾驶舱</view>
            <view class="dashboard-hero__title">婚庆预约经营总览</view>
            <view class="dashboard-hero__desc">
                面向只读查看用户展示真实订单、支付、改期与评价待办，统计口径与后台工作台保持一致。
            </view>
            <view class="dashboard-hero__meta">
                <view class="dashboard-badge">更新时间 {{ overview.time || '--' }}</view>
                <view class="dashboard-badge dashboard-badge--soft">
                    驾驶舱权限 {{ userInfo.can_view_dashboard ? '已开通' : '未开通' }}
                </view>
            </view>
        </view>

        <view class="stats-grid">
            <view v-for="item in statsList" :key="item.title" class="stat-card">
                <view class="stat-card__label">{{ item.title }}</view>
                <view class="stat-card__value">{{ item.value }}</view>
                <view class="stat-card__hint">{{ item.hint }}</view>
            </view>
        </view>

        <view class="panel-card mt-[24rpx]">
            <view class="panel-card__title">待办摘要</view>
            <view class="todo-grid">
                <view v-for="item in todoList" :key="item.label" class="todo-item">
                    <view class="todo-item__label">{{ item.label }}</view>
                    <view class="todo-item__value">{{ item.value }}</view>
                    <view class="todo-item__desc">{{ item.desc }}</view>
                </view>
            </view>
        </view>

        <view class="panel-card mt-[24rpx]">
            <view class="panel-card__title">近 7 日订单趋势</view>
            <view class="trend-list">
                <view v-for="item in orderTrendList" :key="item.label" class="trend-row">
                    <view class="trend-row__label">{{ item.label }}</view>
                    <view class="trend-row__track">
                        <view class="trend-row__bar" :style="{ width: `${item.width}%` }"></view>
                    </view>
                    <view class="trend-row__value">{{ item.value }}</view>
                </view>
            </view>
        </view>

        <view class="panel-card mt-[24rpx]">
            <view class="panel-card__title">近 7 日支付趋势</view>
            <view class="trend-list">
                <view v-for="item in paymentTrendList" :key="item.label" class="trend-row trend-row--gold">
                    <view class="trend-row__label">{{ item.label }}</view>
                    <view class="trend-row__track">
                        <view class="trend-row__bar" :style="{ width: `${item.width}%` }"></view>
                    </view>
                    <view class="trend-row__value">{{ item.value }}</view>
                </view>
            </view>
        </view>

        <view class="panel-card mt-[24rpx]">
            <view class="panel-card__title">当前规则摘要</view>
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
import { getWeddingDashboardOverview } from '@/api/wedding'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { storeToRefs } from 'pinia'
import { computed, reactive } from 'vue'

const reviewModeLabelMap: Record<string, string> = {
    admin: '管理员审核',
    provider: '服务人员审核',
    provider_then_admin: '服务人员初审后管理员终审'
}

const appStore = useAppStore()
const userStore = useUserStore()
const { userInfo } = storeToRefs(userStore)
const serviceBusinessConfig = computed(() => appStore.getServiceBusinessConfig || {})
const overview = reactive<Record<string, any>>({
    time: '',
    today: {},
    todo: {},
    order_trend: {
        labels: [],
        values: []
    },
    payment_trend: {
        labels: [],
        values: []
    }
})

const formatAmount = (value: number | string | undefined) => {
    const amount = Number(value || 0)
    return `￥${amount.toFixed(2)}`
}

const buildTrendList = (
    labels: string[] = [],
    values: Array<number | string> = [],
    valueFormatter?: (value: number) => string
) => {
    const numericValues = values.map((item) => Number(item || 0))
    const maxValue = Math.max(...numericValues, 1)
    return labels.map((label, index) => {
        const value = numericValues[index] || 0
        return {
            label,
            value: valueFormatter ? valueFormatter(value) : `${value}`,
            width: Math.max(12, Math.round((value / maxValue) * 100))
        }
    })
}

const statsList = computed(() => {
    const today = overview.today || {}
    return [
        {
            title: '今日订单',
            value: `${today.today_order_count ?? 0}`,
            hint: `累计订单 ${today.total_order_count ?? 0}`
        },
        {
            title: '今日支付',
            value: formatAmount(today.today_paid_amount),
            hint: `累计支付 ${formatAmount(today.total_paid_amount)}`
        },
        {
            title: '待服务人员确认',
            value: `${today.wait_provider_confirm_count ?? 0}`,
            hint: '订单已锁档，等待服务人员处理'
        },
        {
            title: '待线下凭证审核',
            value: `${today.wait_offline_voucher_audit_count ?? 0}`,
            hint: '需要后台尽快完成人工核验'
        },
        {
            title: '待退款处理',
            value: `${today.wait_refund_count ?? 0}`,
            hint: '退款处理结果将同步影响订单状态'
        },
        {
            title: '待资料变更审核',
            value: `${today.wait_profile_change_count ?? 0}`,
            hint: '资料中心的新版本需审核后才会替换正式数据'
        }
    ]
})

const todoList = computed(() => {
    const todo = overview.todo || {}
    return [
        {
            label: '待改期处理',
            value: `${todo.wait_reschedule_count ?? 0}`,
            desc: '用户已提交改期申请，等待服务人员或后台处理'
        },
        {
            label: '待评价审核',
            value: `${todo.wait_review_audit_count ?? 0}`,
            desc: '未审核通过前不会公开展示，也不会进入经营统计'
        },
        {
            label: '待服务人员确认',
            value: `${todo.wait_provider_confirm_count ?? 0}`,
            desc: '接单前订单继续保持锁档状态'
        },
        {
            label: '待凭证审核',
            value: `${todo.wait_offline_voucher_audit_count ?? 0}`,
            desc: '线下支付凭证正在等待人工核验'
        },
        {
            label: '待退款处理',
            value: `${todo.wait_refund_count ?? 0}`,
            desc: '退款处理完成后需同步订单结果与用户通知'
        },
        {
            label: '待资料变更审核',
            value: `${todo.wait_profile_change_count ?? 0}`,
            desc: '资料、证书、作品、套餐统一由管理员审核'
        },
        {
            label: '待动态审核',
            value: `${todo.wait_post_audit_count ?? 0}`,
            desc: '动态默认走管理员审核，通过后才会公开展示'
        },
        {
            label: '待评论审核',
            value: `${todo.wait_comment_audit_count ?? 0}`,
            desc: '评论审核与评价审核共用婚庆互动审核规则'
        }
    ]
})

const orderTrendList = computed(() =>
    buildTrendList(overview.order_trend?.labels || [], overview.order_trend?.values || []).slice(-7)
)

const paymentTrendList = computed(() =>
    buildTrendList(
        overview.payment_trend?.labels || [],
        overview.payment_trend?.values || [],
        (value) => formatAmount(value)
    ).slice(-7)
)

const ruleList = computed(() => {
    const config = serviceBusinessConfig.value
    return [
        {
            label: '评价审核模式',
            value: reviewModeLabelMap[config.review?.order_review_mode || 'admin'] || '管理员审核'
        },
        {
            label: '评论审核模式',
            value:
                reviewModeLabelMap[config.review?.comment_review_mode || 'provider_then_admin'] ||
                '服务人员初审后管理员终审'
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

const loadOverview = async () => {
    const data = await getWeddingDashboardOverview()
    Object.assign(overview, data || {})
}

onShow(() => {
    loadOverview()
})
</script>

<style lang="scss" scoped>
.dashboard-page {
    background:
        radial-gradient(circle at top left, rgba(219, 39, 119, 0.14), transparent 26%),
        radial-gradient(circle at right bottom, rgba(202, 138, 4, 0.12), transparent 24%),
        linear-gradient(180deg, #fdf2f8, #fbf7f3 44%, #f7f3ef);
}

.dashboard-hero,
.panel-card {
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

.dashboard-hero__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
    margin-top: 24rpx;
}

.dashboard-badge {
    padding: 12rpx 20rpx;
    border-radius: 999rpx;
    background: rgba(157, 23, 77, 0.08);
    color: #9d174d;
    font-size: 22rpx;
}

.dashboard-badge--soft {
    background: rgba(202, 138, 4, 0.10);
    color: #a16207;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
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

.panel-card__title {
    color: #111827;
    font-size: 30rpx;
    font-weight: 600;
}

.todo-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18rpx;
    margin-top: 22rpx;
}

.todo-item {
    padding: 22rpx;
    border-radius: 22rpx;
    background: linear-gradient(180deg, #fff8fb, #fffef9);
    border: 1rpx solid rgba(219, 39, 119, 0.10);
}

.todo-item__label {
    color: #6b7280;
    font-size: 22rpx;
}

.todo-item__value {
    margin-top: 12rpx;
    color: #111827;
    font-size: 42rpx;
    font-weight: 600;
}

.todo-item__desc {
    margin-top: 10rpx;
    color: #6b7280;
    font-size: 22rpx;
    line-height: 1.7;
}

.trend-list {
    display: grid;
    gap: 16rpx;
    margin-top: 22rpx;
}

.trend-row {
    display: grid;
    grid-template-columns: 88rpx minmax(0, 1fr) 150rpx;
    align-items: center;
    gap: 18rpx;
}

.trend-row__label {
    color: #6b7280;
    font-size: 22rpx;
}

.trend-row__track {
    height: 16rpx;
    border-radius: 999rpx;
    background: rgba(219, 39, 119, 0.08);
    overflow: hidden;
}

.trend-row__bar {
    height: 100%;
    border-radius: 999rpx;
    background: linear-gradient(90deg, #db2777, #f472b6);
}

.trend-row--gold .trend-row__track {
    background: rgba(202, 138, 4, 0.10);
}

.trend-row--gold .trend-row__bar {
    background: linear-gradient(90deg, #d97706, #f59e0b);
}

.trend-row__value {
    color: #111827;
    font-size: 22rpx;
    font-weight: 600;
    text-align: right;
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

@media (max-width: 480px) {
    .stats-grid,
    .todo-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .trend-row {
        grid-template-columns: 72rpx minmax(0, 1fr) 132rpx;
    }
}
</style>
