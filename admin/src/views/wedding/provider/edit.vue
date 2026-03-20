<template>
    <popup
        ref="popupRef"
        :title="popupTitle"
        :async="true"
        width="720px"
        @confirm="handleSubmit"
        @close="emit('close')"
    >
        <el-form ref="formRef" :model="formData" label-width="100px" :rules="formRules">
            <el-form-item label="绑定用户" prop="user_id">
                <el-select
                    v-model="formData.user_id"
                    class="w-full"
                    clearable
                    filterable
                    remote
                    reserve-keyword
                    :remote-method="queryUsers"
                    placeholder="请输入昵称、账号或手机号搜索"
                >
                    <el-option
                        v-for="item in userOptions"
                        :key="item.id"
                        :label="formatUserLabel(item)"
                        :value="item.id"
                    />
                </el-select>
            </el-form-item>
            <el-form-item label="服务名称" prop="name">
                <el-input v-model="formData.name" placeholder="请输入服务人员名称" clearable />
            </el-form-item>
            <el-form-item label="服务分类" prop="category_id">
                <el-select v-model="formData.category_id" class="w-full" placeholder="请选择服务分类">
                    <el-option v-for="item in categoryOptions" :key="item.id" :label="item.name" :value="item.id" />
                </el-select>
            </el-form-item>
            <el-form-item label="风格标签" prop="tag_ids">
                <el-select
                    v-model="formData.tag_ids"
                    class="w-full"
                    multiple
                    collapse-tags
                    collapse-tags-tooltip
                    placeholder="请选择风格标签"
                >
                    <el-option v-for="item in tagOptions" :key="item.id" :label="item.name" :value="item.id" />
                </el-select>
            </el-form-item>
            <el-form-item label="头像">
                <div>
                    <material-picker v-model="formData.avatar" :limit="1" />
                    <div class="form-tips">建议使用清晰的服务人员头像或品牌照片。</div>
                </div>
            </el-form-item>
            <el-form-item label="联系电话" prop="mobile">
                <el-input v-model="formData.mobile" placeholder="请输入联系电话" clearable />
            </el-form-item>
            <el-form-item label="推荐位" prop="is_recommend">
                <el-switch v-model="formData.is_recommend" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item label="排序" prop="sort">
                <el-input-number v-model="formData.sort" :min="0" :max="9999" />
            </el-form-item>
            <el-form-item label="状态" prop="status">
                <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item label="服务简介" prop="intro">
                <el-input v-model="formData.intro" type="textarea" :rows="5" placeholder="请输入服务简介" />
            </el-form-item>
        </el-form>
    </popup>
</template>

<script lang="ts" setup name="weddingProviderEdit">
import type { FormInstance } from 'element-plus'

import {
    addServiceProvider,
    detailServiceProvider,
    editServiceProvider,
    getServiceProviderCategoryOptions,
    getServiceProviderTagOptions,
    getServiceProviderUserOptions
} from '@/api/wedding'
import Popup from '@/components/popup/index.vue'

const emit = defineEmits(['success', 'close'])
const popupRef = shallowRef<InstanceType<typeof Popup>>()
const formRef = shallowRef<FormInstance>()
const mode = ref<'add' | 'edit'>('add')

const popupTitle = computed(() => (mode.value === 'edit' ? '编辑服务人员' : '新增服务人员'))

const formData = reactive({
    id: '',
    user_id: undefined as number | undefined,
    category_id: undefined as number | undefined,
    name: '',
    avatar: '',
    mobile: '',
    tag_ids: [] as number[],
    intro: '',
    status: 1,
    is_recommend: 0,
    sort: 0
})

const userOptions = ref<any[]>([])
const categoryOptions = ref<any[]>([])
const tagOptions = ref<any[]>([])

const formRules = {
    user_id: [{ required: true, message: '请选择绑定用户', trigger: ['change'] }],
    category_id: [{ required: true, message: '请选择服务分类', trigger: ['change'] }],
    name: [{ required: true, message: '请输入服务人员名称', trigger: ['blur'] }]
}

const formatUserLabel = (item: Record<string, any>) => {
    const nickname = item.nickname || '未命名用户'
    const account = item.account || '-'
    const mobile = item.mobile || '-'
    return `${nickname} / ${account} / ${mobile}`
}

const queryUsers = async (keyword = '') => {
    userOptions.value = await getServiceProviderUserOptions({ keyword })
}

const loadOptions = async () => {
    const [categories, tags] = await Promise.all([
        getServiceProviderCategoryOptions(),
        getServiceProviderTagOptions()
    ])
    categoryOptions.value = categories || []
    tagOptions.value = tags || []
}

const resetForm = () => {
    Object.assign(formData, {
        id: '',
        user_id: undefined,
        category_id: undefined,
        name: '',
        avatar: '',
        mobile: '',
        tag_ids: [],
        intro: '',
        status: 1,
        is_recommend: 0,
        sort: 0
    })
}

const handleSubmit = async () => {
    await formRef.value?.validate()
    mode.value === 'edit' ? await editServiceProvider(formData) : await addServiceProvider(formData)
    popupRef.value?.close()
    emit('success')
}

const open = async (popupMode: 'add' | 'edit', id?: number) => {
    mode.value = popupMode
    resetForm()
    await loadOptions()
    await queryUsers('')
    popupRef.value?.open()

    if (popupMode === 'edit' && id) {
        const data = await detailServiceProvider({ id })
        Object.assign(formData, data, {
            tag_ids: data.tag_ids || []
        })
        if (!userOptions.value.find((item) => item.id === formData.user_id)) {
            userOptions.value.unshift({
                id: formData.user_id,
                nickname: `已绑定用户 #${formData.user_id}`,
                account: '',
                mobile: formData.mobile
            })
        }
    }
}

defineExpose({
    open
})
</script>
