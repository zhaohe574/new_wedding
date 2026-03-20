<template>
    <popup
        ref="popupRef"
        :title="popupTitle"
        :async="true"
        width="1100px"
        @confirm="handleSubmit"
        @close="emit('close')"
    >
        <el-form ref="formRef" :model="formData" label-width="92px" :rules="formRules">
            <el-row :gutter="18">
                <el-col :span="8">
                    <el-form-item label="服务分类" prop="category_id">
                        <el-select v-model="formData.category_id" class="w-full">
                            <el-option v-for="item in categoryOptions" :key="item.id" :label="item.name" :value="item.id" />
                        </el-select>
                    </el-form-item>
                </el-col>
                <el-col :span="8">
                    <el-form-item label="模板名称" prop="name">
                        <el-input v-model="formData.name" placeholder="请输入模板名称" clearable />
                    </el-form-item>
                </el-col>
                <el-col :span="4">
                    <el-form-item label="排序" prop="sort">
                        <el-input-number v-model="formData.sort" :min="0" :max="9999" />
                    </el-form-item>
                </el-col>
                <el-col :span="4">
                    <el-form-item label="状态" prop="status">
                        <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
                    </el-form-item>
                </el-col>
            </el-row>

            <div class="section-title">
                <span>模板页面</span>
                <el-button type="primary" link @click="addPage">新增页面</el-button>
            </div>

            <div class="page-list">
                <div v-for="(page, pageIndex) in formData.pages" :key="page.uuid" class="page-card">
                    <div class="page-card__head">
                        <span>页面 {{ pageIndex + 1 }}</span>
                        <div class="page-card__actions">
                            <el-button type="primary" link @click="addField(page)">新增字段</el-button>
                            <el-button type="danger" link @click="removePage(pageIndex)">删除页面</el-button>
                        </div>
                    </div>

                    <el-row :gutter="16">
                        <el-col :span="10">
                            <el-form-item :label="`标题 ${pageIndex + 1}`" label-width="72px">
                                <el-input v-model="page.title" placeholder="请输入页面标题" clearable />
                            </el-form-item>
                        </el-col>
                        <el-col :span="10">
                            <el-form-item label="说明" label-width="56px">
                                <el-input v-model="page.description" placeholder="请输入页面说明" clearable />
                            </el-form-item>
                        </el-col>
                        <el-col :span="4">
                            <el-form-item label="排序" label-width="56px">
                                <el-input-number v-model="page.sort" class="w-full" :min="0" :max="9999" />
                            </el-form-item>
                        </el-col>
                    </el-row>

                    <div class="field-list">
                        <div v-for="(field, fieldIndex) in page.fields" :key="field.uuid" class="field-card">
                            <div class="field-card__head">
                                <span>字段 {{ fieldIndex + 1 }}</span>
                                <el-button type="danger" link @click="removeField(page, fieldIndex)">删除字段</el-button>
                            </div>
                            <el-row :gutter="12">
                                <el-col :span="6">
                                    <el-input v-model="field.label" placeholder="字段标题" />
                                </el-col>
                                <el-col :span="5">
                                    <el-input v-model="field.field_key" placeholder="字段键" />
                                </el-col>
                                <el-col :span="4">
                                    <el-select v-model="field.field_type" class="w-full">
                                        <el-option v-for="item in fieldTypeOptions" :key="item.value" :label="item.label" :value="item.value" />
                                    </el-select>
                                </el-col>
                                <el-col :span="3">
                                    <el-input-number v-model="field.sort" class="w-full" :min="0" :max="9999" />
                                </el-col>
                                <el-col :span="3">
                                    <el-switch v-model="field.required" :active-value="1" :inactive-value="0" active-text="必填" />
                                </el-col>
                                <el-col :span="3">
                                    <el-input v-model="field.default_value" placeholder="默认值" />
                                </el-col>
                            </el-row>
                            <el-row :gutter="12" class="mt-3">
                                <el-col :span="10">
                                    <el-input v-model="field.placeholder" placeholder="占位说明" />
                                </el-col>
                                <el-col :span="14">
                                    <el-input
                                        v-if="isChoiceField(field.field_type)"
                                        v-model="field.options_text"
                                        type="textarea"
                                        :rows="2"
                                        placeholder="选项类字段请每行填写一个选项"
                                    />
                                </el-col>
                            </el-row>
                        </div>
                    </div>
                </div>
            </div>
        </el-form>
    </popup>
</template>

<script lang="ts" setup name="weddingServiceContentTemplateEdit">
import type { FormInstance } from 'element-plus'

import {
    addServiceContentTemplate,
    detailServiceContentTemplate,
    editServiceContentTemplate,
    getServiceContentTemplateCategoryOptions
} from '@/api/wedding'
import Popup from '@/components/popup/index.vue'
import feedback from '@/utils/feedback'

const emit = defineEmits(['success', 'close'])
const popupRef = shallowRef<InstanceType<typeof Popup>>()
const formRef = shallowRef<FormInstance>()
const mode = ref<'add' | 'edit'>('add')
const categoryOptions = ref<any[]>([])

const fieldTypeOptions = [
    { label: '单选', value: 'single_choice' },
    { label: '多选', value: 'multiple_choice' },
    { label: '文本', value: 'text' },
    { label: '数字', value: 'number' },
    { label: '长文本', value: 'textarea' }
]

const popupTitle = computed(() => (mode.value === 'edit' ? '编辑服务内容模板' : '新增服务内容模板'))

const createField = () => ({
    uuid: `${Date.now()}_${Math.random()}`,
    label: '',
    field_key: '',
    field_type: 'text',
    required: 0,
    options_text: '',
    default_value: '',
    placeholder: '',
    sort: 0
})

const createPage = () => ({
    uuid: `${Date.now()}_${Math.random()}`,
    title: '',
    description: '',
    sort: 0,
    fields: [createField()]
})

const formData = reactive({
    id: '',
    category_id: undefined as number | undefined,
    name: '',
    status: 1,
    sort: 0,
    pages: [createPage()]
})

const formRules = {
    category_id: [{ required: true, message: '请选择服务分类', trigger: ['change'] }],
    name: [{ required: true, message: '请输入模板名称', trigger: ['blur'] }]
}

const isChoiceField = (fieldType: string) => ['single_choice', 'multiple_choice'].includes(fieldType)

const resetForm = () => {
    Object.assign(formData, {
        id: '',
        category_id: undefined,
        name: '',
        status: 1,
        sort: 0,
        pages: [createPage()]
    })
}

const loadOptions = async () => {
    categoryOptions.value = (await getServiceContentTemplateCategoryOptions()) || []
}

const addPage = () => {
    formData.pages.push(createPage())
}

const removePage = (index: number) => {
    formData.pages.splice(index, 1)
    if (!formData.pages.length) {
        formData.pages.push(createPage())
    }
}

const addField = (page: any) => {
    page.fields.push(createField())
}

const removeField = (page: any, index: number) => {
    page.fields.splice(index, 1)
    if (!page.fields.length) {
        page.fields.push(createField())
    }
}

const normalizePages = () =>
    formData.pages.map((page) => ({
        title: page.title.trim(),
        description: page.description.trim(),
        sort: Number(page.sort || 0),
        fields: page.fields.map((field: any) => ({
            label: field.label.trim(),
            field_key: field.field_key.trim(),
            field_type: field.field_type,
            required: Number(field.required || 0),
            options: isChoiceField(field.field_type)
                ? field.options_text
                      .split('\n')
                      .map((item: string) => item.trim())
                      .filter(Boolean)
                : [],
            default_value: field.default_value.trim(),
            placeholder: field.placeholder.trim(),
            sort: Number(field.sort || 0)
        }))
    }))

const handleSubmit = async () => {
    await formRef.value?.validate()
    const pages = normalizePages()
    const pageWithoutTitle = pages.find((page) => !page.title)
    const fieldWithoutValue = pages.find((page) => page.fields.some((field) => !field.label || !field.field_key))
    if (pageWithoutTitle) {
        feedback.msgError('模板页面标题不能为空')
        return
    }
    if (fieldWithoutValue) {
        feedback.msgError('模板字段标题和字段键不能为空')
        return
    }

    const params = {
        ...formData,
        pages
    }
    mode.value === 'edit' ? await editServiceContentTemplate(params) : await addServiceContentTemplate(params)
    popupRef.value?.close()
    emit('success')
}

const open = async (popupMode: 'add' | 'edit', id?: number) => {
    mode.value = popupMode
    resetForm()
    await loadOptions()
    popupRef.value?.open()

    if (popupMode === 'edit' && id) {
        const data = await detailServiceContentTemplate({ id })
        Object.assign(formData, data, {
            pages: (data.pages || []).map((page: any) => ({
                ...createPage(),
                ...page,
                fields: (page.fields || []).map((field: any) => ({
                    ...createField(),
                    ...field,
                    options_text: (field.options || []).join('\n')
                }))
            }))
        })
    }
}

defineExpose({
    open
})
</script>

<style lang="scss" scoped>
.section-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
    color: #111827;
    font-size: 15px;
    font-weight: 600;
}

.page-list {
    display: grid;
    gap: 16px;
}

.page-card {
    padding: 18px;
    border-radius: 18px;
    background: linear-gradient(180deg, #fff9fb, #fffdf9);
    border: 1px solid rgba(219, 39, 119, 0.12);
}

.page-card__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    color: #9d174d;
    font-size: 13px;
    font-weight: 600;
}

.page-card__actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.field-list {
    display: grid;
    gap: 12px;
}

.field-card {
    padding: 14px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.82);
    border: 1px solid rgba(202, 138, 4, 0.12);
}

.field-card__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
    color: #6b7280;
    font-size: 12px;
}
</style>
