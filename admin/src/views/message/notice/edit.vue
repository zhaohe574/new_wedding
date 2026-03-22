<template>
    <div class="notice-edit-page">
        <el-card class="hero-card !border-none" shadow="never">
            <div class="hero-grid">
                <div>
                    <div class="hero-eyebrow">Wedding Notice</div>
                    <h1 class="hero-title">通知场景配置</h1>
                    <p class="hero-desc">
                        同一场景统一维护站内通知、小程序订阅消息与企业微信应用消息，保证订单关键节点的消息口径一致。
                    </p>
                </div>
                <div class="hero-badges">
                    <span class="hero-badge">场景：{{ formData.scene_name || '-' }}</span>
                    <span class="hero-badge hero-badge--gold">V1 多通道通知</span>
                </div>
            </div>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-page-header content="编辑通知设置" @back="$router.back()" />
        </el-card>

        <el-form :model="formData" label-width="120px" v-loading="loading">
            <el-card class="!border-none mt-4" shadow="never">
                <div class="font-medium mb-6">场景信息</div>
                <el-form-item label="通知名称">{{ formData.scene_name || '-' }}</el-form-item>
                <el-form-item label="通知类型">{{ formData.type || '-' }}</el-form-item>
                <el-form-item label="通知业务">{{ formData.scene_desc || '-' }}</el-form-item>
            </el-card>

            <el-card class="!border-none mt-4" shadow="never">
                <div class="font-medium mb-6">站内通知</div>
                <el-form-item label="开启状态">
                    <el-radio-group v-model="formData.system_notice.status">
                        <el-radio :label="0">关闭</el-radio>
                        <el-radio :label="1">开启</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="通知标题">
                    <div class="w-full max-w-[560px]">
                        <el-input v-model="formData.system_notice.title" placeholder="请输入站内通知标题" />
                    </div>
                </el-form-item>
                <el-form-item label="通知内容">
                    <div class="w-full max-w-[640px]">
                        <el-input
                            v-model="formData.system_notice.content"
                            type="textarea"
                            :rows="4"
                            placeholder="请输入站内通知内容"
                        />
                        <div class="form-tips mt-2">
                            <div v-for="(item, index) in formData.system_notice.tips" :key="`system-${index}`">
                                {{ item }}
                            </div>
                        </div>
                    </div>
                </el-form-item>
            </el-card>

            <el-card class="!border-none mt-4" shadow="never">
                <div class="font-medium mb-6">短信通知</div>
                <el-form-item label="开启状态">
                    <el-radio-group v-model="formData.sms_notice.status">
                        <el-radio :label="0">关闭</el-radio>
                        <el-radio :label="1">开启</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="模板ID">
                    <div class="w-full max-w-[360px]">
                        <el-input v-model="formData.sms_notice.template_id" placeholder="请输入短信模板ID" />
                    </div>
                </el-form-item>
                <el-form-item label="短信内容">
                    <div class="w-full max-w-[640px]">
                        <el-input
                            v-model="formData.sms_notice.content"
                            type="textarea"
                            :rows="4"
                            placeholder="请输入短信模板文案"
                        />
                        <div class="form-tips mt-2">
                            <div v-for="(item, index) in formData.sms_notice.tips" :key="`sms-${index}`">
                                {{ item }}
                            </div>
                        </div>
                    </div>
                </el-form-item>
            </el-card>

            <el-card class="!border-none mt-4" shadow="never">
                <div class="font-medium mb-6">小程序订阅消息</div>
                <el-form-item label="开启状态">
                    <el-radio-group v-model="formData.mnp_notice.status">
                        <el-radio :label="0">关闭</el-radio>
                        <el-radio :label="1">开启</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="模板ID">
                    <div class="w-full max-w-[360px]">
                        <el-input v-model="formData.mnp_notice.template_id" placeholder="请输入订阅消息模板ID" />
                    </div>
                </el-form-item>
                <el-form-item label="模板编号">
                    <div class="w-full max-w-[360px]">
                        <el-input v-model="formData.mnp_notice.template_sn" placeholder="请输入模板编号" />
                    </div>
                </el-form-item>
                <el-form-item label="模板名称">
                    <div class="w-full max-w-[360px]">
                        <el-input v-model="formData.mnp_notice.name" placeholder="用于后台识别的模板名称" />
                    </div>
                </el-form-item>
                <el-form-item label="字段映射 JSON">
                    <div class="w-full max-w-[720px]">
                        <el-input
                            v-model="mnpTplText"
                            type="textarea"
                            :rows="7"
                            placeholder='例如：[{"key":"thing1","value":"订单 {order_sn}"}]'
                        />
                        <div class="form-tips mt-2">
                            <div v-for="(item, index) in formData.mnp_notice.tips" :key="`mnp-${index}`">
                                {{ item }}
                            </div>
                        </div>
                    </div>
                </el-form-item>
            </el-card>

            <el-card class="!border-none mt-4" shadow="never">
                <div class="font-medium mb-6">企业微信应用消息</div>
                <el-form-item label="开启状态">
                    <el-radio-group v-model="formData.work_notice.status">
                        <el-radio :label="0">关闭</el-radio>
                        <el-radio :label="1">开启</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="消息类型">
                    <div class="w-full max-w-[240px]">
                        <el-select v-model="formData.work_notice.message_type">
                            <el-option label="文本卡片" value="textcard" />
                            <el-option label="纯文本" value="text" />
                        </el-select>
                    </div>
                </el-form-item>
                <el-form-item label="消息标题">
                    <div class="w-full max-w-[560px]">
                        <el-input v-model="formData.work_notice.title" placeholder="请输入企业微信消息标题" />
                    </div>
                </el-form-item>
                <el-form-item label="消息描述">
                    <div class="w-full max-w-[720px]">
                        <el-input
                            v-model="formData.work_notice.description"
                            type="textarea"
                            :rows="4"
                            placeholder="请输入企业微信消息描述"
                        />
                    </div>
                </el-form-item>
                <el-form-item label="跳转链接">
                    <div class="w-full max-w-[720px]">
                        <el-input v-model="formData.work_notice.url" placeholder="可填写 H5 链接，留空则走默认链接" />
                    </div>
                </el-form-item>
                <el-form-item label="按钮文案">
                    <div class="w-full max-w-[240px]">
                        <el-input v-model="formData.work_notice.button_text" placeholder="查看详情" />
                    </div>
                </el-form-item>
                <el-form-item label="通道说明">
                    <div class="form-tips">
                        <div v-for="(item, index) in formData.work_notice.tips" :key="`work-${index}`">
                            {{ item }}
                        </div>
                    </div>
                </el-form-item>
            </el-card>
        </el-form>

        <footer-btns>
            <el-button type="primary" @click="handleSave">保存配置</el-button>
        </footer-btns>
    </div>
</template>

<script lang="ts" setup name="noticeEdit">
import { pick } from 'lodash'

import { noticeDetail, setNoticeConfig } from '@/api/message'
import useMultipleTabs from '@/hooks/useMultipleTabs'
import feedback from '@/utils/feedback'

const route = useRoute()
const router = useRouter()
const { removeTab } = useMultipleTabs()

const loading = ref(false)
const mnpTplText = ref('[]')

const createDefaultForm = () => ({
    id: '',
    scene_name: '',
    type: '',
    scene_desc: '',
    system_notice: {
        type: 'system',
        title: '',
        content: '',
        status: 0,
        tips: [] as string[]
    },
    sms_notice: {
        type: 'sms',
        status: 0,
        template_id: '',
        content: '',
        tips: [] as string[]
    },
    oa_notice: {
        type: 'oa'
    },
    mnp_notice: {
        type: 'mnp',
        status: 0,
        template_id: '',
        template_sn: '',
        name: '',
        tpl: [] as Array<Record<string, any>>,
        tips: [] as string[]
    },
    work_notice: {
        type: 'work',
        status: 0,
        message_type: 'textcard',
        title: '',
        description: '',
        content: '',
        url: '',
        button_text: '查看详情',
        tips: [] as string[]
    }
})

const formData = reactive(createDefaultForm())

const syncMnpTplText = () => {
    mnpTplText.value = JSON.stringify(formData.mnp_notice.tpl || [], null, 2)
}

const getDetails = async () => {
    loading.value = true
    try {
        const data = await noticeDetail({
            id: route.query.id
        })
        const defaultForm = createDefaultForm()
        Object.assign(formData, defaultForm, data || {})
        Object.assign(formData.system_notice, defaultForm.system_notice, data?.system_notice || {})
        Object.assign(formData.sms_notice, defaultForm.sms_notice, data?.sms_notice || {})
        Object.assign(formData.mnp_notice, defaultForm.mnp_notice, data?.mnp_notice || {})
        Object.assign(formData.work_notice, defaultForm.work_notice, data?.work_notice || {})
        syncMnpTplText()
    } finally {
        loading.value = false
    }
}

const handleSave = async () => {
    try {
        formData.mnp_notice.tpl = JSON.parse(mnpTplText.value || '[]')
    } catch (error) {
        feedback.msgError('订阅消息字段映射 JSON 格式不正确')
        return
    }

    const data = {
        id: formData.id,
        template: pick(formData, ['system_notice', 'sms_notice', 'oa_notice', 'mnp_notice', 'work_notice'])
    }

    await setNoticeConfig(data)
    feedback.msgSuccess('操作成功')
    removeTab()
    router.back()
}

route.query.id && getDetails()
</script>

<style lang="scss" scoped>
.notice-edit-page {
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
    grid-template-columns: minmax(0, 1.4fr) minmax(260px, 0.8fr);
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

.hero-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: flex-end;
}

.hero-badge {
    padding: 10px 16px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.88);
    border: 1px solid rgba(219, 39, 119, 0.12);
    color: #831843;
    font-size: 12px;
}

.hero-badge--gold {
    color: #a16207;
    border-color: rgba(202, 138, 4, 0.18);
}

.form-tips {
    color: #9ca3af;
    font-size: 12px;
    line-height: 1.8;
}

@media (max-width: 1280px) {
    .hero-grid {
        grid-template-columns: 1fr;
    }

    .hero-badges {
        justify-content: flex-start;
    }
}
</style>
