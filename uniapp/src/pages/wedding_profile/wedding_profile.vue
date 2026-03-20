<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>
    <view class="wedding-profile-page min-h-screen px-[24rpx] py-[24rpx] box-border">
        <view class="hero-card">
            <view class="hero-card__eyebrow">Wedding Profile</view>
            <view class="hero-card__title">婚礼基础档案</view>
            <view class="hero-card__desc">
                这里沉淀婚礼日期、宴会地区、预算和联系人。后续进入下单链路时，会优先复用这里的默认值。
            </view>
        </view>

        <view class="panel-card mt-[24rpx]">
            <view class="panel-card__title">基础信息</view>
            <view class="form-grid">
                <view class="form-item">
                    <view class="form-item__label">婚礼日期</view>
                    <picker mode="date" :value="formData.wedding_date" @change="handleWeddingDateChange">
                        <view class="picker-value">{{ formData.wedding_date || '请选择婚礼日期' }}</view>
                    </picker>
                </view>

                <view class="form-item">
                    <view class="form-item__label">宴会省份</view>
                    <picker mode="selector" :range="provinceOptions" range-key="name" :value="provinceIndex" @change="handleProvinceChange">
                        <view class="picker-value">{{ currentProvinceName || '请选择省份' }}</view>
                    </picker>
                </view>

                <view class="form-item">
                    <view class="form-item__label">宴会城市</view>
                    <picker mode="selector" :range="cityOptions" range-key="name" :value="cityIndex" @change="handleCityChange">
                        <view class="picker-value">{{ currentCityName || '请选择城市' }}</view>
                    </picker>
                </view>

                <view class="form-item">
                    <view class="form-item__label">宴会区县</view>
                    <picker mode="selector" :range="districtOptions" range-key="name" :value="districtIndex" @change="handleDistrictChange">
                        <view class="picker-value">{{ currentDistrictName || '请选择区县' }}</view>
                    </picker>
                </view>

                <view class="form-item">
                    <view class="form-item__label">宴会场地</view>
                    <input v-model="formData.banquet_hotel" class="form-input" placeholder="请输入宴会场地" />
                </view>

                <view class="form-item">
                    <view class="form-item__label">桌数规模</view>
                    <input v-model="formData.table_count" class="form-input" type="number" placeholder="例如：20" />
                </view>

                <view class="form-item">
                    <view class="form-item__label">预算下限</view>
                    <input v-model="formData.budget_min" class="form-input" type="digit" placeholder="请输入预算下限" />
                </view>

                <view class="form-item">
                    <view class="form-item__label">预算上限</view>
                    <input v-model="formData.budget_max" class="form-input" type="digit" placeholder="请输入预算上限" />
                </view>

                <view class="form-item">
                    <view class="form-item__label">联系人</view>
                    <input v-model="formData.contact_name" class="form-input" placeholder="请输入联系人" />
                </view>

                <view class="form-item">
                    <view class="form-item__label">联系方式</view>
                    <input v-model="formData.contact_mobile" class="form-input" type="number" placeholder="请输入联系方式" />
                </view>
            </view>

            <view class="form-item mt-[20rpx]">
                <view class="form-item__label">风格偏好</view>
                <textarea
                    v-model="stylePreferenceText"
                    class="form-textarea"
                    placeholder="可填写多项，用逗号或换行分隔，例如：韩式、轻奢、电影感"
                />
            </view>

            <view class="form-item mt-[20rpx]">
                <view class="form-item__label">备注</view>
                <textarea v-model="formData.remark" class="form-textarea" placeholder="补充备注信息" />
            </view>
        </view>

        <button class="save-btn mt-[24rpx]" @click="handleSave">保存婚礼档案</button>
    </view>
</template>

<script setup lang="ts">
import { getWeddingProfile, saveWeddingProfile } from '@/api/wedding'
import areas from '@/uni_modules/vk-uview-ui/libs/address/areas.json'
import citys from '@/uni_modules/vk-uview-ui/libs/address/citys.json'
import provinces from '@/uni_modules/vk-uview-ui/libs/address/provinces.json'
import { computed, reactive, ref } from 'vue'

type RegionItem = {
    code: string
    name: string
}

const provinceOptions = provinces as RegionItem[]
const formData = reactive({
    wedding_date: '',
    province_code: '',
    city_code: '',
    district_code: '',
    banquet_hotel: '',
    table_count: '',
    budget_min: '',
    budget_max: '',
    contact_name: '',
    contact_mobile: '',
    remark: ''
})
const stylePreferenceText = ref('')

const provinceIndex = computed(() => {
    const index = provinceOptions.findIndex((item) => item.code === formData.province_code)
    return index >= 0 ? index : 0
})

const cityOptions = computed(() => {
    const index = provinceOptions.findIndex((item) => item.code === formData.province_code)
    return index >= 0 ? ((citys as RegionItem[][])[index] || []) : []
})

const cityIndex = computed(() => {
    const index = cityOptions.value.findIndex((item) => item.code === formData.city_code)
    return index >= 0 ? index : 0
})

const districtOptions = computed(() => {
    const provinceIdx = provinceOptions.findIndex((item) => item.code === formData.province_code)
    const cityIdx = cityOptions.value.findIndex((item) => item.code === formData.city_code)
    return provinceIdx >= 0 && cityIdx >= 0 ? (((areas as RegionItem[][][])[provinceIdx] || [])[cityIdx] || []) : []
})

const districtIndex = computed(() => {
    const index = districtOptions.value.findIndex((item) => item.code === formData.district_code)
    return index >= 0 ? index : 0
})

const currentProvinceName = computed(() => provinceOptions.find((item) => item.code === formData.province_code)?.name || '')
const currentCityName = computed(() => cityOptions.value.find((item) => item.code === formData.city_code)?.name || '')
const currentDistrictName = computed(() => districtOptions.value.find((item) => item.code === formData.district_code)?.name || '')

const handleWeddingDateChange = (event: any) => {
    formData.wedding_date = event.detail.value
}

const handleProvinceChange = (event: any) => {
    const index = Number(event.detail.value)
    const province = provinceOptions[index]
    formData.province_code = province?.code || ''
    formData.city_code = ''
    formData.district_code = ''
}

const handleCityChange = (event: any) => {
    const index = Number(event.detail.value)
    const city = cityOptions.value[index]
    formData.city_code = city?.code || ''
    formData.district_code = ''
}

const handleDistrictChange = (event: any) => {
    const index = Number(event.detail.value)
    const district = districtOptions.value[index]
    formData.district_code = district?.code || ''
}

const fillProfile = (profile: Record<string, any>) => {
    Object.assign(formData, {
        wedding_date: profile.wedding_date || '',
        province_code: profile.province_code || '',
        city_code: profile.city_code || '',
        district_code: profile.district_code || '',
        banquet_hotel: profile.banquet_hotel || '',
        table_count: profile.table_count ? String(profile.table_count) : '',
        budget_min: profile.budget_min ? String(profile.budget_min) : '',
        budget_max: profile.budget_max ? String(profile.budget_max) : '',
        contact_name: profile.contact_name || '',
        contact_mobile: profile.contact_mobile || '',
        remark: profile.remark || ''
    })
    stylePreferenceText.value = (profile.style_preference || []).join('，')
}

const getData = async () => {
    fillProfile(await getWeddingProfile())
}

const handleSave = async () => {
    if (formData.district_code === '') {
        uni.showToast({ title: '请选择宴会区县', icon: 'none' })
        return
    }

    const stylePreference = stylePreferenceText.value
        .split(/[\n,，]/)
        .map((item) => item.trim())
        .filter(Boolean)

    await saveWeddingProfile({
        wedding_date: formData.wedding_date,
        district_code: formData.district_code,
        banquet_hotel: formData.banquet_hotel,
        table_count: Number(formData.table_count || 0),
        budget_min: Number(formData.budget_min || 0),
        budget_max: Number(formData.budget_max || 0),
        style_preference: stylePreference,
        contact_name: formData.contact_name,
        contact_mobile: formData.contact_mobile,
        remark: formData.remark
    })
    uni.showToast({ title: '保存成功', icon: 'success' })
    getData()
}

getData()
</script>

<style lang="scss" scoped>
.wedding-profile-page {
    background:
        radial-gradient(circle at top right, rgba(219, 39, 119, 0.10), transparent 26%),
        linear-gradient(180deg, #fffafc, #f8f3ef 44%, #f7f3f0);
}

.hero-card,
.panel-card {
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

.panel-card {
    padding: 28rpx;
}

.panel-card__title {
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

.form-item__label {
    margin-bottom: 10rpx;
    color: #6b7280;
    font-size: 24rpx;
}

.picker-value,
.form-input,
.form-textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 22rpx 24rpx;
    border-radius: 22rpx;
    background: linear-gradient(180deg, #fff9fb, #fffdf9);
    border: 1rpx solid rgba(219, 39, 119, 0.12);
    color: #111827;
    font-size: 26rpx;
}

.form-textarea {
    min-height: 180rpx;
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
