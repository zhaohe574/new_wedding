<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
        <!-- #endif -->
    </page-meta>
    <view class="webview-page">
        <w-page-nav title="网页浏览" />
        <web-view class="webview-page__body" :src="url" />
    </view>
</template>

<script setup lang="ts">
import { onLoad } from '@dcloudio/uni-app'
import { ref } from 'vue'

const url = ref('')

onLoad((options) => {
    url.value = decodeURIComponent(options.url!)
})
</script>

<style scoped>
.webview-page {
    height: 100vh;
    display: flex;
    flex-direction: column;
    background: #ffffff;
}

.webview-page__body {
    flex: 1;
    min-height: 0;
}
</style>
