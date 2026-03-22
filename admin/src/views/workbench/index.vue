<template>
    <div class="workbench-page">
        <el-card class="hero-card !border-none mb-4" shadow="never">
            <div class="hero-layout">
                <div>
                    <div class="hero-eyebrow">Wedding Operations</div>
                    <div class="hero-title">婚庆预约业务工作台</div>
                    <div class="hero-desc">
                        工作台已切换为婚庆业务真实口径，统一展示订单、支付、改期与评价审核待办。
                    </div>
                </div>
                <div class="hero-badges">
                    <span class="hero-badge">更新时间 {{ weddingOverview.time || '--' }}</span>
                    <span class="hero-badge hero-badge--gold">V1 收口执行中</span>
                </div>
            </div>
        </el-card>

        <div class="lg:flex">
            <el-card class="!border-none mb-4 lg:mr-4 lg:w-[350px]" shadow="never">
                <template #header>
                    <span class="card-title">版本信息</span>
                </template>
                <div>
                    <div class="flex leading-9">
                        <div class="w-20">平台名称</div>
                        <span>{{ workbenchData.version.name }}</span>
                    </div>
                    <div class="flex leading-9">
                        <div class="w-20">当前版本</div>
                        <span>{{ workbenchData.version.version }}</span>
                    </div>
                    <div class="flex leading-9">
                        <div class="w-20">获取渠道</div>
                        <div>
                            <a :href="workbenchData.version.channel.website" target="_blank">
                                <el-button type="success" size="small">官网</el-button>
                            </a>
                            <a class="ml-3" :href="workbenchData.version.channel.gitee" target="_blank">
                                <el-button type="danger" size="small">Gitee</el-button>
                            </a>
                        </div>
                    </div>
                </div>
            </el-card>

            <el-card class="!border-none mb-4 flex-1" shadow="never">
                <template #header>
                    <div>
                        <span class="card-title">今日经营摘要</span>
                        <span class="text-tx-secondary text-xs ml-4">
                            更新时间：{{ weddingOverview.time || '--' }}
                        </span>
                    </div>
                </template>

                <div class="summary-grid">
                    <div v-for="item in overviewCards" :key="item.title" class="summary-card">
                        <div class="summary-card__title">{{ item.title }}</div>
                        <div class="summary-card__value">{{ item.value }}</div>
                        <div class="summary-card__desc">{{ item.desc }}</div>
                    </div>
                </div>
            </el-card>
        </div>

        <div class="mb-4">
            <el-card class="!border-none" shadow="never">
                <template #header>
                    <span class="card-title">关键待办</span>
                </template>
                <div class="todo-grid">
                    <div v-for="item in todoCards" :key="item.title" class="todo-card">
                        <div class="todo-card__title">{{ item.title }}</div>
                        <div class="todo-card__value">{{ item.value }}</div>
                        <div class="todo-card__desc">{{ item.desc }}</div>
                    </div>
                </div>
            </el-card>
        </div>

        <div class="function mb-4">
            <el-card class="flex-1 !border-none" shadow="never">
                <template #header>
                    <span>常用功能</span>
                </template>
                <div class="flex flex-wrap">
                    <div
                        v-for="item in workbenchData.menu"
                        :key="item.url"
                        class="md:w-[12.5%] w-1/4 flex flex-col items-center"
                    >
                        <router-link :to="item.url" class="mb-3 flex flex-col items-center quick-link">
                            <image-contain width="40px" height="40px" :src="item?.image" />
                            <div class="mt-2">{{ item.name }}</div>
                        </router-link>
                    </div>
                </div>
            </el-card>
        </div>

        <div class="lg:flex gap-4">
            <el-card class="!border-none mb-4 lg:mb-0 w-full lg:w-2/3" shadow="never">
                <template #header>
                    <span>近 15 日订单趋势</span>
                </template>
                <div>
                    <v-charts
                        ref="visitorChart"
                        style="height: 350px"
                        :option="workbenchData.visitorOption"
                        :autoresize="true"
                    />
                </div>
            </el-card>

            <el-card class="!border-none w-full lg:w-1/3" shadow="never">
                <template #header>
                    <span>近 7 日支付趋势</span>
                </template>
                <div>
                    <v-charts
                        ref="saleChart"
                        style="height: 350px"
                        :option="workbenchData.saleOption"
                        :autoresize="true"
                    />
                </div>
            </el-card>
        </div>
    </div>
</template>

<script lang="ts" setup name="workbench">
import vCharts from 'vue-echarts'

import { getWorkbench } from '@/api/app'
import { getWeddingDashboardOverview } from '@/api/wedding'
import useSettingStore from '@/stores/modules/setting'
import { useComponentRef } from '@/utils/getExposeType'
import { calcColor } from '@/utils/util'

const settingStore = useSettingStore()
const saleChart = useComponentRef(vCharts)
const visitorChart = useComponentRef(vCharts)

const formatAmount = (value: number | string | undefined) => `￥${Number(value || 0).toFixed(2)}`

const workbenchData: any = reactive({
    version: {
        version: '',
        website: '',
        based: '',
        channel: {
            gitee: '',
            website: ''
        }
    },
    menu: [],
    visitorOption: {
        xAxis: {
            type: 'category',
            data: []
        },
        yAxis: {
            type: 'value'
        },
        legend: {
            data: ['订单数']
        },
        tooltip: {
            trigger: 'axis'
        },
        series: [
            {
                name: '订单数',
                data: [],
                type: 'line',
                smooth: true,
                color: settingStore.theme,
                lineStyle: {
                    color: settingStore.theme,
                    width: 2
                },
                areaStyle: {
                    color: {
                        type: 'linear',
                        x: 0,
                        y: 0,
                        x2: 0,
                        y2: 1,
                        colorStops: [
                            {
                                offset: 0,
                                color: settingStore.theme
                            },
                            {
                                offset: 1,
                                color: settingStore.theme
                            }
                        ]
                    },
                    opacity: 0.12
                }
            }
        ]
    },
    saleOption: {
        xAxis: {
            type: 'category',
            data: []
        },
        yAxis: {
            type: 'value',
            name: '单位（元）'
        },
        tooltip: {
            trigger: 'axis'
        },
        series: [
            {
                name: '支付金额',
                data: [],
                type: 'bar',
                showBackground: true,
                backgroundStyle: {
                    color: 'rgba(180, 180, 180, 0.2)',
                    borderRadius: [10, 10, 0, 0]
                },
                barWidth: '40%',
                itemStyle: {
                    borderRadius: [10, 10, 0, 0],
                    color: {
                        type: 'linear',
                        x: 0,
                        y: 0,
                        x2: 0,
                        y2: 1,
                        colorStops: [
                            {
                                offset: 0,
                                color: calcColor(settingStore.theme, 0.7)
                            },
                            {
                                offset: 1,
                                color: settingStore.theme
                            }
                        ]
                    }
                }
            }
        ]
    }
})

const weddingOverview = reactive<any>({
    time: '',
    today: {},
    todo: {},
    order_trend: {
        labels: [],
        values: []
    },
    payment_trend: {
        labels: [],
        values: []
    }
})

const overviewCards = computed(() => [
    {
        title: '今日支付',
        value: formatAmount(weddingOverview.today.today_paid_amount),
        desc: `累计支付 ${formatAmount(weddingOverview.today.total_paid_amount)}`
    },
    {
        title: '今日订单',
        value: `${weddingOverview.today.today_order_count || 0}`,
        desc: `累计订单 ${weddingOverview.today.total_order_count || 0}`
    },
    {
        title: '待服务人员确认',
        value: `${weddingOverview.todo.wait_provider_confirm_count || 0}`,
        desc: '接单前订单仍处于锁档状态'
    },
    {
        title: '待线下凭证审核',
        value: `${weddingOverview.todo.wait_offline_voucher_audit_count || 0}`,
        desc: '等待后台人工核验支付凭证'
    },
    {
        title: '待退款处理',
        value: `${weddingOverview.todo.wait_refund_count || 0}`,
        desc: '退款申请不阻断主流程，但需要平台及时处理'
    },
    {
        title: '待资料变更审核',
        value: `${weddingOverview.todo.wait_profile_change_count || 0}`,
        desc: '资料、证书、作品、套餐统一走管理员审核'
    }
])

const todoCards = computed(() => [
    {
        title: '待改期处理',
        value: weddingOverview.todo.wait_reschedule_count || 0,
        desc: '用户已提交改期申请，等待服务人员或平台处理'
    },
    {
        title: '待评价审核',
        value: weddingOverview.todo.wait_review_audit_count || 0,
        desc: '未审核通过前不会公开展示，也不会纳入经营统计'
    },
    {
        title: '待服务人员确认',
        value: weddingOverview.todo.wait_provider_confirm_count || 0,
        desc: '订单已锁档，等待服务人员确认是否接单'
    },
    {
        title: '待凭证审核',
        value: weddingOverview.todo.wait_offline_voucher_audit_count || 0,
        desc: '线下支付凭证等待后台人工核验'
    },
    {
        title: '待退款处理',
        value: weddingOverview.todo.wait_refund_count || 0,
        desc: '退款结果需回写订单状态，并同步通知用户'
    },
    {
        title: '待资料变更审核',
        value: weddingOverview.todo.wait_profile_change_count || 0,
        desc: '资料中心提交的新版本待管理员审核后才生效'
    },
    {
        title: '待动态审核',
        value: weddingOverview.todo.wait_post_audit_count || 0,
        desc: '动态默认走管理员审核，通过后才对外公开'
    },
    {
        title: '待评论审核',
        value: weddingOverview.todo.wait_comment_audit_count || 0,
        desc: '评论审核遵循 provider_then_admin 或 admin 模式'
    }
])

watch(
    () => settingStore.theme,
    () => {
        updateColor()
    }
)

const getData = () => {
    Promise.all([getWorkbench(), getWeddingDashboardOverview()])
        .then(([workbench, overview]: any) => {
            workbenchData.version = workbench.version
            workbenchData.menu = workbench.menu

            Object.assign(weddingOverview, overview || {})

            workbenchData.visitorOption.xAxis.data = []
            workbenchData.visitorOption.series[0].data = []
            workbenchData.saleOption.xAxis.data = []
            workbenchData.saleOption.series[0].data = []

            ;(overview?.order_trend?.labels || []).forEach((item: any) => {
                workbenchData.visitorOption.xAxis.data.push(item)
            })
            ;(overview?.order_trend?.values || []).forEach((item: any) => {
                workbenchData.visitorOption.series[0].data.push(item)
            })
            ;(overview?.payment_trend?.labels || []).forEach((item: any) => {
                workbenchData.saleOption.xAxis.data.push(item)
            })
            ;(overview?.payment_trend?.values || []).forEach((item: any) => {
                workbenchData.saleOption.series[0].data.push(Number(item || 0))
            })

            updateColor()
        })
        .catch((err: any) => {
            console.log('err', err)
        })
}

const updateColor = () => {
    workbenchData.visitorOption.series[0].color = settingStore.theme
    workbenchData.visitorOption.series[0].lineStyle.color = settingStore.theme
    workbenchData.visitorOption.series[0].areaStyle.color.colorStops = [
        {
            offset: 0,
            color: settingStore.theme
        },
        {
            offset: 1,
            color: settingStore.theme
        }
    ]
    workbenchData.saleOption.series[0].itemStyle.color.colorStops = [
        {
            offset: 0,
            color: calcColor(settingStore.theme, 0.7)
        },
        {
            offset: 1,
            color: settingStore.theme
        }
    ]

    saleChart.value?.clear()
    visitorChart.value?.clear()
    saleChart.value?.setOption(workbenchData.saleOption)
    visitorChart.value?.setOption(workbenchData.visitorOption)
}

onMounted(() => {
    getData()
})
</script>

<style lang="scss" scoped>
.workbench-page {
    background:
        radial-gradient(circle at top left, rgba(219, 39, 119, 0.08), transparent 24%),
        radial-gradient(circle at right bottom, rgba(202, 138, 4, 0.10), transparent 28%),
        linear-gradient(180deg, #fff8fb 0%, #fcfaf7 46%, #f8f4ef 100%);
    min-height: 100%;
}

.hero-card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(255, 249, 251, 0.95));
}

.hero-layout {
    display: flex;
    justify-content: space-between;
    gap: 24px;
    align-items: flex-start;
    flex-wrap: wrap;
}

.hero-eyebrow {
    color: #9d174d;
    font-size: 13px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
}

.hero-title {
    margin-top: 10px;
    color: #1f2937;
    font-size: 32px;
    font-weight: 700;
    line-height: 1.2;
}

.hero-desc {
    margin-top: 14px;
    color: #6b7280;
    font-size: 14px;
    line-height: 1.8;
    max-width: 720px;
}

.hero-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.hero-badge {
    padding: 10px 14px;
    border-radius: 999px;
    background: rgba(219, 39, 119, 0.08);
    color: #9d174d;
    font-size: 13px;
    font-weight: 600;
}

.hero-badge--gold {
    background: rgba(202, 138, 4, 0.12);
    color: #a16207;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
}

.summary-card {
    padding: 18px;
    border-radius: 18px;
    background: linear-gradient(180deg, rgba(255, 250, 251, 0.92), rgba(255, 254, 249, 0.92));
    border: 1px solid rgba(202, 138, 4, 0.14);
}

.summary-card__title {
    color: #6b7280;
    font-size: 13px;
}

.summary-card__value {
    margin-top: 10px;
    color: #111827;
    font-size: 30px;
    font-weight: 700;
    line-height: 1.2;
}

.summary-card__desc {
    margin-top: 10px;
    color: #6b7280;
    font-size: 13px;
    line-height: 1.7;
}

.todo-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
}

.todo-card {
    padding: 18px;
    border-radius: 18px;
    background: linear-gradient(180deg, #fffafb, #fffdfa);
    border: 1px solid rgba(219, 39, 119, 0.10);
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    cursor: pointer;
}

.todo-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(31, 41, 55, 0.08);
    border-color: rgba(219, 39, 119, 0.18);
}

.todo-card__title {
    color: #6b7280;
    font-size: 13px;
}

.todo-card__value {
    margin-top: 10px;
    color: #111827;
    font-size: 34px;
    font-weight: 700;
    line-height: 1.1;
}

.todo-card__desc {
    margin-top: 10px;
    color: #6b7280;
    font-size: 13px;
    line-height: 1.7;
}

.quick-link {
    transition: transform 0.2s ease, opacity 0.2s ease;
    cursor: pointer;
}

.quick-link:hover {
    transform: translateY(-2px);
    opacity: 0.9;
}

@media (max-width: 1279px) {
    .summary-grid,
    .todo-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 767px) {
    .summary-grid,
    .todo-grid {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
