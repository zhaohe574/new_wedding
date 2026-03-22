<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <w-page-nav />
    <view class="provider-public-posts-page min-h-screen px-[24rpx] py-[24rpx] box-border">
        <view class="hero-card">
            <view class="hero-card__eyebrow">Provider Stories</view>
            <view class="hero-card__title">服务动态</view>
        </view>

        <view v-if="loading" class="state-card mt-[24rpx]">正在加载服务动态...</view>
        <template v-else>
            <view v-if="!postLists.length" class="state-card mt-[24rpx]">当前服务人员暂未公开动态。</view>
            <view v-else class="post-list mt-[24rpx]">
                <view v-for="item in postLists" :key="item.id" class="post-card">
                    <view class="post-card__title">{{ item.title || '-' }}</view>
                    <view class="post-card__content">{{ item.content || '暂无内容' }}</view>

                    <view v-if="item.images?.length" class="image-grid">
                        <image
                            v-for="(image, index) in item.images"
                            :key="image + index"
                            class="image-grid__item"
                            :src="image"
                            mode="aspectFill"
                            @click="previewImages(item.images, index)"
                        />
                    </view>

                    <view class="post-card__meta">{{ formatTime(item.create_time) }}</view>

                    <view class="comment-section">
                        <view class="comment-section__title">公开评论</view>
                        <view v-if="!item.comments?.length" class="comment-empty">暂无公开评论</view>
                        <view v-else class="comment-list">
                            <view v-for="comment in item.comments" :key="comment.id" class="comment-item">
                                <view class="comment-item__user">{{ comment.user_nickname || '匿名用户' }}</view>
                                <view class="comment-item__content">{{ comment.content || '-' }}</view>
                                <view class="comment-item__meta">{{ formatTime(comment.create_time) }}</view>
                            </view>
                        </view>

                        <view class="comment-form">
                            <textarea
                                v-model="commentMap[item.id]"
                                class="comment-form__textarea"
                                placeholder="说说你对这条动态的看法"
                            />
                            <button class="comment-form__btn" @click="submitComment(item.id)">提交评论</button>
                        </view>
                    </view>
                </view>
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
import { createProviderPostComment, getProviderPublicPostLists } from '@/api/wedding'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { reactive, ref } from 'vue'

const loading = ref(false)
const providerId = ref(0)
const postLists = ref<any[]>([])
const commentMap = reactive<Record<number, string>>({})

const formatTime = (value: number | string) => {
    if (!value || Number(value) <= 0) {
        return '-'
    }
    const time = new Date(Number(value) * 1000)
    return `${time.getFullYear()}-${String(time.getMonth() + 1).padStart(2, '0')}-${String(time.getDate()).padStart(2, '0')} ${String(
        time.getHours()
    ).padStart(2, '0')}:${String(time.getMinutes()).padStart(2, '0')}`
}

const loadPosts = async () => {
    if (!providerId.value) {
        return
    }
    loading.value = true
    try {
        const data = await getProviderPublicPostLists({
            provider_id: providerId.value,
            page_no: 1,
            page_size: 20
        })
        postLists.value = data?.lists || []
    } finally {
        loading.value = false
    }
}

const previewImages = (urls: string[], current: number) => {
    uni.previewImage({
        urls,
        current
    })
}

const submitComment = async (postId: number) => {
    const content = (commentMap[postId] || '').trim()
    if (!content) {
        uni.showToast({ title: '请输入评论内容', icon: 'none' })
        return
    }
    await createProviderPostComment({
        post_id: postId,
        comment_content: content,
        parent_id: 0
    })
    commentMap[postId] = ''
    uni.showToast({ title: '评论已提交审核', icon: 'success' })
    await loadPosts()
}

onLoad((options) => {
    providerId.value = Number(options?.provider_id || 0)
})

onShow(async () => {
    await loadPosts()
})
</script>

<style lang="scss" scoped>
.provider-public-posts-page {
    background:
        radial-gradient(circle at top right, rgba(219, 39, 119, 0.12), transparent 28%),
        radial-gradient(circle at left bottom, rgba(202, 138, 4, 0.12), transparent 24%),
        linear-gradient(180deg, #fff7fb, #fbf6f2 46%, #f7f2ef);
}

.hero-card,
.state-card,
.post-card {
    padding: 32rpx 30rpx;
    border-radius: 28rpx;
    background: rgba(255, 255, 255, 0.94);
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    box-shadow: 0 18rpx 48rpx rgba(31, 41, 55, 0.06);
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

.state-card {
    margin-top: 16rpx;
    color: #6b7280;
    font-size: 26rpx;
    line-height: 1.8;
}

.post-list {
    display: grid;
    gap: 20rpx;
}

.post-card__title {
    color: #111827;
    font-size: 32rpx;
    font-weight: 600;
}

.post-card__content,
.post-card__meta {
    margin-top: 12rpx;
    color: #6b7280;
    font-size: 24rpx;
    line-height: 1.8;
}

.image-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18rpx;
    margin-top: 20rpx;
}

.image-grid__item {
    width: 100%;
    height: 180rpx;
    border-radius: 24rpx;
    background: #f3f4f6;
}

.comment-section {
    margin-top: 24rpx;
    padding-top: 24rpx;
    border-top: 1rpx solid rgba(219, 39, 119, 0.08);
}

.comment-section__title {
    color: #111827;
    font-size: 28rpx;
    font-weight: 600;
}

.comment-empty {
    margin-top: 12rpx;
    color: #9ca3af;
    font-size: 24rpx;
}

.comment-list {
    display: grid;
    gap: 14rpx;
    margin-top: 16rpx;
}

.comment-item {
    padding: 20rpx 22rpx;
    border-radius: 22rpx;
    background: #fffafc;
    border: 1rpx solid rgba(219, 39, 119, 0.08);
}

.comment-item__user {
    color: #111827;
    font-size: 26rpx;
    font-weight: 600;
}

.comment-item__content,
.comment-item__meta {
    margin-top: 8rpx;
    color: #6b7280;
    font-size: 24rpx;
    line-height: 1.7;
}

.comment-form {
    display: grid;
    gap: 16rpx;
    margin-top: 18rpx;
}

.comment-form__textarea {
    width: 100%;
    min-height: 160rpx;
    box-sizing: border-box;
    padding: 22rpx 24rpx;
    border-radius: 22rpx;
    background: #fffafc;
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    color: #1f2937;
    font-size: 26rpx;
}

.comment-form__btn {
    width: 100%;
    border-radius: 999rpx;
    background: linear-gradient(135deg, #db2777, #ca8a04);
    color: #ffffff;
    font-size: 28rpx;
    font-weight: 600;
    line-height: 84rpx;
}
</style>
