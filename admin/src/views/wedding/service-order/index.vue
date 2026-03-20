<template>
    <div class="service-order-page">
        <el-card class="hero-card !border-none" shadow="never">
            <div class="hero-grid">
                <div>
                    <div class="hero-eyebrow">Wedding Orders</div>
                    <h1 class="hero-title">{{ voucherOnly ? '线下凭证审核' : '订单管理' }}</h1>
                    <p class="hero-desc">
                        {{ voucherOnly ? '聚焦待线下凭证审核订单，完成审核通过或驳回处理。' : '统一查看婚庆服务订单状态，支持待线下凭证审核订单的详情查看与审核处理。' }}
                    </p>
                </div>
                <div class="hero-side">
                    <div class="hero-stat">
                        <span class="hero-stat__label">当前目标</span>
                        <span class="hero-stat__value">下单到支付受理闭环</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat__label">重点状态</span>
                        <span class="hero-stat__value">待确认 / 待支付 / 待凭证审核</span>
                    </div>
                </div>
            </div>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-form :model="queryParams" :inline="true" class="mb-[-16px]">
                <el-form-item class="w-[260px]" label="关键字">
                    <el-input v-model="queryParams.keyword" placeholder="订单号/服务人员/用户" clearable @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item v-if="!voucherOnly" class="w-[200px]" label="订单状态">
                    <el-select v-model="queryParams.order_status" clearable>
                        <el-option v-for="item in statusOptions" :key="item.value" :label="item.label" :value="item.value" />
                    </el-select>
                </el-form-item>
                <el-form-item label="服务日期">
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
                <el-table-column label="服务日期" prop="service_date" min-width="120" />
                <el-table-column label="金额" min-width="100">
                    <template #default="{ row }">￥{{ Number(row.order_amount || 0).toFixed(2) }}</template>
                </el-table-column>
                <el-table-column label="订单状态" prop="order_status_desc" min-width="140" />
                <el-table-column label="支付类型" prop="payment_type_desc" min-width="110" />
                <el-table-column label="支付状态" prop="pay_status_desc" min-width="100" />
                <el-table-column label="凭证审核" prop="voucher_audit_status_desc" min-width="110" />
                <el-table-column label="操作" width="170" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['wedding.service_order/detail']" type="primary" link @click="openDetail(row.id)">
                            详情
                        </el-button>
                        <el-button
                            v-if="Number(row.order_status) === 21"
                            v-perms="['wedding.service_order/offlineVoucherAudit']"
                            type="danger"
                            link
                            @click="openAudit(row.id)"
                        >
                            审核
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-dialog v-model="detailVisible" title="订单详情" width="920px" destroy-on-close>
            <template v-if="detailData.order.id">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="订单号">{{ detailData.order.sn }}</el-descriptions-item>
                    <el-descriptions-item label="订单状态">{{ detailData.order.order_status_desc }}</el-descriptions-item>
                    <el-descriptions-item label="用户">{{ detailData.order.user_nickname || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="用户手机号">{{ detailData.order.user_mobile || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="服务人员">{{ detailData.order.provider_name }}</el-descriptions-item>
                    <el-descriptions-item label="套餐">{{ detailData.order.package_name }}</el-descriptions-item>
                    <el-descriptions-item label="服务日期">{{ detailData.order.service_date }}</el-descriptions-item>
                    <el-descriptions-item label="订单金额">￥{{ Number(detailData.order.order_amount || 0).toFixed(2) }}</el-descriptions-item>
                    <el-descriptions-item label="地区">
                        {{ detailData.order.province_name }} / {{ detailData.order.city_name }} / {{ detailData.order.district_name }}
                    </el-descriptions-item>
                    <el-descriptions-item label="支付类型">{{ detailData.order.payment_type_desc }}</el-descriptions-item>
                </el-descriptions>

                <el-card class="mt-4" shadow="never">
                    <template #header>模板填写快照</template>
                    <el-empty v-if="!templatePages.length" description="暂无模板快照" />
                    <div v-else class="template-list">
                        <div v-for="(page, pageIndex) in templatePages" :key="pageIndex" class="template-page">
                            <div class="template-page__title">{{ page.title || `第 ${pageIndex + 1} 页` }}</div>
                            <div v-if="page.description" class="template-page__desc">{{ page.description }}</div>
                            <div v-for="field in page.fields || []" :key="field.field_key" class="template-page__field">
                                <span>{{ field.label }}</span>
                                <span>{{ field.display_value || '-' }}</span>
                            </div>
                        </div>
                    </div>
                </el-card>

                <el-card class="mt-4" shadow="never">
                    <template #header>线下凭证</template>
                    <el-empty v-if="!detailData.offline_voucher.id" description="暂无线下凭证" />
                    <template v-else>
                        <div class="text-sm text-[#6b7280] mb-2">审核状态：{{ detailData.offline_voucher.audit_status_desc }}</div>
                        <div v-if="detailData.offline_voucher.remark" class="text-sm text-[#6b7280] mb-3">
                            用户备注：{{ detailData.offline_voucher.remark }}
                        </div>
                        <div class="voucher-images">
                            <el-image
                                v-for="(item, index) in detailData.offline_voucher.voucher_images || []"
                                :key="index"
                                :src="item"
                                fit="cover"
                                preview-teleported
                                :preview-src-list="detailData.offline_voucher.voucher_images || []"
                            />
                        </div>
                    </template>
                </el-card>
            </template>
        </el-dialog>

        <el-dialog v-model="auditVisible" title="线下凭证审核" width="560px" destroy-on-close>
            <el-form :model="auditForm" label-width="88px">
                <el-form-item label="审核结果">
                    <el-radio-group v-model="auditForm.audit_status">
                        <el-radio :label="1">通过</el-radio>
                        <el-radio :label="2">驳回</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="审核说明">
                    <el-input
                        v-model="auditForm.audit_remark"
                        type="textarea"
                        :rows="4"
                        maxlength="500"
                        show-word-limit
                        placeholder="请填写审核说明"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="auditVisible = false">取消</el-button>
                <el-button type="primary" @click="submitAudit">确认审核</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="weddingServiceOrderIndex">
import { detailServiceOrder, getServiceOrderLists, offlineVoucherAuditServiceOrder } from '@/api/wedding'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'
import { computed, reactive, ref } from 'vue'

const props = withDefaults(
    defineProps<{
        voucherOnly?: boolean
    }>(),
    {
        voucherOnly: false
    }
)

const statusOptions = [
    { label: '待服务人员确认', value: 10 },
    { label: '服务人员已拒绝', value: 11 },
    { label: '待支付', value: 20 },
    { label: '待线下凭证审核', value: 21 },
    { label: '待履约', value: 30 },
    { label: '已取消', value: 70 }
]

const queryParams = reactive({
    keyword: '',
    order_status: props.voucherOnly ? 21 : '',
    service_date_start: '',
    service_date_end: '',
    voucher_only: props.voucherOnly ? 1 : 0
})
const dateRange = ref<string[]>([])

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getServiceOrderLists,
    params: queryParams
})

const detailVisible = ref(false)
const detailData = reactive<any>({
    order: {},
    snapshot: {},
    offline_voucher: {}
})
const templatePages = computed(() => detailData.snapshot?.template_snapshot?.pages || [])

const auditVisible = ref(false)
const auditForm = reactive({
    order_id: 0,
    audit_status: 1,
    audit_remark: ''
})

const applyDateRange = () => {
    queryParams.service_date_start = dateRange.value?.[0] || ''
    queryParams.service_date_end = dateRange.value?.[1] || ''
    resetPage()
}

const resetAll = () => {
    Object.assign(queryParams, {
        keyword: '',
        order_status: props.voucherOnly ? 21 : '',
        service_date_start: '',
        service_date_end: '',
        voucher_only: props.voucherOnly ? 1 : 0
    })
    dateRange.value = []
    getLists()
}

const openDetail = async (id: number) => {
    const data = await detailServiceOrder({ id })
    Object.assign(detailData, {
        order: data?.order || {},
        snapshot: data?.snapshot || {},
        offline_voucher: data?.offline_voucher || {}
    })
    detailVisible.value = true
}

const openAudit = async (id: number) => {
    await openDetail(id)
    auditForm.order_id = id
    auditForm.audit_status = 1
    auditForm.audit_remark = ''
    auditVisible.value = true
}

const submitAudit = async () => {
    if (!auditForm.order_id) {
        return
    }
    if (!auditForm.audit_remark.trim()) {
        feedback.msgError('请填写审核说明')
        return
    }
    await offlineVoucherAuditServiceOrder({
        order_id: auditForm.order_id,
        audit_status: auditForm.audit_status,
        audit_remark: auditForm.audit_remark
    })
    auditVisible.value = false
    detailVisible.value = false
    getLists()
}

getLists()
</script>

<style lang="scss" scoped>
.service-order-page {
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

.template-list {
    display: grid;
    gap: 12px;
}

.template-page {
    border: 1px solid #f1f5f9;
    border-radius: 12px;
    padding: 12px;
}

.template-page__title {
    color: #111827;
    font-weight: 600;
}

.template-page__desc {
    color: #6b7280;
    font-size: 12px;
    margin-top: 4px;
}

.template-page__field {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    color: #334155;
    font-size: 13px;
    margin-top: 8px;
}

.voucher-images {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}

.voucher-images :deep(.el-image) {
    width: 100%;
    height: 120px;
    border-radius: 10px;
    overflow: hidden;
}
</style>
