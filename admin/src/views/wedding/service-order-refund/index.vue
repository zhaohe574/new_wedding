<template>
    <div class="service-order-refund-page">
        <el-card class="hero-card !border-none" shadow="never">
            <div class="hero-grid">
                <div>
                    <div class="hero-eyebrow">Wedding Refunds</div>
                    <h1 class="hero-title">退款管理</h1>
                    <p class="hero-desc">
                        独立处理婚庆订单退款申请，统一承接用户退款申请审核、后台人工兜底退款，以及关联退款记录与日志查询。
                    </p>
                </div>
                <div class="hero-side">
                    <div class="hero-stat">
                        <span class="hero-stat__label">本轮范围</span>
                        <span class="hero-stat__value">V1 整单退款闭环</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat__label">关键状态</span>
                        <span class="hero-stat__value">待处理 / 退款中 / 已退款 / 退款失败</span>
                    </div>
                </div>
            </div>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-form :model="queryParams" :inline="true" class="mb-[-16px]">
                <el-form-item class="w-[260px]" label="关键字">
                    <el-input v-model="queryParams.keyword" placeholder="订单号/退款单号/服务人员/用户" clearable @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item class="w-[180px]" label="处理状态">
                    <el-select v-model="queryParams.status" clearable>
                        <el-option v-for="item in statusOptions" :key="item.value" :label="item.label" :value="item.value" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[180px]" label="申请来源">
                    <el-select v-model="queryParams.apply_source" clearable>
                        <el-option v-for="item in sourceOptions" :key="item.value" :label="item.label" :value="item.value" />
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
                <el-table-column label="退款单号" prop="refund_record_sn" min-width="170" />
                <el-table-column label="用户" min-width="160">
                    <template #default="{ row }">
                        <div>{{ row.user_nickname || '-' }}</div>
                        <div class="text-xs text-[#9ca3af]">{{ row.user_mobile || '-' }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="服务人员" prop="provider_name" min-width="120" />
                <el-table-column label="申请来源" prop="apply_source_desc" min-width="110" />
                <el-table-column label="退款金额" min-width="110">
                    <template #default="{ row }">￥{{ Number(row.refund_amount || 0).toFixed(2) }}</template>
                </el-table-column>
                <el-table-column label="处理状态" prop="status_desc" min-width="110" />
                <el-table-column label="订单状态" prop="order_status_desc" min-width="110" />
                <el-table-column label="申请时间" min-width="170">
                    <template #default="{ row }">{{ formatTime(row.create_time) }}</template>
                </el-table-column>
                <el-table-column label="操作" width="170" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['wedding.service_order_refund/detail']" type="primary" link @click="openDetail(row.id)">
                            详情
                        </el-button>
                        <el-button
                            v-if="Number(row.status) === 0"
                            v-perms="['wedding.service_order_refund/handle']"
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

        <el-dialog v-model="detailVisible" title="退款申请详情" width="980px" destroy-on-close>
            <template v-if="detailData.refund.id">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="订单号">{{ detailData.refund.sn || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="处理状态">{{ detailData.refund.status_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="申请来源">{{ detailData.refund.apply_source_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="退款金额">￥{{ Number(detailData.refund.refund_amount || 0).toFixed(2) }}</el-descriptions-item>
                    <el-descriptions-item label="用户">{{ detailData.refund.user_nickname || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="用户手机号">{{ detailData.refund.user_mobile || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="服务人员">{{ detailData.refund.provider_name || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="服务日期">{{ detailData.refund.service_date || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="申请时间">{{ formatTime(detailData.refund.create_time) }}</el-descriptions-item>
                    <el-descriptions-item label="处理时间">{{ formatTime(detailData.refund.handle_time) }}</el-descriptions-item>
                    <el-descriptions-item :span="2" label="申请原因">{{ detailData.refund.apply_reason || '-' }}</el-descriptions-item>
                    <el-descriptions-item :span="2" label="处理备注">{{ detailData.refund.handle_remark || '-' }}</el-descriptions-item>
                </el-descriptions>

                <el-card class="mt-4" shadow="never">
                    <template #header>关联订单</template>
                    <el-descriptions :column="2" border>
                        <el-descriptions-item label="订单状态">{{ detailData.order.order_status_desc || '-' }}</el-descriptions-item>
                        <el-descriptions-item label="支付状态">{{ detailData.order.pay_status_desc || '-' }}</el-descriptions-item>
                        <el-descriptions-item label="支付类型">{{ detailData.order.payment_type_desc || '-' }}</el-descriptions-item>
                        <el-descriptions-item label="订单金额">￥{{ Number(detailData.order.order_amount || 0).toFixed(2) }}</el-descriptions-item>
                        <el-descriptions-item label="套餐">{{ detailData.order.package_name || '-' }}</el-descriptions-item>
                        <el-descriptions-item label="服务日期">{{ detailData.order.service_date || '-' }}</el-descriptions-item>
                    </el-descriptions>
                </el-card>

                <el-card class="mt-4" shadow="never">
                    <template #header>退款记录</template>
                    <el-empty v-if="!detailData.refund_record.id" description="暂无退款记录" />
                    <el-descriptions v-else :column="2" border>
                        <el-descriptions-item label="退款单号">{{ detailData.refund_record.sn || '-' }}</el-descriptions-item>
                        <el-descriptions-item label="退款方式">{{ detailData.refund_record.refund_way_text || '-' }}</el-descriptions-item>
                        <el-descriptions-item label="退款状态">{{ detailData.refund_record.refund_status_text || '-' }}</el-descriptions-item>
                        <el-descriptions-item label="退款类型">{{ detailData.refund_record.refund_type_text || '-' }}</el-descriptions-item>
                        <el-descriptions-item label="退款金额">￥{{ Number(detailData.refund_record.refund_amount || 0).toFixed(2) }}</el-descriptions-item>
                        <el-descriptions-item label="交易流水号">{{ detailData.refund_record.transaction_id || '-' }}</el-descriptions-item>
                    </el-descriptions>
                </el-card>

                <el-card class="mt-4" shadow="never">
                    <template #header>退款日志</template>
                    <el-empty v-if="!detailData.refund_logs.length" description="暂无退款日志" />
                    <el-table v-else :data="detailData.refund_logs" size="small">
                        <el-table-column label="日志编号" prop="sn" min-width="160" />
                        <el-table-column label="处理人" prop="handler" min-width="120" />
                        <el-table-column label="退款状态" prop="refund_status_text" min-width="110" />
                        <el-table-column label="退款金额" min-width="100">
                            <template #default="{ row }">￥{{ Number(row.refund_amount || 0).toFixed(2) }}</template>
                        </el-table-column>
                        <el-table-column label="更新时间" min-width="170">
                            <template #default="{ row }">{{ formatTime(row.update_time || row.create_time) }}</template>
                        </el-table-column>
                        <el-table-column label="说明" prop="refund_msg" min-width="220" show-overflow-tooltip />
                    </el-table>
                </el-card>
            </template>
        </el-dialog>

        <el-dialog v-model="handleVisible" title="处理退款申请" width="560px" destroy-on-close>
            <el-form :model="handleForm" label-width="88px">
                <el-form-item label="处理结果">
                    <el-radio-group v-model="handleForm.audit_status">
                        <el-radio :label="1">通过</el-radio>
                        <el-radio :label="2">驳回</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="处理备注">
                    <el-input
                        v-model="handleForm.handle_remark"
                        type="textarea"
                        :rows="4"
                        maxlength="500"
                        show-word-limit
                        placeholder="请填写处理备注"
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

<script lang="ts" setup name="weddingServiceOrderRefundIndex">
import { detailServiceOrderRefund, getServiceOrderRefundLists, handleServiceOrderRefund } from '@/api/wedding'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'
import { reactive, ref } from 'vue'

const statusOptions = [
    { label: '待处理', value: 0 },
    { label: '已驳回', value: 1 },
    { label: '退款中', value: 2 },
    { label: '已退款', value: 3 },
    { label: '退款失败', value: 4 }
]

const sourceOptions = [
    { label: '用户申请', value: 'user' },
    { label: '后台发起', value: 'admin' }
]

const queryParams = reactive({
    keyword: '',
    status: '',
    apply_source: ''
})

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getServiceOrderRefundLists,
    params: queryParams
})

const detailVisible = ref(false)
const detailData = reactive<any>({
    refund: {},
    order: {},
    refund_record: {},
    refund_logs: []
})

const handleVisible = ref(false)
const handleForm = reactive({
    id: 0,
    audit_status: 1,
    handle_remark: ''
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
    queryParams.apply_source = ''
    getLists()
}

const openDetail = async (id: number) => {
    const data = await detailServiceOrderRefund({ id })
    Object.assign(detailData, {
        refund: data?.refund || {},
        order: data?.order || {},
        refund_record: data?.refund_record || {},
        refund_logs: data?.refund_logs || []
    })
    detailVisible.value = true
}

const openHandle = async (id: number) => {
    await openDetail(id)
    handleForm.id = id
    handleForm.audit_status = 1
    handleForm.handle_remark = ''
    handleVisible.value = true
}

const submitHandle = async () => {
    if (!handleForm.id) {
        return
    }
    if (!handleForm.handle_remark.trim()) {
        feedback.msgError('请填写处理备注')
        return
    }
    await handleServiceOrderRefund({
        id: handleForm.id,
        audit_status: handleForm.audit_status,
        handle_remark: handleForm.handle_remark
    })
    feedback.msgSuccess('处理成功')
    handleVisible.value = false
    detailVisible.value = false
    getLists()
}

getLists()
</script>

<style lang="scss" scoped>
.service-order-refund-page {
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
