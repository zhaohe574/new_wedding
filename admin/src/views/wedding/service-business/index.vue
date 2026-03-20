<template>
    <div class="service-business-page">
        <el-card class="hero-card !border-none" shadow="never">
            <div class="hero-grid">
                <div>
                    <div class="hero-eyebrow">婚庆预约系统</div>
                    <h1 class="hero-title">业务规则配置中心</h1>
                    <p class="hero-desc">
                        这里统一维护交易、审核、互动、通知、展示五类基线规则，并集中管理驾驶舱授权名单与入口开关。
                    </p>
                </div>
                <div class="hero-side">
                    <div class="hero-stat">
                        <span class="hero-stat__label">当前阶段</span>
                        <span class="hero-stat__value">V1 基线</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat__label">配置范围</span>
                        <span class="hero-stat__value">5 大规则组</span>
                    </div>
                </div>
            </div>
        </el-card>

        <el-form :model="formData" label-width="160px" class="mt-4">
            <el-card class="!border-none mt-4" shadow="never">
                <template #header>
                    <div class="section-head">
                        <span>交易规则</span>
                        <span class="section-tip">控制接单确认、支付方式与支付超时口径</span>
                    </div>
                </template>
                <el-form-item label="开启在线支付">
                    <el-switch v-model="formData.trade.online_pay_enabled" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item label="开启线下凭证">
                    <el-switch v-model="formData.trade.offline_voucher_enabled" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item label="待确认超时（分钟）">
                    <div class="w-[240px]">
                        <el-input-number v-model="formData.trade.provider_confirm_timeout_minutes" :min="1" :max="1440" />
                    </div>
                </el-form-item>
                <el-form-item label="待支付超时（分钟）">
                    <div class="w-[240px]">
                        <el-input-number v-model="formData.trade.pay_timeout_minutes" :min="1" :max="1440" />
                    </div>
                </el-form-item>
            </el-card>

            <el-card class="!border-none mt-4" shadow="never">
                <template #header>
                    <div class="section-head">
                        <span>审核规则</span>
                        <span class="section-tip">统一资料、动态、评论、评价的审核模式</span>
                    </div>
                </template>
                <el-form-item label="资料审核模式">
                    <div class="w-[280px]">
                        <el-select v-model="formData.review.provider_profile_review_mode">
                            <el-option label="管理员审核" value="admin" />
                            <el-option label="服务人员审核" value="provider" />
                            <el-option label="服务人员初审后管理员终审" value="provider_then_admin" />
                        </el-select>
                    </div>
                </el-form-item>
                <el-form-item label="动态审核模式">
                    <div class="w-[280px]">
                        <el-select v-model="formData.review.post_review_mode">
                            <el-option label="管理员审核" value="admin" />
                            <el-option label="服务人员审核" value="provider" />
                            <el-option label="服务人员初审后管理员终审" value="provider_then_admin" />
                        </el-select>
                    </div>
                </el-form-item>
                <el-form-item label="评论审核模式">
                    <div class="w-[280px]">
                        <el-select v-model="formData.review.comment_review_mode">
                            <el-option label="管理员审核" value="admin" />
                            <el-option label="服务人员审核" value="provider" />
                            <el-option label="服务人员初审后管理员终审" value="provider_then_admin" />
                        </el-select>
                    </div>
                </el-form-item>
                <el-form-item label="评价审核模式">
                    <div class="w-[280px]">
                        <el-select v-model="formData.review.order_review_mode">
                            <el-option label="管理员审核" value="admin" />
                            <el-option label="服务人员审核" value="provider" />
                            <el-option label="服务人员初审后管理员终审" value="provider_then_admin" />
                        </el-select>
                    </div>
                </el-form-item>
            </el-card>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mt-4">
                <el-card class="!border-none" shadow="never">
                    <template #header>
                        <div class="section-head">
                            <span>互动规则</span>
                            <span class="section-tip">控制动态、评论、评价入口</span>
                        </div>
                    </template>
                    <el-form-item label="允许发布动态">
                        <el-switch v-model="formData.interaction.post_enabled" :active-value="1" :inactive-value="0" />
                    </el-form-item>
                    <el-form-item label="允许新增评论">
                        <el-switch v-model="formData.interaction.comment_enabled" :active-value="1" :inactive-value="0" />
                    </el-form-item>
                    <el-form-item label="允许订单评价">
                        <el-switch v-model="formData.interaction.order_review_enabled" :active-value="1" :inactive-value="0" />
                    </el-form-item>
                </el-card>

                <el-card class="!border-none" shadow="never">
                    <template #header>
                        <div class="section-head">
                            <span>通知规则</span>
                            <span class="section-tip">控制业务通知是否对外触发</span>
                        </div>
                    </template>
                    <el-form-item label="站内通知">
                        <el-switch v-model="formData.notice.system_notice_enabled" :active-value="1" :inactive-value="0" />
                    </el-form-item>
                    <el-form-item label="订阅消息">
                        <el-switch v-model="formData.notice.mnp_notice_enabled" :active-value="1" :inactive-value="0" />
                    </el-form-item>
                    <el-form-item label="企业微信消息">
                        <el-switch v-model="formData.notice.work_wechat_notice_enabled" :active-value="1" :inactive-value="0" />
                    </el-form-item>
                </el-card>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mt-4">
                <el-card class="!border-none" shadow="never">
                    <template #header>
                        <div class="section-head">
                            <span>展示与入口</span>
                            <span class="section-tip">控制用户端可见入口与业务页显隐</span>
                        </div>
                    </template>
                    <el-form-item label="服务人员中心">
                        <el-switch v-model="formData.display.provider_center_enabled" :active-value="1" :inactive-value="0" />
                    </el-form-item>
                    <el-form-item label="经营驾驶舱">
                        <el-switch v-model="formData.display.dashboard_enabled" :active-value="1" :inactive-value="0" />
                    </el-form-item>
                    <el-form-item label="婚礼基础档案">
                        <el-switch v-model="formData.display.wedding_profile_enabled" :active-value="1" :inactive-value="0" />
                    </el-form-item>
                </el-card>

                <el-card class="!border-none" shadow="never">
                    <template #header>
                        <div class="section-head">
                            <span>权限口径</span>
                            <span class="section-tip">服务人员权限已切换到真实主档，页面仅保留驾驶舱授权名单</span>
                        </div>
                    </template>
                    <div class="source-card">
                        <div class="source-card__title">服务人员中心</div>
                        <div class="source-card__desc">
                            账号是否可进入服务人员中心，统一由“服务人员档案”中的 `service_provider.user_id`
                            绑定关系决定，不再维护 JSON 占位名单。
                        </div>
                    </div>
                    <el-form-item label="驾驶舱用户">
                        <div class="w-full">
                            <el-input
                                v-model="dashboardUsersText"
                                type="textarea"
                                :rows="5"
                                placeholder="[1001, 1002]"
                            />
                            <div class="form-tips">格式为 JSON 数组，内容为用户 ID。</div>
                        </div>
                    </el-form-item>
                </el-card>
            </div>
        </el-form>

        <footer-btns>
            <el-button type="primary" @click="handleSubmit">保存配置</el-button>
        </footer-btns>
    </div>
</template>

<script lang="ts" setup name="serviceBusinessConfig">
import { ElMessage } from 'element-plus'

import { getServiceBusinessConfig, setServiceBusinessConfig } from '@/api/setting/serviceBusiness'

const createDefaultForm = () => ({
    trade: {
        online_pay_enabled: 1,
        offline_voucher_enabled: 1,
        provider_confirm_timeout_minutes: 30,
        pay_timeout_minutes: 30
    },
    review: {
        provider_profile_review_mode: 'admin',
        post_review_mode: 'admin',
        comment_review_mode: 'provider_then_admin',
        order_review_mode: 'admin'
    },
    interaction: {
        post_enabled: 1,
        comment_enabled: 1,
        order_review_enabled: 1
    },
    notice: {
        system_notice_enabled: 1,
        mnp_notice_enabled: 1,
        work_wechat_notice_enabled: 0
    },
    display: {
        provider_center_enabled: 1,
        dashboard_enabled: 1,
        wedding_profile_enabled: 1
    },
    dashboard_view_users: [] as number[]
})

const formData = reactive(createDefaultForm())
const dashboardUsersText = ref('[]')

const syncText = () => {
    dashboardUsersText.value = JSON.stringify(formData.dashboard_view_users, null, 2)
}

const getDetail = async () => {
    const data = await getServiceBusinessConfig()
    const defaultForm = createDefaultForm()
    Object.assign(formData.trade, defaultForm.trade, data.trade || {})
    Object.assign(formData.review, defaultForm.review, data.review || {})
    Object.assign(formData.interaction, defaultForm.interaction, data.interaction || {})
    Object.assign(formData.notice, defaultForm.notice, data.notice || {})
    Object.assign(formData.display, defaultForm.display, data.display || {})
    formData.dashboard_view_users = Array.isArray(data.dashboard_view_users) ? data.dashboard_view_users : []
    syncText()
}

const handleSubmit = async () => {
    try {
        formData.dashboard_view_users = JSON.parse(dashboardUsersText.value || '[]')
    } catch (error) {
        ElMessage.error('授权名单 JSON 格式不正确')
        return
    }

    await setServiceBusinessConfig(formData)
    await getDetail()
}

getDetail()
</script>

<style lang="scss" scoped>
.service-business-page {
    padding-bottom: 80px;
}

.hero-card {
    background:
        radial-gradient(circle at top left, rgba(219, 39, 119, 0.16), transparent 32%),
        radial-gradient(circle at right bottom, rgba(202, 138, 4, 0.16), transparent 28%),
        linear-gradient(135deg, #fffdf8, #fff7fb 48%, #fffef8);
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
    font-weight: 700;
}

.section-head {
    display: flex;
    align-items: baseline;
    gap: 12px;
    flex-wrap: wrap;
}

.section-tip {
    color: #9ca3af;
    font-size: 12px;
}

.source-card {
    margin-bottom: 18px;
    padding: 16px 18px;
    border-radius: 18px;
    background: linear-gradient(180deg, #fff9fb, #fffdf9);
    border: 1px solid rgba(219, 39, 119, 0.12);
}

.source-card__title {
    color: #111827;
    font-size: 14px;
    font-weight: 600;
}

.source-card__desc {
    margin-top: 10px;
    color: #6b7280;
    line-height: 1.8;
}

@media (max-width: 1280px) {
    .hero-grid {
        grid-template-columns: 1fr;
    }
}
</style>
