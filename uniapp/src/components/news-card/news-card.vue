<template>
    <navigator class="news-card" :url="`/pages/news_detail/news_detail?id=${newsId}`">
        <view class="news-card__inner">
            <view class="news-card__media" v-if="item.image">
                <u-image :src="item.image" width="220" height="170" border-radius="20"></u-image>
            </view>
            <view class="news-card__content">
                <view class="news-card__tag">婚礼动态</view>
                <view class="news-card__title">{{ item.title }}</view>

                <view class="news-card__meta">
                    <view>{{ item.create_time }}</view>
                    <view class="news-card__meta-right">
                        <image
                            src="/static/images/icon/icon_visit.png"
                            class="news-card__visit-icon"
                        ></image>
                        <view class="news-card__visit-text">{{ item.click }}</view>
                    </view>
                </view>
            </view>
        </view>
    </navigator>
</template>

<script lang="ts" setup>
withDefaults(
    defineProps<{
        item: any
        newsId: number
    }>(),
    {
        item: {},
        newsId: 0
    }
)
</script>

<style lang="scss" scoped>
.news-card {
    display: block;
    margin: 0 24rpx 18rpx;
}

.news-card__inner {
    display: flex;
    gap: 20rpx;
    padding: 24rpx;
    border-radius: 28rpx;
    background: rgba(255, 255, 255, 0.94);
    border: 1rpx solid rgba(219, 39, 119, 0.1);
    box-shadow: 0 18rpx 48rpx rgba(31, 41, 55, 0.06);
}

.news-card__media {
    flex-shrink: 0;
}

.news-card__content {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.news-card__tag {
    color: #9d174d;
    font-size: 20rpx;
    letter-spacing: 3rpx;
}

.news-card__title {
    margin-top: 12rpx;
    color: #111827;
    font-size: 30rpx;
    font-weight: 600;
    -webkit-line-clamp: 2;
    overflow: hidden;
    word-break: break-all;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-box-orient: vertical;
}

.news-card__meta {
    margin-top: auto;
    padding-top: 16rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    color: #9ca3af;
    font-size: 22rpx;
}

.news-card__meta-right {
    display: flex;
    align-items: center;
}

.news-card__visit-icon {
    width: 28rpx;
    height: 28rpx;
}

.news-card__visit-text {
    margin-left: 10rpx;
}

@media screen and (max-width: 360px) {
    .news-card__inner {
        flex-direction: column;
    }

    .news-card__media {
        width: 100%;
    }
}
</style>
