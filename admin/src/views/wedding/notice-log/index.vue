<template>
    <div class="notice-log-page">
        <el-card class="hero-card !border-none" shadow="never">
            <div class="hero-grid">
                <div>
                    <div class="hero-eyebrow">Wedding Notice Logs</div>
                    <h1 class="hero-title">通知日志</h1>
                    <p class="hero-desc">
                        统一排查婚庆订单站外通知发送结果，本轮仅提供日志查询与失败定位，不做重试与人工补发。
                    </p>
                </div>
                <div class="hero-side">
                    <div class="hero-stat">
                        <span class="hero-stat__label">当前通道</span>
                        <span class="hero-stat__value">小程序订阅消息 / 企业微信应用</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat__label">排查重点</span>
                        <span class="hero-stat__value">配置缺失、账号缺失、模板缺失</span>
                    </div>
                </div>
            </div>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-tabs v-model="tabActive" @tab-change="handleTabChange">
                <el-tab-pane label="小程序订阅消息" name="mnp" />
                <el-tab-pane label="企业微信应用消息" name="wecom" />
            </el-tabs>

            <el-form :model="queryParams" :inline="true" class="mb-[-16px]">
                <el-form-item class="w-[260px]" label="关键字">
                    <el-input
                        v-model="queryParams.keyword"
                        :placeholder="tabActive === 'mnp' ? '场景/模板ID/用户' : '场景/接收账号/服务人员'"
                        clearable
                        @keyup.enter="loadCurrent"
                    />
                </el-form-item>
                <el-form-item class="w-[180px]" label="发送状态">
                    <el-select v-model="queryParams.send_status" clearable>
                        <el-option label="发送失败" :value="0" />
                        <el-option label="发送成功" :value="1" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="loadCurrent">查询</el-button>
                    <el-button @click="resetAll">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table v-if="tabActive === 'mnp'" v-loading="mnpPager.loading" :data="mnpPager.lists" size="large">
                <el-table-column label="场景" prop="scene_name" min-width="150" />
                <el-table-column label="用户" min-width="160">
                    <template #default="{ row }">
                        <div>{{ row.user_nickname || '-' }}</div>
                        <div class="table-sub">{{ row.user_mobile || '-' }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="模板ID" prop="template_id" min-width="180" />
                <el-table-column label="OpenID" prop="openid" min-width="180" show-overflow-tooltip />
                <el-table-column label="发送状态" min-width="100">
                    <template #default="{ row }">
                        <el-tag v-if="Number(row.send_status) === 1">成功</el-tag>
                        <el-tag v-else type="danger">失败</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="错误信息" prop="error_message" min-width="220" show-overflow-tooltip />
                <el-table-column label="发送时间" min-width="170">
                    <template #default="{ row }">{{ formatTime(row.create_time) }}</template>
                </el-table-column>
                <el-table-column label="操作" width="90" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="openDetail(row)">详情</el-button>
                    </template>
                </el-table-column>
            </el-table>

            <el-table v-else v-loading="wecomPager.loading" :data="wecomPager.lists" size="large">
                <el-table-column label="场景" prop="scene_name" min-width="150" />
                <el-table-column label="服务人员" prop="provider_name" min-width="140" />
                <el-table-column label="接收账号" prop="receiver_userid" min-width="180" />
                <el-table-column label="AgentID" prop="agent_id" min-width="120" />
                <el-table-column label="发送状态" min-width="100">
                    <template #default="{ row }">
                        <el-tag v-if="Number(row.send_status) === 1">成功</el-tag>
                        <el-tag v-else type="danger">失败</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="错误信息" prop="error_message" min-width="220" show-overflow-tooltip />
                <el-table-column label="发送时间" min-width="170">
                    <template #default="{ row }">{{ formatTime(row.create_time) }}</template>
                </el-table-column>
                <el-table-column label="操作" width="90" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="openDetail(row)">详情</el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="flex justify-end mt-4">
                <pagination v-if="tabActive === 'mnp'" v-model="mnpPager" @change="loadMnp" />
                <pagination v-else v-model="wecomPager" @change="loadWecom" />
            </div>
        </el-card>

        <el-dialog v-model="detailVisible" title="通知日志详情" width="960px" destroy-on-close>
            <template v-if="detailData.id">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="场景">{{ detailData.scene_name || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="发送状态">
                        {{ Number(detailData.send_status) === 1 ? '成功' : '失败' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="模板ID / AgentID">
                        {{ detailData.template_id || detailData.agent_id || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="接收标识">
                        {{ detailData.openid || detailData.receiver_userid || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="发送时间">
                        {{ formatTime(detailData.create_time) }}
                    </el-descriptions-item>
                    <el-descriptions-item label="错误信息">
                        {{ detailData.error_message || '-' }}
                    </el-descriptions-item>
                </el-descriptions>

                <div class="detail-grid mt-4">
                    <el-card shadow="never">
                        <template #header>请求参数</template>
                        <pre class="json-block">{{ formatPayload(detailData.request_data) }}</pre>
                    </el-card>
                    <el-card shadow="never">
                        <template #header>响应结果</template>
                        <pre class="json-block">{{ formatPayload(detailData.response_data) }}</pre>
                    </el-card>
                </div>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="weddingNoticeLogIndex">
import { getNoticeMnpLogLists, getNoticeWecomLogLists } from '@/api/wedding'
import { usePaging } from '@/hooks/usePaging'

const tabActive = ref<'mnp' | 'wecom'>('mnp')
const queryParams = reactive({
    keyword: '',
    send_status: ''
})

const { pager: mnpPager, getLists: loadMnp } = usePaging({
    fetchFun: getNoticeMnpLogLists,
    params: queryParams
})

const { pager: wecomPager, getLists: loadWecom } = usePaging({
    fetchFun: getNoticeWecomLogLists,
    params: queryParams
})

const detailVisible = ref(false)
const detailData = reactive<any>({})

const formatTime = (value: number | string) => {
    if (!value || Number(value) <= 0) {
        return '-'
    }
    const time = new Date(Number(value) * 1000)
    return `${time.getFullYear()}-${String(time.getMonth() + 1).padStart(2, '0')}-${String(time.getDate()).padStart(2, '0')} ${String(
        time.getHours()
    ).padStart(2, '0')}:${String(time.getMinutes()).padStart(2, '0')}:${String(time.getSeconds()).padStart(2, '0')}`
}

const formatPayload = (value: any) => {
    if (value === null || value === undefined || value === '') {
        return '-'
    }
    if (typeof value === 'object') {
        return JSON.stringify(value, null, 2)
    }

    try {
        return JSON.stringify(JSON.parse(String(value)), null, 2)
    } catch (error) {
        return String(value)
    }
}

const loadCurrent = () => {
    tabActive.value === 'mnp' ? loadMnp() : loadWecom()
}

const resetAll = () => {
    queryParams.keyword = ''
    queryParams.send_status = ''
    loadCurrent()
}

const handleTabChange = () => {
    loadCurrent()
}

const openDetail = (row: Record<string, any>) => {
    Object.assign(detailData, row || {})
    detailVisible.value = true
}

loadCurrent()
</script>

<style lang="scss" scoped>
.notice-log-page {
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

.table-sub {
    color: #9ca3af;
    font-size: 12px;
    line-height: 1.6;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.json-block {
    min-height: 320px;
    margin: 0;
    padding: 16px;
    border-radius: 12px;
    background: #fffafc;
    color: #4b5563;
    font-size: 12px;
    line-height: 1.8;
    white-space: pre-wrap;
    word-break: break-all;
}
</style>
