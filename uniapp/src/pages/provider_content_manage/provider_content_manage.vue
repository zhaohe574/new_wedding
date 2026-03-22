<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <w-page-nav />
    <view class="provider-content-manage-page min-h-screen px-[24rpx] py-[24rpx] box-border">
        <view class="hero-card">
            <view class="hero-card__eyebrow">Provider Content Center</view>
            <view class="hero-card__title">内容互动中心</view>
        </view>

        <view class="panel-card mt-[24rpx]">
            <view class="panel-card__title">发布动态</view>
            <view class="form-block mt-[20rpx]">
                <view class="form-label">动态标题</view>
                <input v-model="postForm.title" class="field-input" placeholder="请输入动态标题" />
            </view>
            <view class="form-block mt-[18rpx]">
                <view class="form-label">动态内容</view>
                <textarea v-model="postForm.content" class="field-textarea" placeholder="请输入动态内容" />
            </view>
            <view class="form-block mt-[18rpx]">
                <view class="form-label">动态图片</view>
                <view class="image-grid">
                    <image
                        v-for="(item, index) in postForm.images"
                        :key="item + index"
                        class="image-grid__item"
                        :src="item"
                        mode="aspectFill"
                        @click="previewImages(index)"
                    />
                    <view class="image-grid__add" @click="handleChooseImages">添加图片</view>
                </view>
            </view>
            <button class="submit-btn mt-[24rpx]" @click="submitPost">提交动态</button>
        </view>

        <view class="panel-card mt-[24rpx]">
            <view class="panel-card__title">我的动态</view>
            <view v-if="!postLists.length" class="empty-tip">暂未发布动态。</view>
            <view v-else class="post-list">
                <view v-for="item in postLists" :key="item.id" class="post-item">
                    <view class="post-item__top">
                        <view class="post-item__title">{{ item.title }}</view>
                        <view class="post-item__status">{{ item.audit_status_desc || '-' }}</view>
                    </view>
                    <view class="post-item__content">{{ item.content || '暂无内容' }}</view>
                    <view class="post-item__meta">
                        <text>评论 {{ item.comment_count || 0 }}</text>
                        <text> · {{ formatTime(item.create_time) }}</text>
                    </view>
                    <view class="post-item__actions">
                        <text class="action-link" @click="handleDeletePost(item.id)">删除</text>
                    </view>
                </view>
            </view>
        </view>

        <view class="panel-card mt-[24rpx]">
            <view class="panel-card__title">待我处理的评论</view>
            <view v-if="!pendingComments.length" class="empty-tip">当前没有待处理评论。</view>
            <view v-else class="comment-list">
                <view v-for="item in pendingComments" :key="item.id" class="comment-item">
                    <view class="comment-item__title">{{ item.post_title || '未命名动态' }}</view>
                    <view class="comment-item__meta">{{ item.user_nickname || '匿名用户' }} · {{ formatTime(item.create_time) }}</view>
                    <view class="comment-item__content">{{ item.content || '-' }}</view>
                    <view class="comment-item__actions">
                        <button class="mini-btn mini-btn--line" @click="handleAuditComment(item.id, 2)">驳回</button>
                        <button class="mini-btn" @click="handleAuditComment(item.id, 1)">通过</button>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { uploadImage } from '@/api/app'
import {
    auditProviderComment,
    deleteProviderPost,
    getProviderCommentPendingLists,
    getProviderPostLists,
    saveProviderPost
} from '@/api/wedding'
import { useUserStore } from '@/stores/user'
import { onShow } from '@dcloudio/uni-app'
import { reactive, ref } from 'vue'

const userStore = useUserStore()
const postLists = ref<any[]>([])
const pendingComments = ref<any[]>([])
const postForm = reactive({
    title: '',
    content: '',
    images: [] as string[]
})

const formatTime = (value: number | string) => {
    if (!value || Number(value) <= 0) {
        return '-'
    }
    const time = new Date(Number(value) * 1000)
    return `${time.getFullYear()}-${String(time.getMonth() + 1).padStart(2, '0')}-${String(time.getDate()).padStart(2, '0')} ${String(
        time.getHours()
    ).padStart(2, '0')}:${String(time.getMinutes()).padStart(2, '0')}`
}

const loadData = async () => {
    const [postData, commentData] = await Promise.all([
        getProviderPostLists({
            page_no: 1,
            page_size: 20
        }),
        getProviderCommentPendingLists({
            page_no: 1,
            page_size: 20,
            only_pending: 1
        })
    ])

    postLists.value = postData?.lists || []
    pendingComments.value = commentData?.lists || []
}

const handleChooseImages = async () => {
    if (postForm.images.length >= 9) {
        uni.showToast({ title: '最多上传 9 张图片', icon: 'none' })
        return
    }
    const chooseResult = await uni.chooseImage({
        count: 9 - postForm.images.length,
        sizeType: ['compressed']
    })

    for (const filePath of chooseResult.tempFilePaths || []) {
        uni.showLoading({ title: '上传中...' })
        try {
            const uploadResult: any = await uploadImage(filePath, userStore.token || userStore.temToken || '')
            if (uploadResult?.uri) {
                postForm.images.push(uploadResult.uri)
            }
        } finally {
            uni.hideLoading()
        }
    }
}

const previewImages = (current: number) => {
    uni.previewImage({
        urls: postForm.images,
        current
    })
}

const submitPost = async () => {
    if (!postForm.title.trim()) {
        uni.showToast({ title: '请输入动态标题', icon: 'none' })
        return
    }
    if (!postForm.content.trim()) {
        uni.showToast({ title: '请输入动态内容', icon: 'none' })
        return
    }

    await saveProviderPost({
        title: postForm.title,
        content: postForm.content,
        cover: postForm.images[0] || '',
        images: postForm.images,
        status: 1
    })
    uni.showToast({ title: '动态已提交', icon: 'success' })
    Object.assign(postForm, {
        title: '',
        content: '',
        images: []
    })
    await loadData()
}

const handleDeletePost = async (id: number) => {
    const res = await uni.showModal({
        title: '确认删除',
        content: '删除后将不再展示该动态，是否继续？'
    })
    if (!res.confirm) {
        return
    }
    await deleteProviderPost({ id })
    uni.showToast({ title: '已删除', icon: 'success' })
    await loadData()
}

const handleAuditComment = async (commentId: number, auditStatus: 1 | 2) => {
    await auditProviderComment({
        comment_id: commentId,
        audit_status: auditStatus,
        audit_remark: auditStatus === 1 ? '服务人员审核通过' : '服务人员审核驳回'
    })
    uni.showToast({ title: auditStatus === 1 ? '已通过' : '已驳回', icon: 'success' })
    await loadData()
}

onShow(async () => {
    await loadData()
})
</script>

<style lang="scss" scoped>
.provider-content-manage-page {
    background:
        radial-gradient(circle at top right, rgba(219, 39, 119, 0.12), transparent 28%),
        radial-gradient(circle at left bottom, rgba(202, 138, 4, 0.12), transparent 24%),
        linear-gradient(180deg, #fff7fb, #fbf6f2 46%, #f7f2ef);
}

.hero-card,
.panel-card {
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

.panel-card__title {
    color: #111827;
    font-size: 30rpx;
    font-weight: 600;
}

.form-block {
    display: grid;
    gap: 12rpx;
}

.form-label {
    color: #6b7280;
    font-size: 24rpx;
}

.field-input,
.field-textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 22rpx 24rpx;
    border-radius: 22rpx;
    background: #fffafc;
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    color: #1f2937;
    font-size: 26rpx;
}

.field-textarea {
    min-height: 220rpx;
}

.image-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18rpx;
}

.image-grid__item,
.image-grid__add {
    width: 100%;
    height: 150rpx;
    border-radius: 24rpx;
}

.image-grid__item {
    background: #f3f4f6;
}

.image-grid__add {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(180deg, #fff8fb, #fffef9);
    border: 1rpx dashed rgba(219, 39, 119, 0.24);
    color: #9d174d;
    font-size: 24rpx;
}

.submit-btn,
.mini-btn {
    border-radius: 999rpx;
    background: linear-gradient(135deg, #db2777, #ca8a04);
    color: #ffffff;
}

.submit-btn {
    width: 100%;
    font-size: 28rpx;
    font-weight: 600;
    line-height: 88rpx;
}

.mini-btn {
    min-width: 140rpx;
    font-size: 24rpx;
    line-height: 68rpx;
}

.mini-btn--line {
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(219, 39, 119, 0.16);
    color: #9d174d;
}

.empty-tip {
    margin-top: 18rpx;
    color: #9ca3af;
    font-size: 24rpx;
    line-height: 1.8;
}

.post-list,
.comment-list {
    display: grid;
    gap: 20rpx;
    margin-top: 20rpx;
}

.post-item,
.comment-item {
    padding: 22rpx 24rpx;
    border-radius: 24rpx;
    background: #fffafc;
    border: 1rpx solid rgba(219, 39, 119, 0.1);
}

.post-item__top,
.comment-item__actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.post-item__title,
.comment-item__title {
    color: #111827;
    font-size: 28rpx;
    font-weight: 600;
}

.post-item__status {
    color: #9d174d;
    font-size: 22rpx;
}

.post-item__content,
.comment-item__content,
.post-item__meta,
.comment-item__meta {
    margin-top: 10rpx;
    color: #6b7280;
    font-size: 24rpx;
    line-height: 1.7;
}

.post-item__actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 14rpx;
}

.action-link {
    color: #be123c;
    font-size: 24rpx;
}
</style>
