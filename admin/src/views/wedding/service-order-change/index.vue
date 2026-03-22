<template>
    <div class="service-order-change-page">
        <el-card class="hero-card !border-none" shadow="never">
            <div class="hero-grid">
                <div>
                    <div class="hero-eyebrow">Wedding Reschedule</div>
                    <h1 class="hero-title">改期管理</h1>
                    <p class="hero-desc">
                        统一查看婚庆订单改期申请，服务人员优先处理，后台仅对待处理申请做兜底处理。
                    </p>
                </div>
                <div class="hero-side">
                    <div class="hero-stat">
                        <span class="hero-stat__label">本轮范围</span>
                        <span class="hero-stat__value">待处理申请兜底仲裁</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat__label">关键状态</span>
                        <span class="hero-stat__value">待处理 / 已通过 / 已驳回</span>
                    </div>
                </div>
            </div>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-form :model="queryParams" :inline="true" class="mb-[-16px]">
                <el-form-item class="w-[260px]" label="关键字">
                    <el-input v-model="queryParams.keyword" placeholder="订单号/服务人员/用户" clearable @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item class="w-[200px]" label="处理状态">
                    <el-select v-model="queryParams.status" clearable>
                        <el-option v-for="item in statusOptions" :key="item.value" :label="item.label" :value="item.value" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetAll">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table v-loading="pager.loading" :data="pager.lists" size="large">
                <el-table-column label="订单号" prop="sn" min-width="170" />
                <el-table-column label="用户" min-width="160">
                    <template #default="{ row }">
                        <div>{{ row.user_nickname || '-' }}</div>
                        <div class="text-xs text-[#9ca3af]">{{ row.user_mobile || '-' }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="服务人员" prop="provider_name" min-width="120" />
                <el-table-column label="原日期" prop="old_service_date" min-width="120" />
                <el-table-column label="新日期" prop="new_service_date" min-width="120" />
                <el-table-column label="处理状态" prop="status_desc" min-width="110" />
                <el-table-column label="处理角色" prop="handle_role_desc" min-width="110" />
                <el-table-column label="申请时间" min-width="170">
                    <template #default="{ row }">{{ formatTime(row.create_time) }}</template>
                </el-table-column>
                <el-table-column label="操作" width="170" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['wedding.service_order_change/detail']" type="primary" link @click="openDetail(row.id)">
                            详情
                        </el-button>
                        <el-button
                            v-if="Number(row.status) === 0"
                            v-perms="['wedding.service_order_change/handle']"
                            type="danger"
                            link
                            @click="openHandle(row.id)"
                        >
                            处理
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-dialog v-model="detailVisible" title="改期申请详情" width="760px" destroy-on-close>
            <template v-if="detailData.id">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="订单号">{{ detailData.sn || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="处理状态">{{ detailData.status_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="用户">{{ detailData.user_nickname || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="用户手机号">{{ detailData.user_mobile || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="服务人员">{{ detailData.provider_name || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="处理角色">{{ detailData.handle_role_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="原服务日期">{{ detailData.old_service_date || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="新服务日期">{{ detailData.new_service_date || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="申请时间">{{ formatTime(detailData.create_time) }}</el-descriptions-item>
                    <el-descriptions-item label="处理时间">{{ formatTime(detailData.handle_time) }}</el-descriptions-item>
                    <el-descriptions-item :span="2" label="申请原因">{{ detailData.apply_reason || '-' }}</el-descriptions-item>
                    <el-descriptions-item :span="2" label="处理备注">{{ detailData.handle_remark || '-' }}</el-descriptions-item>
                </el-descriptions>
            </template>
        </el-dialog>

        <el-dialog v-model="handleVisible" title="处理改期申请" width="560px" destroy-on-close>
            <el-form :model="handleForm" label-width="88px">
                <el-form-item label="处理结果">
                    <el-radio-group v-model="handleForm.audit_status">
                        <el-radio :label="1">通过</el-radio>
                        <el-radio :label="2">驳回</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="处理说明">
                    <el-input
                        v-model="handleForm.audit_remark"
                        type="textarea"
                        :rows="4"
                        maxlength="500"
                        show-word-limit
                        placeholder="请填写处理说明"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="handleVisible = false">取消</el-button>
                <el-button type="primary" @click="submitHandle">确认处理</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="weddingServiceOrderChangeIndex">
import { detailServiceOrderChange, getServiceOrderChangeLists, handleServiceOrderChange } from '@/api/wedding'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'
import { reactive, ref } from 'vue'

const statusOptions = [
    { label: '待处理', value: 0 },
    { label: '已通过', value: 1 },
    { label: '已驳回', value: 2 }
]

const queryParams = reactive({
    keyword: '',
    status: ''
})

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getServiceOrderChangeLists,
    params: queryParams
})

const detailVisible = ref(false)
const detailData = reactive<any>({})

const handleVisible = ref(false)
const handleForm = reactive({
    id: 0,
    audit_status: 1,
    audit_remark: ''
})

const formatTime = (value: number | string) => {
    if (!value || Number(value) <= 0) {
        return '-'
    }
    const time = new Date(Number(value) * 1000)
    return `${time.getFullYear()}-${String(time.getMonth() + 1).padStart(2, '0')}-${String(time.getDate()).padStart(2, '0')} ${String(
        time.getHours()
    ).padStart(2, '0')}:${String(time.getMinutes()).padStart(2, '0')}:${String(time.getSeconds()).padStart(2, '0')}`
}

const resetAll = () => {
    queryParams.keyword = ''
    queryParams.status = ''
    getLists()
}

const openDetail = async (id: number) => {
    const data = await detailServiceOrderChange({ id })
    Object.assign(detailData, data || {})
    detailVisible.value = true
}

const openHandle = async (id: number) => {
    await openDetail(id)
    handleForm.id = id
    handleForm.audit_status = 1
    handleForm.audit_remark = ''
    handleVisible.value = true
}

const submitHandle = async () => {
    if (!handleForm.id) {
        return
    }
    if (!handleForm.audit_remark.trim()) {
        feedback.msgError('请填写处理说明')
        return
    }
    await handleServiceOrderChange({
        id: handleForm.id,
        audit_status: handleForm.audit_status,
        audit_remark: handleForm.audit_remark
    })
    feedback.msgSuccess('处理成功')
    handleVisible.value = false
    detailVisible.value = false
    getLists()
}

getLists()
</script>

<style lang="scss" scoped>
.service-order-change-page {
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
