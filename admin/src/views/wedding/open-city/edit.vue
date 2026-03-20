<template>
    <popup
        ref="popupRef"
        :title="popupTitle"
        :async="true"
        width="620px"
        @confirm="handleSubmit"
        @close="emit('close')"
    >
        <el-form ref="formRef" :model="formData" label-width="92px" :rules="formRules">
            <el-form-item label="开放城市" prop="city_code">
                <el-cascader
                    v-model="formData.region_codes"
                    class="w-full"
                    :options="cityOptions"
                    :props="{ emitPath: true, checkStrictly: false }"
                    placeholder="请选择要开放的市级城市"
                />
            </el-form-item>
            <el-form-item label="排序" prop="sort">
                <div>
                    <el-input-number v-model="formData.sort" :min="0" :max="9999" />
                    <div class="form-tips">数值越大越排前。</div>
                </div>
            </el-form-item>
            <el-form-item label="状态" prop="status">
                <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
            </el-form-item>
        </el-form>
    </popup>
</template>

<script lang="ts" setup name="weddingOpenCityEdit">
import type { FormInstance } from 'element-plus'

import {
    addServiceOpenCity,
    detailServiceOpenCity,
    editServiceOpenCity,
    getServiceOpenCityOptions
} from '@/api/wedding'
import Popup from '@/components/popup/index.vue'

const emit = defineEmits(['success', 'close'])
const popupRef = shallowRef<InstanceType<typeof Popup>>()
const formRef = shallowRef<FormInstance>()
const mode = ref<'add' | 'edit'>('add')
const cityOptions = ref<any[]>([])

const popupTitle = computed(() => (mode.value === 'edit' ? '编辑开放城市' : '新增开放城市'))

const formData = reactive({
    id: '',
    region_codes: [] as string[],
    city_code: '',
    sort: 0,
    status: 1
})

const formRules = {
    city_code: [{ required: true, message: '请选择开放城市', trigger: ['change'] }]
}

const resetForm = () => {
    Object.assign(formData, {
        id: '',
        region_codes: [],
        city_code: '',
        sort: 0,
        status: 1
    })
}

watch(
    () => formData.region_codes,
    (value) => {
        formData.city_code = value?.[1] || ''
    },
    { deep: true }
)

const handleSubmit = async () => {
    await formRef.value?.validate()
    const params = {
        ...formData,
        city_code: formData.city_code
    }
    mode.value === 'edit' ? await editServiceOpenCity(params) : await addServiceOpenCity(params)
    popupRef.value?.close()
    emit('success')
}

const open = async (popupMode: 'add' | 'edit', id?: number) => {
    mode.value = popupMode
    resetForm()
    cityOptions.value = await getServiceOpenCityOptions()
    popupRef.value?.open()

    if (popupMode === 'edit' && id) {
        const data = await detailServiceOpenCity({ id })
        Object.assign(formData, data, {
            region_codes: [data.province_code, data.city_code],
            city_code: data.city_code
        })
    }
}

defineExpose({
    open
})
</script>
