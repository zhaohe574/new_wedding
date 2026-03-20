<template>
    <div class="provider-package-page">
        <el-card class="hero-card !border-none" shadow="never">
            <div class="hero-grid">
                <div>
                    <div class="hero-eyebrow">Wedding Package</div>
                    <h1 class="hero-title">套餐管理</h1>
                    <p class="hero-desc">
                        套餐是婚庆交易链路里的可售单元。这里统一维护套餐主档与地区价格，后续列表、详情和订单预览都会复用同一套命中规则。
                    </p>
                </div>
                <div class="hero-side">
                    <div class="hero-stat">
                        <span class="hero-stat__label">价格命中</span>
                        <span class="hero-stat__value">县区 -> 市级 -> 省级</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat__label">地区来源</span>
                        <span class="hero-stat__value">已开放城市范围</span>
                    </div>
                </div>
            </div>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-form :model="queryParams" :inline="true" class="mb-[-16px]">
                <el-form-item class="w-[280px]" label="关键字">
                    <el-input v-model="queryParams.keyword" placeholder="套餐、服务人员、分类" clearable @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item class="w-[220px]" label="服务分类">
                    <el-select v-model="queryParams.category_id" clearable>
                        <el-option v-for="item in categoryOptions" :key="item.id" :label="item.name" :value="item.id" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[220px]" label="服务人员">
                    <el-select v-model="queryParams.provider_id" clearable filterable>
                        <el-option
                            v-for="item in providerOptions"
                            :key="item.id"
                            :label="`${item.name}${item.category_name ? ` / ${item.category_name}` : ''}`"
                            :value="item.id"
                        />
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
                    <el-button v-perms="['wedding.provider_package/add']" type="primary" @click="openEdit('add')">
                        新增套餐
                    </el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table v-loading="pager.loading" :data="pager.lists" size="large">
                <el-table-column label="套餐名称" prop="name" min-width="180" />
                <el-table-column label="服务人员" prop="provider_name" min-width="150" />
                <el-table-column label="服务分类" prop="category_name" min-width="120" />
                <el-table-column label="服务时长" prop="service_duration" min-width="120" />
                <el-table-column label="地区价格" min-width="100">
                    <template #default="{ row }">
                        <el-tag type="warning" effect="plain">{{ row.price_count }} 条</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="状态" min-width="90">
                    <template #default="{ row }">
                        <el-switch
                            v-perms="['wedding.provider_package/edit']"
                            v-model="row.status"
                            :active-value="1"
                            :inactive-value="0"
                            @change="changeStatus($event, row.id)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" min-width="90" />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['wedding.provider_package/edit']" type="primary" link @click="openEdit('edit', row.id)">
                            编辑
                        </el-button>
                        <el-button v-perms="['wedding.provider_package/delete']" type="danger" link @click="handleDelete(row.id)">
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <provider-package-edit ref="editRef" @success="getLists" @close="void 0" />
    </div>
</template>

<script lang="ts" setup name="weddingProviderPackageIndex">
import {
    deleteProviderPackage,
    getProviderPackageLists,
    getProviderPackageProviderOptions,
    getServiceProviderCategoryOptions,
    updateProviderPackageStatus
} from '@/api/wedding'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

import ProviderPackageEdit from './edit.vue'

const queryParams = reactive({
    keyword: '',
    category_id: '',
    provider_id: '',
    status: ''
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getProviderPackageLists,
    params: queryParams
})

const editRef = shallowRef<InstanceType<typeof ProviderPackageEdit>>()
const categoryOptions = ref<any[]>([])
const providerOptions = ref<any[]>([])

const getOptions = async () => {
    const [categories, providers] = await Promise.all([
        getServiceProviderCategoryOptions(),
        getProviderPackageProviderOptions()
    ])
    categoryOptions.value = categories || []
    providerOptions.value = providers || []
}

const openEdit = async (mode: 'add' | 'edit', id?: number) => {
    await nextTick()
    editRef.value?.open(mode, id)
}

const changeStatus = async (status: string | number | boolean, id: number) => {
    try {
        await updateProviderPackageStatus({ id, status: Number(status) })
    } finally {
        getLists()
    }
}

const handleDelete = async (id: number) => {
    await feedback.confirm('确认删除当前套餐？')
    await deleteProviderPackage({ id })
    getLists()
}

getOptions()
getLists()
</script>

<style lang="scss" scoped>
.provider-package-page {
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
    max-width: 760px;
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
</style>
