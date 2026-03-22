<template>
    <view class="user-info mb-[0rpx]">
        <view class="flex items-center justify-between px-[50rpx] pb-[50rpx] pt-[40rpx]">
            <view
                v-if="isLogin"
                class="flex items-center"
                @click="navigateTo('/pages/user_data/user_data')"
            >
                <u-avatar :src="user.avatar" :size="120"></u-avatar>
                <view class="text-white ml-[20rpx]">
                    <view class="text-2xl">{{ user.nickname }}</view>
                    <view class="text-xs mt-[18rpx]" @click.stop="copy(user.account)">
                        账号：{{ user.account }}
                    </view>
                    <view
                        v-if="user.can_enter_provider_center || user.can_view_dashboard"
                        class="ability-tags mt-[18rpx]"
                    >
                        <view v-if="user.can_enter_provider_center" class="ability-tag">
                            服务人员中心
                        </view>
                        <view v-if="user.can_view_dashboard" class="ability-tag ability-tag--gold">
                            驾驶舱权限
                        </view>
                    </view>
                </view>
            </view>
            <navigator v-else class="flex items-center" hover-class="none" url="/pages/login/login">
                <u-avatar src="/static/images/user/default_avatar.png" :size="120"></u-avatar>
                <view class="text-white text-3xl ml-[20rpx]">未登录</view>
            </navigator>
            <navigator v-if="isLogin" hover-class="none" url="/pages/user_set/user_set">
                <u-icon name="setting" color="#fff" :size="48"></u-icon>
            </navigator>
        </view>
    </view>
</template>
<script lang="ts" setup>
import { useCopy } from '@/hooks/useCopy'

defineProps({
    pageMeta: {
        type: Object,
        default: () => ({})
    },
    content: {
        type: Object,
        default: () => ({})
    },
    styles: {
        type: Object,
        default: () => ({})
    },
    user: {
        type: Object,
        default: () => ({})
    },
    isLogin: {
        type: Boolean
    }
})
const { copy } = useCopy()

const navigateTo = (url: string) => {
    uni.navigateTo({
        url
    })
}
</script>

<style lang="scss" scoped>
.user-info {
    background: url(../../../static/images/user/my_topbg.png),
        linear-gradient(135deg, #db2777, #f472b6 56%, #f5c46b);
    background-repeat: no-repeat;
    background-position: bottom;
    background-size: 100%;
}

.ability-tags {
    display: flex;
    gap: 12rpx;
    flex-wrap: wrap;
}

.ability-tag {
    padding: 8rpx 18rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.18);
    font-size: 20rpx;
    line-height: 1.4;
}

.ability-tag--gold {
    background: rgba(202, 138, 4, 0.22);
}
</style>
