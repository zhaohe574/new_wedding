<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <w-page-nav />
    <z-paging
        ref="paging"
        v-model="noticeLists"
        @query="queryList"
        :show-loading-more-when-reload="true"
    >
        <view class="notice-center-page min-h-screen px-[24rpx] py-[24rpx] box-border">
            <view class="hero-card">
                <view class="hero-card__eyebrow">Notice Center</view>
                <view class="hero-card__title">通知中心</view>
                <view class="hero-actions">
                    <view class="hero-badge">未读 {{ unreadCount }}</view>
                    <button class="hero-btn" @click="handleReadAll">全部已读</button>
                </view>
            </view>

            <view v-if="!noticeLists.length" class="state-card mt-[24rpx]">当前暂无通知记录。</view>
            <view v-else class="notice-grid mt-[24rpx]">
                <view
                    v-for="(item, index) in noticeLists"
                    :key="item.id"
                    class="notice-card"
                    :class="{ 'notice-card--unread': Number(item.read) === 0 }"
                    @click="openNotice(item, index)"
                >
                    <view class="notice-card__top">
                        <view class="notice-card__title">{{ item.title || '系统通知' }}</view>
                        <view class="notice-card__status">{{ Number(item.read) === 0 ? '未读' : '已读' }}</view>
                    </view>
                    <view class="notice-card__content">{{ item.content || '-' }}</view>
                    <view class="notice-card__time">{{ formatTime(item.create_time) }}</view>
                </view>
            </view>
        </view>
    </z-paging>
</template>

<script setup lang="ts">
import { getNoticeLists, readAllNotice, readNotice } from '@/api/notice'
import { shallowRef, ref } from 'vue'

const paging = shallowRef()
const noticeLists = ref<any[]>([])
const unreadCount = ref(0)

const formatTime = (value: number | string) => {
    if (!value || Number(value) <= 0) {
        return '-'
    }
    const time = new Date(Number(value) * 1000)
    return `${time.getFullYear()}-${String(time.getMonth() + 1).padStart(2, '0')}-${String(time.getDate()).padStart(2, '0')} ${String(
        time.getHours()
    ).padStart(2, '0')}:${String(time.getMinutes()).padStart(2, '0')}`
}

const queryList = async (pageNo: number, pageSize: number) => {
    try {
        const data = await getNoticeLists({
            page_no: pageNo,
            page_size: pageSize
        })
        unreadCount.value = Number(data?.unread_count || 0)
        paging.value.complete(data?.lists || [])
    } catch (error) {
        paging.value.complete(false)
    }
}

const markRead = async (item: any, index: number) => {
    if (Number(item.read) === 1) {
        return
    }
    await readNotice({ id: item.id })
    noticeLists.value[index] = {
        ...item,
        read: 1
    }
    unreadCount.value = Math.max(0, unreadCount.value - 1)
}

const openNotice = async (item: any, index: number) => {
    await markRead(item, index)
    const path = item?.extra_data?.path || ''
    if (!path) {
        return
    }
    uni.navigateTo({
        url: path
    })
}

const handleReadAll = async () => {
    if (!unreadCount.value) {
        uni.showToast({ title: '当前没有未读通知', icon: 'none' })
        return
    }
    await readAllNotice()
    unreadCount.value = 0
    noticeLists.value = noticeLists.value.map((item) => ({
        ...item,
        read: 1
    }))
    uni.showToast({ title: '已全部标记为已读', icon: 'success' })
}
</script>

<style lang="scss" scoped>
.notice-center-page {
    background:
        radial-gradient(circle at top right, rgba(219, 39, 119, 0.1), transparent 26%),
        linear-gradient(180deg, #fffafc, #f8f3ef 44%, #f7f3f0);
}

.hero-card,
.state-card,
.notice-card {
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    border-radius: 28rpx;
    box-shadow: 0 18rpx 48rpx rgba(31, 41, 55, 0.06);
}

.hero-card,
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

.state-card {
    margin-top: 16rpx;
    color: #6b7280;
    font-size: 24rpx;
    line-height: 1.8;
}

.hero-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20rpx;
    margin-top: 24rpx;
}

.hero-badge {
    padding: 10rpx 22rpx;
    border-radius: 999rpx;
    background: rgba(219, 39, 119, 0.08);
    color: #9d174d;
    font-size: 22rpx;
}

.hero-btn {
    margin: 0;
    padding: 0 28rpx;
    height: 72rpx;
    line-height: 72rpx;
    border-radius: 999rpx;
    background: linear-gradient(135deg, #db2777, #ca8a04);
    color: #fff;
    font-size: 24rpx;
    font-weight: 600;
}

.notice-grid {
    display: grid;
    gap: 18rpx;
}

.notice-card {
    padding: 24rpx;
}

.notice-card--unread {
    border-color: rgba(219, 39, 119, 0.24);
    box-shadow: 0 22rpx 52rpx rgba(219, 39, 119, 0.08);
}

.notice-card__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.notice-card__title {
    color: #111827;
    font-size: 28rpx;
    font-weight: 600;
}

.notice-card__status {
    color: #9d174d;
    font-size: 22rpx;
}

.notice-card__content {
    margin-top: 14rpx;
    color: #4b5563;
    font-size: 24rpx;
    line-height: 1.8;
}

.notice-card__time {
    margin-top: 18rpx;
    color: #9ca3af;
    font-size: 22rpx;
}
</style>
