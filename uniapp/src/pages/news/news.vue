<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar
            :front-color="$theme.navColor"
            background-color="transparent"
        />
        <!-- #endif -->
    </page-meta>
    <w-page-nav variant="tab" :show-back="false" />
    <view class="news-page w-page-shell w-page-shell--tab">
        <view class="w-card-soft news-head">
            <view class="w-section-caption">Wedding Moments</view>
            <view class="news-head__title">婚礼动态</view>
        </view>

        <navigator class="w-card news-search" url="/pages/search/search">
            <u-search
                placeholder="搜索婚礼灵感与筹备内容"
                disabled
                :show-action="false"
            ></u-search>
        </navigator>

        <view v-if="!tabList.length" class="w-state-card">当前暂无可浏览动态。</view>

        <tabs
            v-else
            class="tabs-shell"
            :current="current"
            @change="handleChange"
            height="80"
            bar-width="60"
            :barStyle="{ bottom: '0' }"
        >
            <tab v-for="(item, i) in tabList" :key="i" :name="item.name">
                <view class="news-list-shell">
                    <news-list :cid="item.id" :i="i" :index="current"></news-list>
                </view>
            </tab>
        </tabs>
        <tabbar />
    </view>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import NewsList from './component/news-list.vue'
import { getArticleCate } from '@/api/news'

const tabList = ref<any[]>([])
const current = ref(0)

const handleChange = (index: number) => {
    current.value = Number(index)
}

const getData = async () => {
    const data = await getArticleCate()
    tabList.value = [{ name: '全部', id: '' }].concat(data || [])
}

onLoad(() => {
    getData()
})
</script>

<style lang="scss">
.news-page {
    display: flex;
    flex-direction: column;
    padding-top: calc(var(--w-page-nav-height) + 24rpx);
}

.news-head__title {
    margin-top: 10rpx;
    color: #111827;
    font-size: 40rpx;
    font-weight: 600;
}

.news-search {
    margin-top: 20rpx;
    padding: 14rpx 16rpx;
}

.tabs-shell {
    flex: 1;
    min-height: 0;
    margin-top: 20rpx;
    padding: 0 8rpx 12rpx;
    border-radius: 28rpx;
    background: rgba(255, 255, 255, 0.76);
    border: 1rpx solid rgba(219, 39, 119, 0.08);
    overflow: hidden;
}

.news-list-shell {
    height: 100%;
    padding-top: 20rpx;
}
</style>
