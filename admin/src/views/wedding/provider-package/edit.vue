<template>
    <popup
        ref="popupRef"
        :title="popupTitle"
        :async="true"
        width="960px"
        @confirm="handleSubmit"
        @close="emit('close')"
    >
        <el-form ref="formRef" :model="formData" label-width="92px" :rules="formRules">
            <el-row :gutter="18">
                <el-col :span="12">
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
                </el-col>
                <el-col :span="12">
                    <el-form-item label="套餐名称" prop="name">
                        <el-input v-model="formData.name" placeholder="请输入套餐名称" clearable />
                    </el-form-item>
                </el-col>
            </el-row>

            <el-row :gutter="18">
                <el-col :span="12">
                    <el-form-item label="服务时长" prop="service_duration">
                        <el-input v-model="formData.service_duration" placeholder="例如：全天、8 小时、半天" clearable />
                    </el-form-item>
                </el-col>
                <el-col :span="6">
                    <el-form-item label="排序" prop="sort">
                        <el-input-number v-model="formData.sort" :min="0" :max="9999" />
                    </el-form-item>
                </el-col>
                <el-col :span="6">
                    <el-form-item label="状态" prop="status">
                        <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
                    </el-form-item>
                </el-col>
            </el-row>

            <el-form-item label="套餐简介" prop="summary">
                <el-input v-model="formData.summary" type="textarea" :rows="3" placeholder="请输入套餐简介" />
            </el-form-item>

            <div class="section-title">
                <span>地区价格</span>
                <el-button type="primary" link @click="addAreaPrice">新增一条价格</el-button>
            </div>

            <div class="area-list">
                <div v-for="(item, index) in formData.area_prices" :key="item.uuid" class="area-card">
                    <div class="area-card__head">
                        <span>地区价格 {{ index + 1 }}</span>
                        <el-button type="danger" link @click="removeAreaPrice(index)">删除</el-button>
                    </div>
                    <el-row :gutter="16">
                        <el-col :span="12">
                            <el-form-item :label="`地区 ${index + 1}`" label-width="86px">
                                <el-cascader
                                    v-model="item.region_codes"
                                    class="w-full"
                                    :options="openRegionOptions"
                                    :props="{ emitPath: true, checkStrictly: true }"
                                    placeholder="请选择省、市或县区"
                                />
                            </el-form-item>
                        </el-col>
                        <el-col :span="5">
                            <el-form-item label="价格" label-width="56px">
                                <el-input-number v-model="item.price" class="w-full" :min="0.01" :precision="2" :step="100" />
                            </el-form-item>
                        </el-col>
                        <el-col :span="3">
                            <el-form-item label="排序" label-width="56px">
                                <el-input-number v-model="item.sort" class="w-full" :min="0" :max="9999" />
                            </el-form-item>
                        </el-col>
                        <el-col :span="4">
                            <el-form-item label="状态" label-width="56px">
                                <el-switch v-model="item.status" :active-value="1" :inactive-value="0" />
                            </el-form-item>
                        </el-col>
                    </el-row>
                </div>
            </div>
        </el-form>
    </popup>
</template>

<script lang="ts" setup name="weddingProviderPackageEdit">
import type { FormInstance } from 'element-plus'

import {
    addProviderPackage,
    detailProviderPackage,
    editProviderPackage,
    getProviderPackageOpenRegionOptions,
    getProviderPackageProviderOptions
} from '@/api/wedding'
import Popup from '@/components/popup/index.vue'
import feedback from '@/utils/feedback'

const emit = defineEmits(['success', 'close'])
const popupRef = shallowRef<InstanceType<typeof Popup>>()
const formRef = shallowRef<FormInstance>()
const mode = ref<'add' | 'edit'>('add')

const providerOptions = ref<any[]>([])
const openRegionOptions = ref<any[]>([])

const popupTitle = computed(() => (mode.value === 'edit' ? '编辑套餐' : '新增套餐'))

const createAreaPrice = () => ({
    uuid: `${Date.now()}_${Math.random()}`,
    region_codes: [] as string[],
    price: 0,
    sort: 0,
    status: 1
})

const formData = reactive({
    id: '',
    provider_id: undefined as number | undefined,
    name: '',
    summary: '',
    service_duration: '',
    status: 1,
    sort: 0,
    area_prices: [createAreaPrice()]
})

const formRules = {
    provider_id: [{ required: true, message: '请选择服务人员', trigger: ['change'] }],
    name: [{ required: true, message: '请输入套餐名称', trigger: ['blur'] }]
}

const resetForm = () => {
    Object.assign(formData, {
        id: '',
        provider_id: undefined,
        name: '',
        summary: '',
        service_duration: '',
        status: 1,
        sort: 0,
        area_prices: [createAreaPrice()]
    })
}

const loadOptions = async () => {
    const [providers, regions] = await Promise.all([
        getProviderPackageProviderOptions(),
        getProviderPackageOpenRegionOptions()
    ])
    providerOptions.value = providers || []
    openRegionOptions.value = regions || []
}

const addAreaPrice = () => {
    formData.area_prices.push(createAreaPrice())
}

const removeAreaPrice = (index: number) => {
    formData.area_prices.splice(index, 1)
    if (!formData.area_prices.length) {
        formData.area_prices.push(createAreaPrice())
    }
}

const normalizeAreaPrices = () =>
    formData.area_prices.map((item) => ({
        region_codes: item.region_codes || [],
        price: Number(item.price || 0),
        sort: Number(item.sort || 0),
        status: Number(item.status || 0)
    }))

const handleSubmit = async () => {
    await formRef.value?.validate()
    const areaPrices = normalizeAreaPrices()
    const invalidItem = areaPrices.find((item) => item.region_codes.length === 0 || item.price <= 0)
    if (invalidItem) {
        feedback.msgError('请完整填写地区价格')
        return
    }

    const params = {
        ...formData,
        area_prices: areaPrices
    }
    mode.value === 'edit' ? await editProviderPackage(params) : await addProviderPackage(params)
    popupRef.value?.close()
    emit('success')
}

const open = async (popupMode: 'add' | 'edit', id?: number) => {
    mode.value = popupMode
    resetForm()
    await loadOptions()
    popupRef.value?.open()

    if (popupMode === 'edit' && id) {
        const data = await detailProviderPackage({ id })
        Object.assign(formData, data, {
            area_prices: (data.area_prices || []).map((item: any) => ({
                ...createAreaPrice(),
                ...item,
                region_codes: item.region_codes || [],
                price: Number(item.price || 0)
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

.area-list {
    display: grid;
    gap: 14px;
}

.area-card {
    padding: 18px;
    border-radius: 18px;
    background: linear-gradient(180deg, #fff9fb, #fffdf9);
    border: 1px solid rgba(219, 39, 119, 0.12);
}

.area-card__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
    color: #9d174d;
    font-size: 13px;
    font-weight: 600;
}
</style>
