<template>
    <div class="shop-pages h-[530px]">
        <div class="flex flex-wrap link-list">
            <div
                class="link-item border border-br px-5 py-[5px] rounded-[3px] cursor-pointer mr-[10px] mb-[10px]"
                v-for="(item, index) in linkList"
                :class="{
                    'border-primary text-primary':
                        modelValue.path == item.path && modelValue.name == item.name
                }"
                :key="index"
                @click="handleSelect(item)"
            >
                {{ item.name }}
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'

import { type Link, LinkTypeEnum } from '.'

defineProps({
    modelValue: {
        type: Object as PropType<Link>,
        default: () => ({})
    }
})
const emit = defineEmits<{
    (event: 'update:modelValue', value: Link): void
}>()

const linkList = ref([
    {
        path: '/pages/index/index',
        // name: '',
        name: '首页',
        type: LinkTypeEnum.SHOP_PAGES,
        canTab: true
    },
    {
        path: '/pages/news/news',
        name: '动态',
        type: LinkTypeEnum.SHOP_PAGES,
        canTab: true
    },
    {
        path: '/pages/user/user',
        name: '我的',
        type: LinkTypeEnum.SHOP_PAGES,
        canTab: true
    },
    {
        path: '/pages/wedding_profile/wedding_profile',
        name: '婚礼基础档案',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/wedding_region/wedding_region',
        name: '地区与档期',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/wedding_order_list/wedding_order_list',
        name: '我的婚庆订单',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/notice_center/notice_center',
        name: '通知中心',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/provider_center/provider_center',
        name: '服务人员中心',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/dashboard/dashboard',
        name: '经营驾驶舱',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/collection/collection',
        name: '我的收藏',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/customer_service/customer_service',
        name: '联系客服',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/user_set/user_set',
        name: '个人设置',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/as_us/as_us',
        name: '关于我们',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/user_data/user_data',
        name: '个人资料',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/agreement/agreement',
        name: '隐私政策',
        query: {
            type: 'privacy'
        },
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/agreement/agreement',
        name: '服务协议',
        query: {
            type: 'service'
        },
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/search/search',
        name: '搜索',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/packages/pages/user_wallet/user_wallet',
        name: '我的钱包',
        type: LinkTypeEnum.SHOP_PAGES
    }
])

const handleSelect = (value: Link) => {
    emit('update:modelValue', value)
}
</script>
