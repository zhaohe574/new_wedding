<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <w-page-nav />
    <view class="wedding-template-form-page min-h-screen px-[24rpx] py-[24rpx] box-border">
        <view class="hero-card">
            <view class="hero-card__eyebrow">Template Form</view>
            <view class="hero-card__title">服务内容填写</view>
            <view class="hero-card__meta">{{ selectionSummary }}</view>
        </view>

        <view v-if="loading" class="state-card mt-[24rpx]">正在加载模板与婚礼档案...</view>
        <template v-else-if="template.pages.length">
            <scroll-view class="step-scroll mt-[24rpx]" scroll-x enable-flex>
                <view
                    v-for="(page, index) in template.pages"
                    :key="page.id || index"
                    class="step-chip"
                    :class="{ 'step-chip--active': activePageIndex === index }"
                >
                    {{ index + 1 }}. {{ page.title }}
                </view>
            </scroll-view>

            <view class="panel-card mt-[24rpx]">
                <view class="panel-card__title">{{ currentPage.title }}</view>

                <view class="field-list">
                    <view v-for="field in currentPage.fields || []" :key="field.field_key" class="field-item">
                        <view class="field-item__label">
                            {{ field.label }}
                            <text v-if="Number(field.required || 0) === 1" class="field-item__required">*</text>
                        </view>

                        <input
                            v-if="field.field_type === 'text'"
                            v-model="formData[field.field_key]"
                            class="field-input"
                            :placeholder="field.placeholder || `请输入${field.label}`"
                        />

                        <input
                            v-else-if="field.field_type === 'number'"
                            v-model="formData[field.field_key]"
                            class="field-input"
                            type="digit"
                            :placeholder="field.placeholder || `请输入${field.label}`"
                        />

                        <textarea
                            v-else-if="field.field_type === 'textarea'"
                            v-model="formData[field.field_key]"
                            class="field-textarea"
                            :placeholder="field.placeholder || `请输入${field.label}`"
                        />

                        <view v-else class="choice-group">
                            <view
                                v-for="option in field.options || []"
                                :key="`${field.field_key}-${option}`"
                                class="choice-chip"
                                :class="{ 'choice-chip--active': isOptionSelected(field, option) }"
                                @click="handleOptionSelect(field, option)"
                            >
                                {{ option }}
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <view class="action-row mt-[24rpx]">
                <button class="ghost-btn" :disabled="activePageIndex === 0" @click="handlePrev">上一页</button>
                <button class="ghost-btn" @click="handleSaveDraft">暂存草稿</button>
            </view>
            <button v-if="!isLastPage" class="action-btn mt-[20rpx]" @click="handleNext">下一页</button>
            <button v-else class="action-btn mt-[20rpx]" @click="handleGoPreview">进入订单预览</button>
        </template>
        <view v-else class="state-card mt-[24rpx]">当前服务分类尚未配置模板，请联系管理员维护。</view>
    </view>
</template>

<script setup lang="ts">
import { getWeddingProfile, getWeddingTemplate } from '@/api/wedding'
import {
    buildWeddingSelectionSummary,
    buildWeddingTradeQueryUrl,
    getWeddingOrderDraft,
    normalizeWeddingTradeQuery,
    patchWeddingOrderDraft,
    type WeddingTradeQuery
} from '@/utils/wedding'
import { onLoad } from '@dcloudio/uni-app'
import { computed, reactive, ref } from 'vue'

const loading = ref(false)
const activePageIndex = ref(0)
const tradeQuery = ref<WeddingTradeQuery>(normalizeWeddingTradeQuery())
const template = reactive<any>({
    id: 0,
    name: '',
    pages: []
})
const formData = reactive<Record<string, any>>({})

const selectionSummary = computed(() => buildWeddingSelectionSummary(tradeQuery.value))
const currentPage = computed(() => template.pages[activePageIndex.value] || { fields: [], title: '' })
const isLastPage = computed(() => activePageIndex.value >= template.pages.length - 1)

const normalizeChoiceValue = (field: any, rawValue: any) => {
    if (field.field_type === 'multiple_choice') {
        if (Array.isArray(rawValue)) {
            return rawValue.filter((item) => (field.options || []).includes(item))
        }

        if (typeof rawValue === 'string') {
            return rawValue
                .split(/[\n,，]/)
                .map((item) => item.trim())
                .filter((item) => (field.options || []).includes(item))
        }

        return []
    }

    const value = Array.isArray(rawValue) ? rawValue[0] : rawValue
    const normalized = String(value || '').trim()
    if (!normalized) {
        return ''
    }

    return (field.options || []).includes(normalized) ? normalized : ''
}

const getProfileDefaultValue = (field: any, profile: Record<string, any>) => {
    const rawValue = profile[field.field_key]
    if (rawValue === undefined || rawValue === null || rawValue === '') {
        return undefined
    }

    if (field.field_type === 'single_choice' || field.field_type === 'multiple_choice') {
        return normalizeChoiceValue(field, rawValue)
    }

    if (field.field_type === 'number') {
        return String(rawValue)
    }

    if (Array.isArray(rawValue)) {
        return rawValue.join('，')
    }

    return String(rawValue)
}

const getFieldDefaultValue = (field: any) => {
    if (field.field_type === 'single_choice' || field.field_type === 'multiple_choice') {
        return normalizeChoiceValue(field, field.default_value)
    }
    return field.default_value || ''
}

const fillFormData = (profile: Record<string, any>, draft: Record<string, any>) => {
    const draftFormData = draft.template_form_data || {}
    ;(template.pages || []).forEach((page: any) => {
        ;(page.fields || []).forEach((field: any) => {
            if (draftFormData[field.field_key] !== undefined) {
                formData[field.field_key] =
                    field.field_type === 'single_choice' || field.field_type === 'multiple_choice'
                        ? normalizeChoiceValue(field, draftFormData[field.field_key])
                        : draftFormData[field.field_key]
                return
            }

            const profileValue = getProfileDefaultValue(field, profile)
            if (profileValue !== undefined) {
                formData[field.field_key] = profileValue
                return
            }

            formData[field.field_key] = getFieldDefaultValue(field)
        })
    })
}

const isFieldEmpty = (field: any) => {
    const value = formData[field.field_key]
    if (field.field_type === 'multiple_choice') {
        return !Array.isArray(value) || !value.length
    }
    return String(value ?? '').trim() === ''
}

const validatePage = (pageIndex: number) => {
    const page = template.pages[pageIndex]
    if (!page) {
        return true
    }

    for (const field of page.fields || []) {
        if (Number(field.required || 0) === 1 && isFieldEmpty(field)) {
            uni.showToast({ title: `请填写${field.label}`, icon: 'none' })
            return false
        }
    }

    return true
}

const validateAllPages = () => {
    for (let pageIndex = 0; pageIndex < template.pages.length; pageIndex++) {
        if (!validatePage(pageIndex)) {
            activePageIndex.value = pageIndex
            return false
        }
    }

    return true
}

const handleOptionSelect = (field: any, option: string) => {
    if (field.field_type === 'single_choice') {
        formData[field.field_key] = option
        return
    }

    const currentValue = Array.isArray(formData[field.field_key]) ? [...formData[field.field_key]] : []
    const index = currentValue.indexOf(option)
    if (index >= 0) {
        currentValue.splice(index, 1)
    } else {
        currentValue.push(option)
    }
    formData[field.field_key] = currentValue
}

const isOptionSelected = (field: any, option: string) => {
    if (field.field_type === 'single_choice') {
        return formData[field.field_key] === option
    }

    return Array.isArray(formData[field.field_key]) && formData[field.field_key].includes(option)
}

const handlePrev = () => {
    if (activePageIndex.value > 0) {
        activePageIndex.value -= 1
    }
}

const handleNext = () => {
    if (!validatePage(activePageIndex.value)) {
        return
    }
    if (!isLastPage.value) {
        activePageIndex.value += 1
    }
}

const handleSaveDraft = () => {
    patchWeddingOrderDraft({
        template_form_data: { ...formData }
    })
    uni.showToast({ title: '草稿已保存', icon: 'success' })
}

const handleGoPreview = () => {
    if (!validateAllPages()) {
        return
    }

    patchWeddingOrderDraft({
        template_form_data: { ...formData }
    })
    uni.navigateTo({
        url: buildWeddingTradeQueryUrl('/pages/wedding_order_preview/wedding_order_preview', tradeQuery.value)
    })
}

const loadData = async () => {
    const draft = getWeddingOrderDraft()
    if (!draft.category_id || !draft.provider_id || !draft.package_id) {
        uni.showToast({ title: '请先完成服务人员和套餐选择', icon: 'none' })
        setTimeout(() => {
            uni.redirectTo({
                url: buildWeddingTradeQueryUrl('/pages/wedding_provider_list/wedding_provider_list', tradeQuery.value)
            })
        }, 180)
        return
    }

    loading.value = true
    try {
        const [profile, templateData] = await Promise.all([
            getWeddingProfile(),
            getWeddingTemplate({ category_id: draft.category_id })
        ])
        Object.assign(template, {
            id: templateData?.id || 0,
            name: templateData?.name || '',
            pages: templateData?.pages || []
        })
        fillFormData(profile || {}, draft)
    } finally {
        loading.value = false
    }
}

onLoad(async (options) => {
    tradeQuery.value = normalizeWeddingTradeQuery((options || {}) as Record<string, any>)
    await loadData()
})
</script>

<style lang="scss" scoped>
.wedding-template-form-page {
    background:
        radial-gradient(circle at top right, rgba(219, 39, 119, 0.10), transparent 26%),
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
    font-size: 42rpx;
    font-weight: 600;
}

.hero-card__meta,
.state-card {
    margin-top: 16rpx;
    color: #6b7280;
    font-size: 24rpx;
    line-height: 1.8;
}

.hero-card__meta {
    color: #831843;
}

.step-scroll {
    white-space: nowrap;
}

.step-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-right: 16rpx;
    padding: 18rpx 26rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.88);
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    color: #6b7280;
    font-size: 24rpx;
}

.step-chip--active {
    background: linear-gradient(135deg, #db2777, #ca8a04);
    color: #ffffff;
    border-color: transparent;
}

.panel-card__title {
    color: #111827;
    font-size: 30rpx;
    font-weight: 600;
}

.field-list {
    display: grid;
    gap: 24rpx;
    margin-top: 24rpx;
}

.field-item__label {
    margin-bottom: 12rpx;
    color: #111827;
    font-size: 26rpx;
    font-weight: 600;
}

.field-item__required {
    color: #be123c;
}

.field-input,
.field-textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 22rpx 24rpx;
    border-radius: 22rpx;
    background: linear-gradient(180deg, #fff9fb, #fffdf9);
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    color: #111827;
    font-size: 26rpx;
}

.field-textarea {
    min-height: 180rpx;
}

.choice-group {
    display: flex;
    flex-wrap: wrap;
    gap: 14rpx;
}

.choice-chip {
    padding: 16rpx 22rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.90);
    border: 1rpx solid rgba(202, 138, 4, 0.2);
    color: #6b7280;
    font-size: 24rpx;
}

.choice-chip--active {
    background: rgba(190, 24, 93, 0.1);
    border-color: rgba(190, 24, 93, 0.3);
    color: #9d174d;
}

.action-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18rpx;
}

.ghost-btn,
.action-btn {
    width: 100%;
    border-radius: 999rpx;
    font-size: 28rpx;
    font-weight: 600;
}

.ghost-btn {
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    color: #6b7280;
}

.action-btn {
    background: linear-gradient(135deg, #db2777, #ca8a04);
    color: #ffffff;
    box-shadow: 0 20rpx 40rpx rgba(219, 39, 119, 0.18);
}

.ghost-btn[disabled] {
    opacity: 0.45;
}
</style>
