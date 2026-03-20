<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <view class="wedding-region-page min-h-screen px-[24rpx] py-[24rpx] box-border">
        <view class="hero-card">
            <view class="hero-card__eyebrow">Region & Date</view>
            <view class="hero-card__title">地区与档期前置</view>
            <view class="hero-card__desc">
                当前页只负责确定开放县区与服务日期，并把选择结果写成统一前置状态，后续列表与详情会直接复用这组条件。
            </view>
        </view>

        <view class="panel-card mt-[24rpx]">
            <view class="panel-card__title">选择地区</view>
            <view v-if="!provinceOptions.length" class="empty-text">当前还没有可选开放城市，请先在后台维护开放城市。</view>
            <view v-else class="form-grid">
                <view class="form-item">
                    <view class="form-item__label">开放省份</view>
                    <picker mode="selector" :range="provinceOptions" range-key="name" :value="provinceIndex" @change="handleProvinceChange">
                        <view class="picker-value">{{ currentProvinceName || '请选择省份' }}</view>
                    </picker>
                </view>
                <view class="form-item">
                    <view class="form-item__label">开放城市</view>
                    <picker mode="selector" :range="cityOptions" range-key="name" :value="cityIndex" @change="handleCityChange">
                        <view class="picker-value">{{ currentCityName || '请选择城市' }}</view>
                    </picker>
                </view>
                <view class="form-item form-item--full">
                    <view class="form-item__label">开放区县</view>
                    <picker mode="selector" :range="districtOptions" range-key="name" :value="districtIndex" @change="handleDistrictChange">
                        <view class="picker-value">{{ currentDistrictName || '请选择区县' }}</view>
                    </picker>
                </view>
            </view>
        </view>

        <view class="panel-card mt-[24rpx]">
            <view class="panel-card__title">选择日期</view>
            <view class="form-item mt-[20rpx]">
                <view class="form-item__label">服务日期</view>
                <picker mode="date" :value="serviceDate" @change="handleDateChange">
                    <view class="picker-value">{{ serviceDate || '请选择服务日期' }}</view>
                </picker>
            </view>
        </view>

        <view class="summary-card mt-[24rpx]">
            <view class="summary-card__label">当前结果</view>
            <view class="summary-card__value">{{ summaryText || '还未完成地区和日期选择' }}</view>
        </view>

        <button class="save-btn mt-[24rpx]" @click="handleSave">保存前置条件</button>
    </view>
</template>

<script setup lang="ts">
import { getWeddingOpenRegionTree } from '@/api/wedding'
import cache from '@/utils/cache'
import { clearWeddingOrderDraft } from '@/utils/wedding'
import { computed, ref } from 'vue'

type DistrictItem = {
    code: string
    name: string
}

type CityItem = {
    code: string
    name: string
    children: DistrictItem[]
}

type ProvinceItem = {
    code: string
    name: string
    children: CityItem[]
}

const provinceOptions = ref<ProvinceItem[]>([])
const provinceCode = ref('')
const cityCode = ref('')
const districtCode = ref('')
const serviceDate = ref('')

const provinceIndex = computed(() => {
    const index = provinceOptions.value.findIndex((item) => item.code === provinceCode.value)
    return index >= 0 ? index : 0
})

const cityOptions = computed(() => provinceOptions.value.find((item) => item.code === provinceCode.value)?.children || [])
const cityIndex = computed(() => {
    const index = cityOptions.value.findIndex((item) => item.code === cityCode.value)
    return index >= 0 ? index : 0
})

const districtOptions = computed(() => cityOptions.value.find((item) => item.code === cityCode.value)?.children || [])
const districtIndex = computed(() => {
    const index = districtOptions.value.findIndex((item) => item.code === districtCode.value)
    return index >= 0 ? index : 0
})

const currentProvinceName = computed(() => provinceOptions.value.find((item) => item.code === provinceCode.value)?.name || '')
const currentCityName = computed(() => cityOptions.value.find((item) => item.code === cityCode.value)?.name || '')
const currentDistrictName = computed(() => districtOptions.value.find((item) => item.code === districtCode.value)?.name || '')

const summaryText = computed(() => {
    const regionText = [currentProvinceName.value, currentCityName.value, currentDistrictName.value].filter(Boolean).join(' / ')
    return [regionText, serviceDate.value].filter(Boolean).join('，')
})

const handleProvinceChange = (event: any) => {
    const index = Number(event.detail.value)
    const province = provinceOptions.value[index]
    provinceCode.value = province?.code || ''
    cityCode.value = ''
    districtCode.value = ''
}

const handleCityChange = (event: any) => {
    const index = Number(event.detail.value)
    const city = cityOptions.value[index]
    cityCode.value = city?.code || ''
    districtCode.value = ''
}

const handleDistrictChange = (event: any) => {
    const index = Number(event.detail.value)
    const district = districtOptions.value[index]
    districtCode.value = district?.code || ''
}

const handleDateChange = (event: any) => {
    serviceDate.value = event.detail.value
}

const restoreSelection = () => {
    const region = cache.get('selected_region') || {}
    provinceCode.value = region.province_code || ''
    cityCode.value = region.city_code || ''
    districtCode.value = region.district_code || ''
    serviceDate.value = cache.get('selected_service_date') || ''
}

const getData = async () => {
    const data = await getWeddingOpenRegionTree()
    provinceOptions.value = data.tree || []
    restoreSelection()
}

const handleSave = () => {
    if (!districtCode.value) {
        uni.showToast({ title: '请选择开放区县', icon: 'none' })
        return
    }

    if (!serviceDate.value) {
        uni.showToast({ title: '请选择服务日期', icon: 'none' })
        return
    }

    cache.set('selected_region', {
        province_code: provinceCode.value,
        province_name: currentProvinceName.value,
        city_code: cityCode.value,
        city_name: currentCityName.value,
        district_code: districtCode.value,
        district_name: currentDistrictName.value
    })
    cache.set('selected_service_date', serviceDate.value)
    clearWeddingOrderDraft()
    uni.showToast({ title: '已保存，准备进入列表', icon: 'success' })
    setTimeout(() => {
        uni.redirectTo({
            url: '/pages/wedding_provider_list/wedding_provider_list'
        })
    }, 180)
}

getData()
</script>

<style lang="scss" scoped>
.wedding-region-page {
    background:
        radial-gradient(circle at top right, rgba(219, 39, 119, 0.10), transparent 26%),
        linear-gradient(180deg, #fffafc, #f8f3ef 44%, #f7f3f0);
}

.hero-card,
.panel-card,
.summary-card {
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    border-radius: 28rpx;
    box-shadow: 0 18rpx 48rpx rgba(31, 41, 55, 0.06);
}

.hero-card {
    padding: 36rpx 32rpx;
}

.hero-card__eyebrow {
    color: #9d174d;
    font-size: 22rpx;
    letter-spacing: 4rpx;
}

.hero-card__title {
    margin-top: 16rpx;
    color: #1f2937;
    font-size: 44rpx;
    font-weight: 600;
}

.hero-card__desc {
    margin-top: 16rpx;
    color: #6b7280;
    font-size: 26rpx;
    line-height: 1.8;
}

.panel-card,
.summary-card {
    padding: 28rpx;
}

.panel-card__title,
.summary-card__label {
    color: #111827;
    font-size: 30rpx;
    font-weight: 600;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18rpx;
    margin-top: 24rpx;
}

.form-item--full {
    grid-column: 1 / -1;
}

.form-item__label {
    margin-bottom: 10rpx;
    color: #6b7280;
    font-size: 24rpx;
}

.picker-value {
    width: 100%;
    box-sizing: border-box;
    padding: 22rpx 24rpx;
    border-radius: 22rpx;
    background: linear-gradient(180deg, #fff9fb, #fffdf9);
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    color: #111827;
    font-size: 26rpx;
}

.summary-card__value {
    margin-top: 14rpx;
    color: #6b7280;
    font-size: 26rpx;
    line-height: 1.8;
}

.empty-text {
    margin-top: 20rpx;
    color: #9ca3af;
    font-size: 24rpx;
    line-height: 1.8;
}

.save-btn {
    width: 100%;
    border-radius: 999rpx;
    background: linear-gradient(135deg, #db2777, #ca8a04);
    color: #ffffff;
    font-size: 28rpx;
    font-weight: 600;
    box-shadow: 0 20rpx 40rpx rgba(219, 39, 119, 0.18);
}
</style>
