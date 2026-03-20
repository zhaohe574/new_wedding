<template>
    <div class="resource-center-page">
        <el-card class="hero-card !border-none" shadow="never">
            <div class="hero-grid">
                <div>
                    <div class="hero-eyebrow">Wedding Resource Center</div>
                    <h1 class="hero-title">服务资源中心</h1>
                    <p class="hero-desc">
                        在这里统一维护服务分类与风格标签，为服务人员档案、套餐模板和用户筛选页提供同一套业务口径。
                    </p>
                </div>
                <div class="hero-side">
                    <div class="hero-stat">
                        <span class="hero-stat__label">当前模块</span>
                        <span class="hero-stat__value">T2.1 服务资源域</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat__label">数据来源</span>
                        <span class="hero-stat__value">后台真实模型</span>
                    </div>
                </div>
            </div>
        </el-card>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mt-4">
            <el-card class="!border-none" shadow="never">
                <template #header>
                    <div class="section-head">
                        <span>服务分类</span>
                        <el-button
                            v-perms="['wedding.service_category/add']"
                            type="primary"
                            @click="openEdit('category', 'add')"
                        >
                            新增分类
                        </el-button>
                    </div>
                </template>
                <el-table :data="categoryList" size="large">
                    <el-table-column prop="name" label="分类名称" min-width="160" />
                    <el-table-column prop="sort" label="排序" min-width="90" />
                    <el-table-column label="状态" min-width="90">
                        <template #default="{ row }">
                            <el-switch
                                v-perms="['wedding.service_category/edit']"
                                v-model="row.status"
                                :active-value="1"
                                :inactive-value="0"
                                @change="changeCategoryStatus($event, row.id)"
                            />
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" min-width="120" fixed="right">
                        <template #default="{ row }">
                            <el-button
                                v-perms="['wedding.service_category/edit']"
                                type="primary"
                                link
                                @click="openEdit('category', 'edit', row.id)"
                            >
                                编辑
                            </el-button>
                            <el-button
                                v-perms="['wedding.service_category/delete']"
                                type="danger"
                                link
                                @click="handleDelete('category', row.id)"
                            >
                                删除
                            </el-button>
                        </template>
                    </el-table-column>
                </el-table>
            </el-card>

            <el-card class="!border-none" shadow="never">
                <template #header>
                    <div class="section-head">
                        <span>风格标签</span>
                        <el-button
                            v-perms="['wedding.service_tag/add']"
                            type="primary"
                            @click="openEdit('tag', 'add')"
                        >
                            新增标签
                        </el-button>
                    </div>
                </template>
                <el-table :data="tagList" size="large">
                    <el-table-column prop="name" label="标签名称" min-width="160" />
                    <el-table-column prop="sort" label="排序" min-width="90" />
                    <el-table-column label="状态" min-width="90">
                        <template #default="{ row }">
                            <el-switch
                                v-perms="['wedding.service_tag/edit']"
                                v-model="row.status"
                                :active-value="1"
                                :inactive-value="0"
                                @change="changeTagStatus($event, row.id)"
                            />
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" min-width="120" fixed="right">
                        <template #default="{ row }">
                            <el-button
                                v-perms="['wedding.service_tag/edit']"
                                type="primary"
                                link
                                @click="openEdit('tag', 'edit', row.id)"
                            >
                                编辑
                            </el-button>
                            <el-button
                                v-perms="['wedding.service_tag/delete']"
                                type="danger"
                                link
                                @click="handleDelete('tag', row.id)"
                            >
                                删除
                            </el-button>
                        </template>
                    </el-table-column>
                </el-table>
            </el-card>
        </div>

        <resource-edit ref="editRef" @success="getData" @close="void 0" />
    </div>
</template>

<script lang="ts" setup name="weddingResourceCenter">
import {
    deleteServiceCategory,
    deleteServiceTag,
    getServiceCategoryLists,
    getServiceTagLists,
    updateServiceCategoryStatus,
    updateServiceTagStatus
} from '@/api/wedding'
import feedback from '@/utils/feedback'

import ResourceEdit from './resource-edit.vue'

const categoryList = ref<any[]>([])
const tagList = ref<any[]>([])
const editRef = shallowRef<InstanceType<typeof ResourceEdit>>()

const getData = async () => {
    const [categoryData, tagData] = await Promise.all([
        getServiceCategoryLists({ page_type: 0 }),
        getServiceTagLists({ page_type: 0 })
    ])
    categoryList.value = categoryData.lists || []
    tagList.value = tagData.lists || []
}

const openEdit = async (kind: 'category' | 'tag', mode: 'add' | 'edit', id?: number) => {
    await nextTick()
    editRef.value?.open(kind, mode, id)
}

const handleDelete = async (kind: 'category' | 'tag', id: number) => {
    await feedback.confirm('确认删除当前数据？')
    if (kind === 'category') {
        await deleteServiceCategory({ id })
    } else {
        await deleteServiceTag({ id })
    }
    getData()
}

const changeCategoryStatus = async (status: string | number | boolean, id: number) => {
    try {
        await updateServiceCategoryStatus({ id, status: Number(status) })
    } finally {
        getData()
    }
}

const changeTagStatus = async (status: string | number | boolean, id: number) => {
    try {
        await updateServiceTagStatus({ id, status: Number(status) })
    } finally {
        getData()
    }
}

getData()
</script>

<style lang="scss" scoped>
.resource-center-page {
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

.section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}
</style>
