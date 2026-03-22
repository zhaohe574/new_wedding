<template>
    <div class="provider-page">
        <el-card class="hero-card !border-none" shadow="never">
            <div class="hero-grid">
                <div>
                    <div class="hero-eyebrow">Wedding Provider</div>
                    <h1 class="hero-title">服务人员档案</h1>
                    <p class="hero-desc">
                        这里维护服务人员主档与用户绑定关系。用户端的服务人员中心权限会直接从这里计算，不再依赖 JSON 占位名单。
                    </p>
                </div>
                <div class="hero-side">
                    <div class="hero-stat">
                        <span class="hero-stat__label">绑定规则</span>
                        <span class="hero-stat__value">一人一档</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat__label">权限来源</span>
                        <span class="hero-stat__value">真实主档模型</span>
                    </div>
                </div>
            </div>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-form :model="queryParams" :inline="true" class="mb-[-16px]">
                <el-form-item class="w-[280px]" label="关键字">
                    <el-input
                        v-model="queryParams.keyword"
                        placeholder="名称、手机号、昵称、账号"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[220px]" label="服务分类">
                    <el-select v-model="queryParams.category_id" clearable>
                        <el-option v-for="item in categoryOptions" :key="item.id" :label="item.name" :value="item.id" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[180px]" label="状态">
                    <el-select v-model="queryParams.status" clearable>
                        <el-option label="启用" :value="1" />
                        <el-option label="停用" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                    <el-button
                        v-perms="['wedding.service_provider/add']"
                        type="primary"
                        @click="openEdit('add')"
                    >
                        新增服务人员
                    </el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table v-loading="pager.loading" :data="pager.lists" size="large">
                <el-table-column label="头像" width="90">
                    <template #default="{ row }">
                        <el-avatar :src="row.avatar" :size="48" />
                    </template>
                </el-table-column>
                <el-table-column label="服务名称" prop="name" min-width="150" />
                <el-table-column label="绑定用户" min-width="220">
                    <template #default="{ row }">
                        <div>{{ row.user_nickname || `用户 #${row.user_id}` }}</div>
                        <div class="table-sub">{{ row.user_account || '-' }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="服务分类" prop="category_name" min-width="120" />
                <el-table-column label="风格标签" min-width="180">
                    <template #default="{ row }">
                        <el-tag v-for="item in row.tag_names" :key="item" class="mr-2 mb-2" type="danger" effect="plain">
                            {{ item }}
                        </el-tag>
                        <span v-if="!row.tag_names?.length" class="table-sub">未配置</span>
                    </template>
                </el-table-column>
                <el-table-column label="联系电话" prop="mobile" min-width="120" />
                <el-table-column label="企微接收账号" prop="work_wechat_userid" min-width="160" />
                <el-table-column label="推荐" prop="is_recommend_desc" min-width="80" />
                <el-table-column label="状态" min-width="90">
                    <template #default="{ row }">
                        <el-switch
                            v-perms="['wedding.service_provider/edit']"
                            v-model="row.status"
                            :active-value="1"
                            :inactive-value="0"
                            @change="changeStatus($event, row.id)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" min-width="80" />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['wedding.service_provider/edit']"
                            type="primary"
                            link
                            @click="openEdit('edit', row.id)"
                        >
                            编辑
                        </el-button>
                        <el-button
                            v-perms="['wedding.service_provider/delete']"
                            type="danger"
                            link
                            @click="handleDelete(row.id)"
                        >
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <provider-edit ref="editRef" @success="getLists" @close="void 0" />
    </div>
</template>

<script lang="ts" setup name="weddingProviderIndex">
import {
    deleteServiceProvider,
    getServiceProviderCategoryOptions,
    getServiceProviderLists,
    updateServiceProviderStatus
} from '@/api/wedding'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

import ProviderEdit from './edit.vue'

const queryParams = reactive({
    keyword: '',
    category_id: '',
    status: ''
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getServiceProviderLists,
    params: queryParams
})

const editRef = shallowRef<InstanceType<typeof ProviderEdit>>()
const categoryOptions = ref<any[]>([])

const getCategoryOptions = async () => {
    categoryOptions.value = await getServiceProviderCategoryOptions()
}

const openEdit = async (mode: 'add' | 'edit', id?: number) => {
    await nextTick()
    editRef.value?.open(mode, id)
}

const changeStatus = async (status: string | number | boolean, id: number) => {
    try {
        await updateServiceProviderStatus({ id, status: Number(status) })
    } finally {
        getLists()
    }
}

const handleDelete = async (id: number) => {
    await feedback.confirm('确认删除当前服务人员档案？')
    await deleteServiceProvider({ id })
    getLists()
}

getCategoryOptions()
getLists()
</script>

<style lang="scss" scoped>
.provider-page {
    padding-bottom: 36px;
}

.hero-card {
    background:
        radial-gradient(circle at top left, rgba(219, 39, 119, 0.16), transparent 34%),
        radial-gradient(circle at right bottom, rgba(202, 138, 4, 0.14), transparent 28%),
        linear-gradient(135deg, #fffdf8, #fff7fb 50%, #fffef8);
}

.hero-grid {
    display: grid;
    gap: 24px;
    grid-template-columns: minmax(0, 1.5fr) minmax(280px, 0.8fr);
    align-items: end;
}

.hero-eyebrow {
    color: #9d174d;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.hero-title {
    margin: 10px 0 14px;
    color: #1f2937;
    font-size: 30px;
    line-height: 1.2;
}

.hero-desc {
    max-width: 720px;
    color: #6b7280;
    line-height: 1.8;
}

.hero-side {
    display: grid;
    gap: 14px;
}

.hero-stat {
    border: 1px solid rgba(219, 39, 119, 0.12);
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.88);
    padding: 18px 20px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
}

.hero-stat__label {
    display: block;
    color: #9ca3af;
    font-size: 12px;
    margin-bottom: 8px;
}

.hero-stat__value {
    color: #111827;
    font-size: 18px;
    font-weight: 600;
}

.table-sub {
    color: #9ca3af;
    font-size: 12px;
    line-height: 1.6;
}
</style>
