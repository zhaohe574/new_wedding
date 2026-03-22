<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
        <!-- #endif -->
    </page-meta>
    <view class="agreement-page">
        <w-page-nav :title="navTitle" />
        <view class="agreement-page__content">
            <u-parse :html="agreementContent"></u-parse>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getPolicy } from '@/api/app'

const agreementType = ref('') // 协议类型
const agreementContent = ref('') // 协议内容
const navTitle = ref('协议')

const getData = async (type: string) => {
    const res = await getPolicy({ type })
    agreementContent.value = res.content
    navTitle.value = String(res.title || '协议')
    uni.setNavigationBarTitle({
        title: String(res.title)
    })
}

onLoad((options: any) => {
    if (options.type) {
        agreementType.value = options.type
        getData(agreementType.value)
    }
})
</script>

<style lang="scss" scoped>
.agreement-page__content {
    padding: 30rpx;
    background: #ffffff;
}
</style>
