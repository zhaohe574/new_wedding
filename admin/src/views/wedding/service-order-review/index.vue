<template>
    <div class="service-order-review-page">
        <el-card class="hero-card !border-none" shadow="never">
            <div class="hero-grid">
                <div>
                    <div class="hero-eyebrow">Wedding Reviews</div>
                    <h1 class="hero-title">评价审核</h1>
                    <p class="hero-desc">
                        聚焦婚庆订单评价审核流转，兼容管理员审核、服务人员审核与服务人员初审后管理员终审。
                    </p>
                </div>
                <div class="hero-side">
                    <div class="hero-stat">
                        <span class="hero-stat__label">本轮能力</span>
                        <span class="hero-stat__value">订单评价单独审核闭环</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat__label">当前焦点</span>
                        <span class="hero-stat__value">待审核 / 审核通过 / 审核驳回</span>
                    </div>
                </div>
            </div>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-form :model="queryParams" :inline="true" class="mb-[-16px]">
                <el-form-item class="w-[260px]" label="关键字">
                    <el-input v-model="queryParams.keyword" placeholder="订单号/服务人员/用户" clearable @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item class="w-[200px]" label="审核状态">
                    <el-select v-model="queryParams.audit_status" clearable>
                        <el-option v-for="item in auditStatusOptions" :key="item.value" :label="item.label" :value="item.value" />
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
                <el-table-column label="套餐" prop="package_name" min-width="140" />
                <el-table-column label="评分" min-width="80">
                    <template #default="{ row }">{{ Number(row.score || 0) }} 分</template>
                </el-table-column>
                <el-table-column label="审核状态" prop="audit_status_desc" min-width="110" />
                <el-table-column label="审核归属" prop="audit_role_desc" min-width="140" />
                <el-table-column label="提交时间" min-width="170">
                    <template #default="{ row }">{{ formatTime(row.create_time) }}</template>
                </el-table-column>
                <el-table-column label="操作" width="170" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['wedding.service_order_review/detail']" type="primary" link @click="openDetail(row.id)">
                            详情
                        </el-button>
                        <el-button
                            v-if="Number(row.audit_status) === 0"
                            v-perms="['wedding.service_order_review/audit']"
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

        <el-dialog v-model="detailVisible" title="评价详情" width="820px" destroy-on-close>
            <template v-if="detailData.id">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="订单号">{{ detailData.sn || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="审核状态">{{ detailData.audit_status_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="用户">{{ detailData.user_nickname || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="用户手机号">{{ detailData.user_mobile || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="服务人员">{{ detailData.provider_name || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="审核归属">{{ detailData.audit_role_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="套餐">{{ detailData.package_name || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="评分">{{ Number(detailData.score || 0) }} 分</el-descriptions-item>
                    <el-descriptions-item label="提交时间">{{ formatTime(detailData.create_time) }}</el-descriptions-item>
                    <el-descriptions-item label="审核时间">{{ formatTime(detailData.audit_time) }}</el-descriptions-item>
                    <el-descriptions-item :span="2" label="评价内容">{{ detailData.content || '-' }}</el-descriptions-item>
                    <el-descriptions-item :span="2" label="审核备注">{{ detailData.audit_remark || '-' }}</el-descriptions-item>
                </el-descriptions>

                <el-card class="mt-4" shadow="never">
                    <template #header>评价图片</template>
                    <el-empty v-if="!detailData.images?.length" description="暂无评价图片" />
                    <div v-else class="review-images">
                        <el-image
                            v-for="(item, index) in detailData.images"
                            :key="index"
                            :src="item"
                            fit="cover"
                            preview-teleported
                            :preview-src-list="detailData.images"
                        />
                    </div>
                </el-card>
            </template>
        </el-dialog>

        <el-dialog v-model="auditVisible" title="评价审核" width="560px" destroy-on-close>
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

<script lang="ts" setup name="weddingServiceOrderReviewIndex">
import { auditServiceOrderReview, detailServiceOrderReview, getServiceOrderReviewLists } from '@/api/wedding'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'
import { reactive, ref } from 'vue'

const auditStatusOptions = [
    { label: '待审核', value: 0 },
    { label: '审核通过', value: 1 },
    { label: '审核驳回', value: 2 }
]

const queryParams = reactive({
    keyword: '',
    audit_status: ''
})

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getServiceOrderReviewLists,
    params: queryParams
})

const detailVisible = ref(false)
const detailData = reactive<any>({})

const auditVisible = ref(false)
const auditForm = reactive({
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
    queryParams.audit_status = ''
    getLists()
}

const openDetail = async (id: number) => {
    const data = await detailServiceOrderReview({ id })
    Object.assign(detailData, data || {})
    detailVisible.value = true
}

const openAudit = async (id: number) => {
    await openDetail(id)
    auditForm.id = id
    auditForm.audit_status = 1
    auditForm.audit_remark = ''
    auditVisible.value = true
}

const submitAudit = async () => {
    if (!auditForm.id) {
        return
    }
    if (!auditForm.audit_remark.trim()) {
        feedback.msgError('请填写审核说明')
        return
    }
    await auditServiceOrderReview({
        id: auditForm.id,
        audit_status: auditForm.audit_status,
        audit_remark: auditForm.audit_remark
    })
    feedback.msgSuccess('审核成功')
    auditVisible.value = false
    detailVisible.value = false
    getLists()
}

getLists()
</script>

<style lang="scss" scoped>
.service-order-review-page {
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

.review-images {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.review-images :deep(.el-image) {
    width: 100%;
    height: 120px;
    border-radius: 12px;
    overflow: hidden;
}
</style>
