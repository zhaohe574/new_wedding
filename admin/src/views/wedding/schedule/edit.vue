<template>
    <popup
        ref="popupRef"
        :title="popupTitle"
        :async="true"
        width="640px"
        @confirm="handleSubmit"
        @close="emit('close')"
    >
        <el-form ref="formRef" :model="formData" label-width="92px" :rules="formRules">
            <el-form-item label="服务人员" prop="provider_id">
                <el-select v-model="formData.provider_id" class="w-full" filterable placeholder="请选择服务人员">
                    <el-option
                        v-for="item in providerOptions"
                        :key="item.id"
                        :label="`${item.name}${item.category_name ? ` / ${item.category_name}` : ''}`"
                        :value="item.id"
                    />
                </el-select>
            </el-form-item>
            <el-form-item label="服务日期" prop="service_date">
                <el-date-picker v-model="formData.service_date" class="w-full" type="date" value-format="YYYY-MM-DD" />
            </el-form-item>
            <el-form-item label="档期状态" prop="status">
                <el-select v-model="formData.status" class="w-full">
                    <el-option label="可预约" value="available" />
                    <el-option label="已锁定" value="locked" />
                    <el-option label="已占用" value="occupied" />
                    <el-option label="不可服务" value="unavailable" />
                </el-select>
            </el-form-item>
            <el-form-item label="备注" prop="remark">
                <el-input v-model="formData.remark" type="textarea" :rows="4" placeholder="请输入备注" />
            </el-form-item>
        </el-form>
    </popup>
</template>

<script lang="ts" setup name="weddingScheduleEdit">
import type { FormInstance } from 'element-plus'

import {
    addProviderSchedule,
    detailProviderSchedule,
    editProviderSchedule,
    getProviderScheduleProviderOptions
} from '@/api/wedding'
import Popup from '@/components/popup/index.vue'

const emit = defineEmits(['success', 'close'])
const popupRef = shallowRef<InstanceType<typeof Popup>>()
const formRef = shallowRef<FormInstance>()
const mode = ref<'add' | 'edit'>('add')
const providerOptions = ref<any[]>([])

const popupTitle = computed(() => (mode.value === 'edit' ? '编辑档期记录' : '新增档期记录'))

const formData = reactive({
    id: '',
    provider_id: undefined as number | undefined,
    service_date: '',
    status: 'unavailable',
    remark: ''
})

const formRules = {
    provider_id: [{ required: true, message: '请选择服务人员', trigger: ['change'] }],
    service_date: [{ required: true, message: '请选择服务日期', trigger: ['change'] }],
    status: [{ required: true, message: '请选择档期状态', trigger: ['change'] }]
}

const resetForm = () => {
    Object.assign(formData, {
        id: '',
        provider_id: undefined,
        service_date: '',
        status: 'unavailable',
        remark: ''
    })
}

const loadOptions = async () => {
    providerOptions.value = (await getProviderScheduleProviderOptions()) || []
}

const handleSubmit = async () => {
    await formRef.value?.validate()
    mode.value === 'edit' ? await editProviderSchedule(formData) : await addProviderSchedule(formData)
    popupRef.value?.close()
    emit('success')
}

const open = async (popupMode: 'add' | 'edit', id?: number) => {
    mode.value = popupMode
    resetForm()
    await loadOptions()
    popupRef.value?.open()

    if (popupMode === 'edit' && id) {
        const data = await detailProviderSchedule({ id })
        Object.assign(formData, data)
    }
}

defineExpose({
    open
})
</script>
