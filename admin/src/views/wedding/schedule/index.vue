<template>
    <div class="schedule-page">
        <el-card class="hero-card !border-none" shadow="never">
            <div class="hero-grid">
                <div>
                    <div class="hero-eyebrow">Wedding Schedule</div>
                    <h1 class="hero-title">档期管理</h1>
                    <p class="hero-desc">
                        当前采用“服务人员 + 自然日”的稀疏档期模型。无记录即默认可预约，显式记录用于锁档、占用与人工禁用日期。
                    </p>
                </div>
                <div class="hero-side">
                    <div class="hero-stat">
                        <span class="hero-stat__label">默认解释</span>
                        <span class="hero-stat__value">无记录 = 可预约</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat__label">冲突粒度</span>
                        <span class="hero-stat__value">服务人员 + 自然日</span>
                    </div>
                </div>
            </div>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-form :model="queryParams" :inline="true" class="mb-[-16px]">
                <el-form-item class="w-[260px]" label="关键字">
                    <el-input v-model="queryParams.keyword" placeholder="服务人员、分类" clearable @keyup.enter="resetPage" />
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
                        <el-option v-for="item in statusOptions" :key="item.value" :label="item.label" :value="item.value" />
                    </el-select>
                </el-form-item>
                <el-form-item label="日期范围">
                    <el-date-picker
                        v-model="dateRange"
                        type="daterange"
                        range-separator="至"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        value-format="YYYY-MM-DD"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="applyDateRange">查询</el-button>
                    <el-button @click="resetAll">重置</el-button>
                    <el-button v-perms="['wedding.provider_schedule/add']" type="primary" @click="openEdit('add')">
                        新增档期记录
                    </el-button>
                </el-form-item>
            </el-form>

            <div class="legend-grid">
                <div v-for="item in statusLegend" :key="item.value" class="legend-item">
                    <span class="legend-item__title">{{ item.label }}</span>
                    <span class="legend-item__desc">{{ item.desc }}</span>
                </div>
            </div>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table v-loading="pager.loading" :data="pager.lists" size="large">
                <el-table-column label="服务人员" prop="provider_name" min-width="150" />
                <el-table-column label="服务分类" prop="category_name" min-width="120" />
                <el-table-column label="服务日期" prop="service_date" min-width="120" />
                <el-table-column label="状态" min-width="180">
                    <template #default="{ row }">
                        <div class="status-cell">
                            <span class="status-cell__title">{{ row.status_desc }}</span>
                            <span class="status-cell__desc">{{ row.status_hint || '-' }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="锁定来源" min-width="160">
                    <template #default="{ row }">
                        <div class="status-cell">
                            <span class="status-cell__title">{{ row.source_desc || '-' }}</span>
                            <span class="status-cell__desc">{{ row.source_display || '-' }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="备注" prop="remark" min-width="220" show-overflow-tooltip />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['wedding.provider_schedule/edit']" type="primary" link @click="openEdit('edit', row.id)">
                            编辑
                        </el-button>
                        <el-button v-perms="['wedding.provider_schedule/delete']" type="danger" link @click="handleDelete(row.id)">
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <schedule-edit ref="editRef" @success="getLists" @close="void 0" />
    </div>
</template>

<script lang="ts" setup name="weddingScheduleIndex">
import { deleteProviderSchedule, getProviderScheduleLists, getProviderScheduleProviderOptions } from '@/api/wedding'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

import ScheduleEdit from './edit.vue'

const statusOptions = [
    { label: '可预约', value: 'available' },
    { label: '已锁定', value: 'locked' },
    { label: '已占用', value: 'occupied' },
    { label: '不可服务', value: 'unavailable' }
]

const statusLegend = [
    { label: '可预约', value: 'available', desc: '无显式限制记录，可继续接单。' },
    { label: '已锁定', value: 'locked', desc: '订单待确认阶段锁档，仅可只读查看。' },
    { label: '已占用', value: 'occupied', desc: '订单已确认占用，需随订单流转释放。' },
    { label: '不可服务', value: 'unavailable', desc: '人工设为不可服务，可恢复为可预约。' }
]

const queryParams = reactive({
    keyword: '',
    provider_id: '',
    status: '',
    start_date: '',
    end_date: ''
})

const dateRange = ref<string[]>([])

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getProviderScheduleLists,
    params: queryParams
})

const editRef = shallowRef<InstanceType<typeof ScheduleEdit>>()
const providerOptions = ref<any[]>([])

const getOptions = async () => {
    providerOptions.value = (await getProviderScheduleProviderOptions()) || []
}

const applyDateRange = () => {
    queryParams.start_date = dateRange.value?.[0] || ''
    queryParams.end_date = dateRange.value?.[1] || ''
    resetPage()
}

const resetAll = () => {
    Object.assign(queryParams, {
        keyword: '',
        provider_id: '',
        status: '',
        start_date: '',
        end_date: ''
    })
    dateRange.value = []
    getLists()
}

const openEdit = async (mode: 'add' | 'edit', id?: number) => {
    await nextTick()
    editRef.value?.open(mode, id)
}

const handleDelete = async (id: number) => {
    await feedback.confirm('确认删除当前档期记录？')
    await deleteProviderSchedule({ id })
    getLists()
}

getOptions()
getLists()
</script>

<style lang="scss" scoped>
.schedule-page {
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

.legend-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-top: 18px;
}

.legend-item {
    border: 1px solid rgba(219, 39, 119, 0.12);
    border-radius: 16px;
    background: linear-gradient(180deg, #fff9fb, #fffdf9);
    padding: 14px 16px;
}

.legend-item__title {
    display: block;
    color: #111827;
    font-size: 14px;
    font-weight: 600;
}

.legend-item__desc {
    display: block;
    color: #6b7280;
    font-size: 12px;
    line-height: 1.7;
    margin-top: 8px;
}

.status-cell {
    display: grid;
    gap: 4px;
}

.status-cell__title {
    color: #111827;
    font-weight: 600;
}

.status-cell__desc {
    color: #6b7280;
    font-size: 12px;
    line-height: 1.6;
}
</style>
