<template>
    <popup
        ref="popupRef"
        :title="popupTitle"
        :async="true"
        width="560px"
        @confirm="handleSubmit"
        @close="emit('close')"
    >
        <el-form ref="formRef" :model="formData" label-width="92px" :rules="formRules">
            <el-form-item :label="nameLabel" prop="name">
                <el-input v-model="formData.name" :placeholder="`请输入${nameLabel}`" clearable />
            </el-form-item>
            <el-form-item label="排序" prop="sort">
                <div>
                    <el-input-number v-model="formData.sort" :min="0" :max="9999" />
                    <div class="form-tips">默认为 0，数值越大越排前</div>
                </div>
            </el-form-item>
            <el-form-item label="状态" prop="status">
                <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
            </el-form-item>
        </el-form>
    </popup>
</template>

<script lang="ts" setup name="weddingResourceEdit">
import type { FormInstance } from 'element-plus'

import {
    addServiceCategory,
    addServiceTag,
    detailServiceCategory,
    detailServiceTag,
    editServiceCategory,
    editServiceTag
} from '@/api/wedding'
import Popup from '@/components/popup/index.vue'

type ResourceKind = 'category' | 'tag'

const emit = defineEmits(['success', 'close'])
const popupRef = shallowRef<InstanceType<typeof Popup>>()
const formRef = shallowRef<FormInstance>()

const mode = ref<'add' | 'edit'>('add')
const kind = ref<ResourceKind>('category')
const formData = reactive({
    id: '',
    name: '',
    sort: 0,
    status: 1
})

const popupTitle = computed(() => {
    const label = kind.value === 'category' ? '服务分类' : '风格标签'
    return mode.value === 'edit' ? `编辑${label}` : `新增${label}`
})

const nameLabel = computed(() => (kind.value === 'category' ? '分类名称' : '标签名称'))

const formRules = {
    name: [
        {
            required: true,
            message: '请输入名称',
            trigger: ['blur']
        }
    ]
}

const resetForm = () => {
    Object.assign(formData, {
        id: '',
        name: '',
        sort: 0,
        status: 1
    })
}

const setFormData = (data: Record<string, any>) => {
    Object.keys(formData).forEach((key) => {
        if (data[key] !== undefined && data[key] !== null) {
            // @ts-ignore
            formData[key] = data[key]
        }
    })
}

const handleSubmit = async () => {
    await formRef.value?.validate()

    if (kind.value === 'category') {
        mode.value === 'edit'
            ? await editServiceCategory(formData)
            : await addServiceCategory(formData)
    } else {
        mode.value === 'edit' ? await editServiceTag(formData) : await addServiceTag(formData)
    }

    popupRef.value?.close()
    emit('success')
}

const open = async (resourceKind: ResourceKind, popupMode: 'add' | 'edit', id?: number) => {
    resetForm()
    kind.value = resourceKind
    mode.value = popupMode
    popupRef.value?.open()

    if (popupMode === 'edit' && id) {
        const data =
            resourceKind === 'category'
                ? await detailServiceCategory({ id })
                : await detailServiceTag({ id })
        setFormData(data)
    }
}

defineExpose({
    open
})
</script>
