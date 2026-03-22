<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <w-page-nav />
    <view class="provider-profile-manage-page min-h-screen px-[24rpx] py-[24rpx] box-border">
        <view class="hero-card">
            <view class="hero-card__eyebrow">Provider Profile Center</view>
            <view class="hero-card__title">资料中心</view>
        </view>

        <scroll-view class="tab-scroll mt-[24rpx]" scroll-x>
            <view class="tab-row">
                <view
                    v-for="item in tabs"
                    :key="item.value"
                    class="tab-pill"
                    :class="{ 'tab-pill--active': activeTab === item.value }"
                    @click="activeTab = item.value"
                >
                    {{ item.label }}
                </view>
            </view>
        </scroll-view>

        <view class="panel-card mt-[24rpx]">
            <view class="panel-card__title">当前生效数据</view>

            <template v-if="activeTab === 'profile'">
                <view class="profile-form mt-[20rpx]">
                    <view class="form-block">
                        <view class="form-label">头像</view>
                        <avatar-upload v-model="profileForm.avatar" :size="120" :round="24" />
                    </view>
                    <view class="form-block">
                        <view class="form-label">服务名称</view>
                        <input v-model="profileForm.name" class="field-input" placeholder="请输入服务名称" />
                    </view>
                    <view class="form-block">
                        <view class="form-label">联系电话</view>
                        <input v-model="profileForm.mobile" class="field-input" placeholder="请输入联系电话" />
                    </view>
                    <view class="form-block">
                        <view class="form-label">企微接收账号</view>
                        <input
                            v-model="profileForm.work_wechat_userid"
                            class="field-input"
                            placeholder="请输入企业微信 userid"
                        />
                    </view>
                    <view class="form-block">
                        <view class="form-label">风格标签 ID</view>
                        <input v-model="tagIdsText" class="field-input" placeholder="例如：1,2,3" />
                        <view class="field-tip">当前按标签 ID 提交，保持与后台主档口径一致。</view>
                    </view>
                    <view class="form-block">
                        <view class="form-label">服务简介</view>
                        <textarea
                            v-model="profileForm.intro"
                            class="field-textarea"
                            placeholder="请输入服务简介"
                        />
                    </view>
                </view>
            </template>

            <template v-else>
                <view class="json-editor mt-[20rpx]">
                    <textarea
                        v-if="activeTab === 'certificate'"
                        v-model="certificatesText"
                        class="json-editor__textarea"
                        placeholder="请维护证书 JSON 数组"
                    />
                    <textarea
                        v-else-if="activeTab === 'work'"
                        v-model="worksText"
                        class="json-editor__textarea"
                        placeholder="请维护作品 JSON 数组"
                    />
                    <textarea
                        v-else
                        v-model="packagesText"
                        class="json-editor__textarea"
                        placeholder="请维护套餐 JSON 数组"
                    />
                    <view class="field-tip mt-[16rpx]">
                        <text v-if="activeTab === 'certificate'">
                            证书项建议字段：certificate_name、certificate_image、issuing_authority、issue_date、expire_date、description、status、sort。
                        </text>
                        <text v-else-if="activeTab === 'work'">
                            作品项建议字段：title、cover、images、content、status、sort。
                        </text>
                        <text v-else>
                            套餐项建议字段：name、summary、service_duration、status、sort、area_prices。
                        </text>
                    </view>
                </view>
            </template>

            <button class="submit-btn mt-[24rpx]" @click="handleSubmitCurrent">提交当前页审核</button>
        </view>

        <view class="panel-card mt-[24rpx]">
            <view class="panel-card__title">待审核版本</view>
            <view v-if="!currentPending" class="empty-tip">当前标签暂无待审核变更。</view>
            <template v-else>
                <view class="pending-badge">状态：{{ currentPending.audit_status_desc || '待审核' }}</view>
                <view class="pending-meta">提交时间：{{ formatTime(currentPending.create_time) }}</view>
                <view class="json-preview mt-[20rpx]">{{ formatJson(currentPending.after_snapshot || {}) }}</view>
            </template>
        </view>

        <view class="panel-card mt-[24rpx]">
            <view class="panel-card__title">最近变更记录</view>
            <view v-if="!changeLists.length" class="empty-tip">暂未提交过变更申请。</view>
            <view v-else class="history-list">
                <view v-for="item in changeLists" :key="item.id" class="history-item">
                    <view class="history-item__top">
                        <view class="history-item__title">{{ changeTypeMap[item.change_type] || item.change_type }}</view>
                        <view class="history-item__status">{{ item.audit_status_desc || '-' }}</view>
                    </view>
                    <view class="history-item__meta">{{ formatTime(item.create_time) }}</view>
                    <view class="history-item__remark">{{ item.audit_remark || '等待管理员处理' }}</view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import {
    getProviderCenterDetail,
    getProviderChangeRequestLists,
    submitProviderChangeRequest
} from '@/api/wedding'
import AvatarUpload from '@/components/avatar-upload/avatar-upload.vue'
import { onShow } from '@dcloudio/uni-app'
import { computed, reactive, ref, watch } from 'vue'

type TabValue = 'profile' | 'certificate' | 'work' | 'package'

const tabs = [
    { label: '资料', value: 'profile' as TabValue },
    { label: '证书', value: 'certificate' as TabValue },
    { label: '作品', value: 'work' as TabValue },
    { label: '套餐', value: 'package' as TabValue }
]

const changeTypeMap: Record<string, string> = {
    profile: '资料',
    certificate: '证书',
    work: '作品',
    package: '套餐'
}

const activeTab = ref<TabValue>('profile')
const detail = reactive<any>({
    provider: {},
    certificates: [],
    works: [],
    packages: [],
    pending_map: {}
})
const changeLists = ref<any[]>([])

const profileForm = reactive({
    name: '',
    avatar: '',
    mobile: '',
    work_wechat_userid: '',
    intro: ''
})
const tagIdsText = ref('')
const certificatesText = ref('[]')
const worksText = ref('[]')
const packagesText = ref('[]')

const currentPending = computed(() => detail.pending_map?.[activeTab.value] || null)

const formatTime = (value: number | string) => {
    if (!value || Number(value) <= 0) {
        return '-'
    }
    const time = new Date(Number(value) * 1000)
    return `${time.getFullYear()}-${String(time.getMonth() + 1).padStart(2, '0')}-${String(time.getDate()).padStart(2, '0')} ${String(
        time.getHours()
    ).padStart(2, '0')}:${String(time.getMinutes()).padStart(2, '0')}`
}

const formatJson = (value: any) => JSON.stringify(value || {}, null, 2)

const syncForms = () => {
    Object.assign(profileForm, {
        name: detail.provider?.name || '',
        avatar: detail.provider?.avatar || '',
        mobile: detail.provider?.mobile || '',
        work_wechat_userid: detail.provider?.work_wechat_userid || '',
        intro: detail.provider?.intro || ''
    })
    tagIdsText.value = Array.isArray(detail.provider?.tag_ids) ? detail.provider.tag_ids.join(',') : ''
    certificatesText.value = formatJson(detail.certificates || [])
    worksText.value = formatJson(detail.works || [])
    packagesText.value = formatJson(detail.packages || [])
}

const loadDetail = async () => {
    const data = await getProviderCenterDetail()
    Object.assign(detail, data || {})
    syncForms()
}

const loadChangeLists = async () => {
    const data = await getProviderChangeRequestLists({
        page_no: 1,
        page_size: 20
    })
    changeLists.value = data?.lists || []
}

const parseJsonText = (text: string, label: string) => {
    try {
        const parsed = JSON.parse(text || '[]')
        if (!Array.isArray(parsed)) {
            throw new Error()
        }
        return parsed
    } catch (error) {
        uni.showToast({ title: `${label} JSON 格式不正确`, icon: 'none' })
        throw error
    }
}

const handleSubmitCurrent = async () => {
    let payload: Record<string, any> | any[] = {}

    if (activeTab.value === 'profile') {
        payload = {
            ...profileForm,
            tag_ids: tagIdsText.value
                .split(',')
                .map((item) => Number(item.trim()))
                .filter((item) => item > 0)
        }
    }

    if (activeTab.value === 'certificate') {
        payload = parseJsonText(certificatesText.value, '证书')
    }

    if (activeTab.value === 'work') {
        payload = parseJsonText(worksText.value, '作品')
    }

    if (activeTab.value === 'package') {
        payload = parseJsonText(packagesText.value, '套餐')
    }

    await submitProviderChangeRequest({
        change_type: activeTab.value,
        payload
    })
    uni.showToast({ title: '已提交审核', icon: 'success' })
    await Promise.all([loadDetail(), loadChangeLists()])
}

watch(activeTab, () => {
    syncForms()
})

onShow(async () => {
    await Promise.all([loadDetail(), loadChangeLists()])
})
</script>

<style lang="scss" scoped>
.provider-profile-manage-page {
    background:
        radial-gradient(circle at top left, rgba(219, 39, 119, 0.14), transparent 28%),
        radial-gradient(circle at right bottom, rgba(202, 138, 4, 0.12), transparent 24%),
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

.tab-scroll {
    white-space: nowrap;
}

.tab-row {
    display: inline-flex;
    gap: 16rpx;
}

.tab-pill {
    padding: 14rpx 28rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.88);
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    color: #6b7280;
    font-size: 24rpx;
}

.tab-pill--active {
    background: linear-gradient(135deg, rgba(219, 39, 119, 0.14), rgba(202, 138, 4, 0.14));
    color: #9d174d;
    border-color: rgba(202, 138, 4, 0.18);
}

.panel-card__title {
    color: #111827;
    font-size: 30rpx;
    font-weight: 600;
}

.profile-form,
.history-list {
    display: grid;
    gap: 20rpx;
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
.field-textarea,
.json-editor__textarea,
.json-preview {
    width: 100%;
    box-sizing: border-box;
    padding: 22rpx 24rpx;
    border-radius: 22rpx;
    background: #fffafc;
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    color: #1f2937;
    font-size: 26rpx;
    line-height: 1.7;
}

.field-textarea,
.json-editor__textarea,
.json-preview {
    min-height: 220rpx;
}

.field-tip,
.pending-meta,
.empty-tip {
    color: #9ca3af;
    font-size: 22rpx;
    line-height: 1.8;
}

.submit-btn {
    width: 100%;
    border-radius: 999rpx;
    background: linear-gradient(135deg, #db2777, #ca8a04);
    color: #ffffff;
    font-size: 28rpx;
    font-weight: 600;
    line-height: 88rpx;
}

.pending-badge {
    display: inline-flex;
    padding: 10rpx 18rpx;
    border-radius: 999rpx;
    background: rgba(202, 138, 4, 0.12);
    color: #a16207;
    font-size: 22rpx;
    font-weight: 600;
}

.history-item {
    padding: 22rpx 24rpx;
    border-radius: 22rpx;
    background: #fffafc;
    border: 1rpx solid rgba(219, 39, 119, 0.1);
}

.history-item__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.history-item__title {
    color: #111827;
    font-size: 28rpx;
    font-weight: 600;
}

.history-item__status {
    color: #9d174d;
    font-size: 22rpx;
}

.history-item__meta,
.history-item__remark {
    margin-top: 10rpx;
    color: #6b7280;
    font-size: 24rpx;
    line-height: 1.7;
}
</style>
