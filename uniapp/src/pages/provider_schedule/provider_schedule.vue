<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <w-page-nav />
    <view class="provider-schedule-page min-h-screen px-[24rpx] py-[24rpx] box-border">
        <view class="hero-card">
            <view class="hero-card__eyebrow">Provider Schedule</view>
            <view class="hero-card__title">自然日档期管理</view>
            <view class="hero-card__meta">
                {{ providerName }} · {{ monthLabel }} · 本月可编辑 {{ Number(monthData.summary?.editable || 0) }} 天
            </view>
        </view>

        <view class="summary-grid mt-[24rpx]">
            <view v-for="item in summaryCards" :key="item.label" class="summary-card">
                <view class="summary-card__label">{{ item.label }}</view>
                <view class="summary-card__value">{{ item.value }}</view>
                <view class="summary-card__desc">{{ item.desc }}</view>
            </view>
        </view>

        <view class="panel-card mt-[24rpx]">
            <view class="panel-card__header">
                <view class="panel-card__title">月份与批量操作</view>
                <view class="month-toolbar">
                    <view class="toolbar-button" @click="changeMonth(-1)">上一月</view>
                    <view class="month-toolbar__label">{{ monthLabel }}</view>
                    <view class="toolbar-button" @click="changeMonth(1)">下一月</view>
                </view>
            </view>

            <view class="helper-row">
                <view class="helper-tag" @click="goCurrentMonth">回到本月</view>
                <view class="helper-text">先点选日期，再执行批量设为不可服务或恢复可预约。</view>
            </view>

            <textarea
                v-model="remark"
                class="remark-textarea"
                maxlength="500"
                placeholder="批量设为不可服务时可填写统一备注，例如：婚礼外拍、异地档期、休息日。"
            />

            <view class="selection-bar">
                <view class="selection-bar__info">已选 {{ selectedDates.length }} 天</view>
                <view class="selection-actions">
                    <view class="action-button action-button--ghost" @click="clearSelection">清空选择</view>
                    <view class="action-button action-button--gold" @click="handleRestoreAvailable">恢复可预约</view>
                    <view class="action-button action-button--rose" @click="handleSetUnavailable">设为不可服务</view>
                </view>
            </view>

            <view v-if="selectedDates.length" class="selected-chip-list">
                <view v-for="item in selectedDates" :key="item" class="selected-chip">{{ item }}</view>
            </view>
        </view>

        <view class="panel-card mt-[24rpx]">
            <view class="panel-card__header panel-card__header--stack">
                <view class="panel-card__title">月视图档期</view>
                <view class="legend-list">
                    <view v-for="item in legendList" :key="item.status" class="legend-item">
                        <view class="legend-dot" :class="`legend-dot--${item.status}`" />
                        <view class="legend-item__text">{{ item.label }}</view>
                    </view>
                </view>
            </view>

            <view v-if="loading" class="state-card">正在加载当前月份档期...</view>
            <template v-else>
                <view class="weekday-grid">
                    <view v-for="item in weekdayList" :key="item" class="weekday-grid__item">{{ item }}</view>
                </view>

                <view class="calendar-grid">
                    <view
                        v-for="cell in calendarCells"
                        :key="cell.key"
                        class="calendar-cell"
                        :class="cell.type === 'empty' ? 'calendar-cell--empty' : ''"
                    >
                        <template v-if="cell.type === 'day'">
                            <view
                                class="calendar-day"
                                :class="getDayClass(cell)"
                                @click="handleDayClick(cell)"
                            >
                                <view class="calendar-day__top">
                                    <view class="calendar-day__date">{{ cell.day }}</view>
                                    <view v-if="Number(cell.is_today) === 1" class="calendar-day__today">今天</view>
                                </view>
                                <view class="calendar-day__status">{{ cell.status_desc }}</view>
                                <view class="calendar-day__hint">{{ cell.status_hint }}</view>
                                <view v-if="cell.source_display && cell.source_display !== '-'" class="calendar-day__source">
                                    {{ cell.source_display }}
                                </view>
                                <view v-else-if="cell.remark" class="calendar-day__source">{{ cell.remark }}</view>
                            </view>
                        </template>
                    </view>
                </view>
            </template>
        </view>

        <view v-if="readonlyDays.length" class="panel-card mt-[24rpx]">
            <view class="panel-card__title">只读档期提示</view>
            <view class="readonly-list">
                <view v-for="item in readonlyDays" :key="item.service_date" class="readonly-item">
                    <view class="readonly-item__date">{{ item.service_date }}</view>
                    <view class="readonly-item__status">{{ item.status_desc }}</view>
                    <view class="readonly-item__desc">{{ item.source_display || item.status_hint }}</view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { deleteProviderScheduleDates, getProviderScheduleMonth, upsertProviderSchedule } from '@/api/wedding'
import { onShow } from '@dcloudio/uni-app'
import { computed, ref } from 'vue'

const weekdayList = ['一', '二', '三', '四', '五', '六', '日']
const legendList = [
    { status: 'available', label: '可预约，可继续接单' },
    { status: 'unavailable', label: '不可服务，可手动恢复' },
    { status: 'locked', label: '已锁定，只读展示' },
    { status: 'occupied', label: '已占用，只读展示' }
]

const monthData = ref<Record<string, any>>({
    provider: {},
    summary: {},
    days: []
})
const loading = ref(false)
const submitting = ref(false)
const remark = ref('')
const selectedDates = ref<string[]>([])
const currentMonth = ref(getCurrentMonthValue())

const providerName = computed(() => monthData.value?.provider?.name || '当前服务人员')
const monthLabel = computed(() => formatMonthLabel(currentMonth.value))
const summaryCards = computed(() => [
    {
        label: '可预约',
        value: Number(monthData.value.summary?.available || 0),
        desc: '无显式限制记录或已恢复可约'
    },
    {
        label: '不可服务',
        value: Number(monthData.value.summary?.unavailable || 0),
        desc: '服务人员手动设定，不影响订单流转'
    },
    {
        label: '已锁定',
        value: Number(monthData.value.summary?.locked || 0),
        desc: '订单待确认锁档，只读展示'
    },
    {
        label: '已占用',
        value: Number(monthData.value.summary?.occupied || 0),
        desc: '订单已确认占用，只读展示'
    }
])
const readonlyDays = computed(() =>
    (monthData.value.days || []).filter((item: Record<string, any>) => Number(item.can_edit) !== 1)
)
const calendarCells = computed(() => {
    const days = monthData.value.days || []
    if (!days.length) {
        return []
    }

    const firstWeekdayIndex = Number(days[0]?.weekday_index || 1)
    const leading = Array.from({ length: Math.max(0, firstWeekdayIndex - 1) }).map((_, index) => ({
        type: 'empty',
        key: `empty-${index}`
    }))

    const dayCells = days.map((item: Record<string, any>) => ({
        ...item,
        type: 'day',
        key: item.service_date
    }))

    return [...leading, ...dayCells]
})

const loadMonthData = async () => {
    loading.value = true
    try {
        const data = await getProviderScheduleMonth({
            month: currentMonth.value
        })
        monthData.value = {
            provider: data?.provider || {},
            summary: data?.summary || {},
            days: data?.days || []
        }
    } finally {
        loading.value = false
    }
}

const changeMonth = async (offset: number) => {
    currentMonth.value = shiftMonth(currentMonth.value, offset)
    clearSelection()
    await loadMonthData()
}

const goCurrentMonth = async () => {
    const targetMonth = getCurrentMonthValue()
    if (currentMonth.value === targetMonth) {
        uni.showToast({ title: '已经是当前月份', icon: 'none' })
        return
    }

    currentMonth.value = targetMonth
    clearSelection()
    await loadMonthData()
}

const clearSelection = () => {
    selectedDates.value = []
    remark.value = ''
}

const handleDayClick = (day: Record<string, any>) => {
    if (Number(day.can_edit) !== 1) {
        uni.showToast({
            title: `${day.status_desc}档期仅支持查看，需等待订单流转处理`,
            icon: 'none'
        })
        return
    }

    if (selectedDates.value.includes(day.service_date)) {
        selectedDates.value = selectedDates.value.filter((item) => item !== day.service_date)
        return
    }

    selectedDates.value = [...selectedDates.value, day.service_date].sort()
}

const handleSetUnavailable = async () => {
    if (!selectedDates.value.length) {
        uni.showToast({ title: '请先选择服务日期', icon: 'none' })
        return
    }
    if (submitting.value) {
        return
    }

    submitting.value = true
    try {
        await upsertProviderSchedule({
            service_dates: selectedDates.value,
            status: 'unavailable',
            remark: remark.value
        })
        uni.showToast({ title: '已设为不可服务', icon: 'success' })
        clearSelection()
        await loadMonthData()
    } finally {
        submitting.value = false
    }
}

const handleRestoreAvailable = async () => {
    if (!selectedDates.value.length) {
        uni.showToast({ title: '请先选择服务日期', icon: 'none' })
        return
    }
    if (submitting.value) {
        return
    }

    submitting.value = true
    try {
        await deleteProviderScheduleDates({
            service_dates: selectedDates.value
        })
        uni.showToast({ title: '已恢复为可预约', icon: 'success' })
        clearSelection()
        await loadMonthData()
    } finally {
        submitting.value = false
    }
}

const getDayClass = (day: Record<string, any>) => {
    return {
        'calendar-day--available': day.status === 'available',
        'calendar-day--unavailable': day.status === 'unavailable',
        'calendar-day--locked': day.status === 'locked',
        'calendar-day--occupied': day.status === 'occupied',
        'calendar-day--selected': selectedDates.value.includes(day.service_date),
        'calendar-day--readonly': Number(day.can_edit) !== 1
    }
}

onShow(async () => {
    await loadMonthData()
})

function padNumber(value: number) {
    return `${value}`.padStart(2, '0')
}

function getCurrentMonthValue() {
    const now = new Date()
    return `${now.getFullYear()}-${padNumber(now.getMonth() + 1)}`
}

function formatMonthLabel(month: string) {
    const [year, monthValue] = month.split('-').map((item) => Number(item || 0))
    return `${year} 年 ${monthValue} 月`
}

function shiftMonth(month: string, offset: number) {
    const [year, monthValue] = month.split('-').map((item) => Number(item || 0))
    const date = new Date(year, monthValue - 1 + offset, 1)
    return `${date.getFullYear()}-${padNumber(date.getMonth() + 1)}`
}
</script>

<style lang="scss" scoped>
.provider-schedule-page {
    background:
        radial-gradient(circle at top right, rgba(219, 39, 119, 0.10), transparent 28%),
        linear-gradient(180deg, #fffafc, #f8f3ef 44%, #f7f3f0);
}

.hero-card,
.panel-card,
.summary-card {
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

.summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18rpx;
}

.summary-card {
    padding: 24rpx;
}

.summary-card__label {
    color: #9ca3af;
    font-size: 22rpx;
}

.summary-card__value {
    margin-top: 12rpx;
    color: #111827;
    font-size: 34rpx;
    font-weight: 600;
}

.summary-card__desc {
    margin-top: 10rpx;
    color: #6b7280;
    font-size: 22rpx;
    line-height: 1.7;
}

.panel-card {
    padding: 28rpx;
}

.panel-card__header {
    display: flex;
    justify-content: space-between;
    gap: 20rpx;
    align-items: center;
}

.panel-card__header--stack {
    align-items: flex-start;
    flex-direction: column;
}

.panel-card__title {
    color: #111827;
    font-size: 30rpx;
    font-weight: 600;
}

.month-toolbar {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.month-toolbar__label {
    min-width: 190rpx;
    text-align: center;
    color: #111827;
    font-size: 26rpx;
    font-weight: 600;
}

.toolbar-button,
.helper-tag,
.action-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    transition: all 0.2s ease;
}

.toolbar-button,
.helper-tag {
    padding: 12rpx 20rpx;
    border: 1rpx solid rgba(219, 39, 119, 0.16);
    color: #9d174d;
    font-size: 22rpx;
    background: rgba(255, 255, 255, 0.92);
}

.helper-row {
    margin-top: 20rpx;
    display: flex;
    flex-wrap: wrap;
    gap: 14rpx;
    align-items: center;
}

.helper-text {
    color: #6b7280;
    font-size: 22rpx;
    line-height: 1.7;
}

.remark-textarea {
    width: 100%;
    min-height: 152rpx;
    margin-top: 24rpx;
    padding: 24rpx;
    border-radius: 24rpx;
    background: linear-gradient(180deg, #fff9fb, #fffdf9);
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    box-sizing: border-box;
    color: #111827;
    font-size: 24rpx;
    line-height: 1.8;
}

.selection-bar {
    margin-top: 22rpx;
    display: flex;
    justify-content: space-between;
    gap: 16rpx;
    align-items: center;
}

.selection-bar__info {
    color: #831843;
    font-size: 24rpx;
    font-weight: 600;
}

.selection-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 12rpx;
}

.action-button {
    padding: 14rpx 26rpx;
    font-size: 22rpx;
    font-weight: 600;
}

.action-button--ghost {
    border: 1rpx solid rgba(148, 163, 184, 0.24);
    color: #475569;
    background: #ffffff;
}

.action-button--gold {
    color: #b45309;
    background: rgba(202, 138, 4, 0.12);
    border: 1rpx solid rgba(202, 138, 4, 0.16);
}

.action-button--rose {
    color: #ffffff;
    background: linear-gradient(135deg, #db2777, #be185d);
    box-shadow: 0 12rpx 28rpx rgba(190, 24, 93, 0.18);
}

.selected-chip-list {
    margin-top: 18rpx;
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.selected-chip {
    padding: 10rpx 18rpx;
    border-radius: 999rpx;
    background: rgba(219, 39, 119, 0.10);
    color: #9d174d;
    font-size: 20rpx;
}

.legend-list {
    display: flex;
    flex-wrap: wrap;
    gap: 18rpx;
}

.legend-item {
    display: inline-flex;
    align-items: center;
    gap: 10rpx;
}

.legend-dot {
    width: 18rpx;
    height: 18rpx;
    border-radius: 999rpx;
}

.legend-dot--available {
    background: #fbcfe8;
}

.legend-dot--unavailable {
    background: #fcd34d;
}

.legend-dot--locked {
    background: #cbd5f5;
}

.legend-dot--occupied {
    background: #fda4af;
}

.legend-item__text {
    color: #6b7280;
    font-size: 22rpx;
}

.state-card {
    margin-top: 22rpx;
    padding: 24rpx;
    border-radius: 22rpx;
    background: linear-gradient(180deg, #fff8fb, #fffdf9);
    color: #6b7280;
    font-size: 24rpx;
}

.weekday-grid,
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 12rpx;
}

.weekday-grid {
    margin-top: 24rpx;
}

.weekday-grid__item {
    text-align: center;
    color: #9ca3af;
    font-size: 22rpx;
}

.calendar-grid {
    margin-top: 14rpx;
}

.calendar-cell {
    min-height: 176rpx;
}

.calendar-cell--empty {
    visibility: hidden;
}

.calendar-day {
    height: 100%;
    padding: 18rpx 16rpx;
    border-radius: 24rpx;
    border: 1rpx solid rgba(219, 39, 119, 0.10);
    background: linear-gradient(180deg, #fffafc, #fffefe);
    box-sizing: border-box;
}

.calendar-day--available {
    background: linear-gradient(180deg, #fffafc, #ffffff);
}

.calendar-day--unavailable {
    background: linear-gradient(180deg, #fff8eb, #fffdf6);
    border-color: rgba(202, 138, 4, 0.18);
}

.calendar-day--locked {
    background: linear-gradient(180deg, #f7f5ff, #fbfbff);
    border-color: rgba(99, 102, 241, 0.16);
}

.calendar-day--occupied {
    background: linear-gradient(180deg, #fff2f5, #fff8fb);
    border-color: rgba(225, 29, 72, 0.16);
}

.calendar-day--selected {
    box-shadow: inset 0 0 0 2rpx rgba(219, 39, 119, 0.48);
}

.calendar-day--readonly {
    opacity: 0.92;
}

.calendar-day__top {
    display: flex;
    justify-content: space-between;
    gap: 8rpx;
    align-items: center;
}

.calendar-day__date {
    color: #111827;
    font-size: 32rpx;
    font-weight: 600;
}

.calendar-day__today {
    padding: 4rpx 12rpx;
    border-radius: 999rpx;
    background: rgba(219, 39, 119, 0.10);
    color: #9d174d;
    font-size: 18rpx;
}

.calendar-day__status {
    margin-top: 12rpx;
    color: #1f2937;
    font-size: 24rpx;
    font-weight: 600;
}

.calendar-day__hint,
.calendar-day__source {
    margin-top: 10rpx;
    color: #6b7280;
    font-size: 20rpx;
    line-height: 1.6;
}

.readonly-list {
    margin-top: 20rpx;
    display: grid;
    gap: 14rpx;
}

.readonly-item {
    display: grid;
    grid-template-columns: 180rpx 140rpx minmax(0, 1fr);
    gap: 12rpx;
    padding: 22rpx;
    border-radius: 22rpx;
    background: linear-gradient(180deg, #fff8fb, #fffdf9);
    border: 1rpx solid rgba(219, 39, 119, 0.10);
}

.readonly-item__date,
.readonly-item__status,
.readonly-item__desc {
    font-size: 22rpx;
    line-height: 1.7;
}

.readonly-item__date {
    color: #111827;
    font-weight: 600;
}

.readonly-item__status {
    color: #9d174d;
}

.readonly-item__desc {
    color: #6b7280;
}
</style>
