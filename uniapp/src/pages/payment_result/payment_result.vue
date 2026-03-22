<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
        <!-- #endif -->
    </page-meta>
    <w-page-nav />
    <!-- 页面状态 -->
    <page-status :status="status">
        <template #error>
            <u-empty text="订单不存在" mode="order"></u-empty>
        </template>
        <template #default>
            <view class="payment-result w-page-shell">
                <view class="result">
                    <view class="flex flex-col items-center my-[40rpx]">
                        <!-- 支付状态图片 -->
                        <u-image
                            class="status-image"
                            :src="paymentStatus['image']"
                            width="100"
                            height="100"
                            shape="circle"
                        />
                        <!-- 支付状态文字 -->
                        <text class="text-2xl font-medium mt-[20rpx]">{{
                                paymentStatus['text']
                            }}
                        </text>
                        <view class="text-3xl font-medium mt-[20rpx]">
                            ¥ {{ orderInfo.order.order_amount }}
                        </view>
                    </view>

                    <!-- 支付信息 -->
                    <view class="result-info">
                        <view class="result-info__item">
                            <text>订单编号</text>
                            <text>{{ orderInfo.order.order_sn }}</text>
                        </view>
                        <view class="result-info__item">
                            <text>付款时间</text>
                            <text>{{ orderInfo.order.pay_time }}</text>
                        </view>
                        <view class="result-info__item">
                            <text>支付方式</text>
                            <template v-if="orderInfo.pay_status">
                                <text>{{ orderInfo.order.pay_way || '-' }}</text>
                            </template>
                            <template v-else>
                                <text>未支付</text>
                            </template>
                        </view>
                    </view>
                </view>
                <view class="payment-result__actions">
                    <view class="mb-[20rpx]">
                        <u-button
                            v-if="pageOptions.from == 'recharge' || pageOptions.from == 'service_order'"
                            type="primary"
                            shape="circle"
                            hover-class="none"
                            @click="goOrder"
                        >
                            {{ pageOptions.from == 'recharge' ? '继续充值' : '查看订单' }}
                        </u-button>
                    </view>
                    <view class="mb-[20rpx]">
                        <u-button
                            type="primary"
                            plain
                            shape="circle"
                            hover-class="none"
                            @click="goHome"
                        >
                            返回首页
                        </u-button>
                    </view>
                </view>
            </view>
        </template>
    </page-status>
</template>

<script lang="ts" setup>
import {getPayResult} from '@/api/pay'
import {PageStatusEnum} from '@/enums/appEnums'
import {onLoad} from '@dcloudio/uni-app'
import {computed, reactive, ref} from 'vue'
import {useRouter} from "uniapp-router-next";

const router = useRouter()

const mapStatus = {
    succeed: {
        text: '支付成功',
        image: '/static/images/payment/icon_succeed.png'
    },
    waiting: {
        text: '等待支付',
        image: '/static/images/payment/icon_waiting.png'
    }
}
const status = ref(PageStatusEnum['LOADING'])
const pageOptions = ref({
    id: '',
    from: ''
})
const orderInfo = reactive<any>({
    order: {}
})
const paymentStatus = computed(() => {
    const status = !!orderInfo.pay_status
    return mapStatus[status ? 'succeed' : 'waiting']
})

const initPageData = () => {
    return new Promise((resolve, reject) => {
        getPayResult({
            order_id: pageOptions.value.id,
            from: pageOptions.value.from
        })
            .then((data) => {
                Object.assign(orderInfo, data)
                resolve(data)
            })
            .catch((err) => {
                reject(err)
            })
    })
}

const goHome = () => {
    router.reLaunch('/pages/index/index')
}

const goOrder = () => {
    switch (pageOptions.value.from) {
        case 'recharge':
            router.navigateBack()
            break
        case 'service_order':
            uni.redirectTo({
                url: `/pages/wedding_order_detail/wedding_order_detail?order_id=${pageOptions.value.id}`
            })
            break
    }
}

onLoad(async (options: any) => {
    try {
        if (!options.id) throw new Error('订单不存在')
        pageOptions.value = options
        await initPageData()
        status.value = PageStatusEnum['NORMAL']
    } catch (err) {
        console.log(err)
        status.value = PageStatusEnum['ERROR']
    }
})
</script>

<style lang="scss" scoped>
.result {
    padding: 28rpx;
    border-radius: 28rpx;
    background: rgba(255, 255, 255, 0.94);
    border: 1rpx solid rgba(219, 39, 119, 0.1);
    box-shadow: 0 18rpx 48rpx rgba(31, 41, 55, 0.06);
}

.payment-result__actions {
    margin-top: 40rpx;
}

.result-info {
    margin-top: 24rpx;

    .result-info__item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20rpx;
        gap: 16rpx;
        color: #4b5563;
        font-size: 24rpx;
    }
}
</style>
