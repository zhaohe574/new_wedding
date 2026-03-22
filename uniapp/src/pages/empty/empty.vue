<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
        <!-- #endif -->
    </page-meta>
    <view class="empty-page">
        <w-page-nav title="提示" :show-back="false" />
    </view>
</template>

<script setup lang="ts"></script>

<style></style>
