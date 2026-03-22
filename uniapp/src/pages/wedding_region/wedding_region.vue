<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
        <!-- #endif -->
    </page-meta>
    <w-page-nav />
    <view class="wedding-region-page">
        <view class="query-hero">
            <view class="query-hero__search">
                <view class="query-hero__search-icon-wrap">
                    <view class="query-hero__search-icon"></view>
                </view>
                <view class="query-hero__search-main">
                    <view class="query-hero__eyebrow">Wedding Match</view>
                    <view class="query-hero__search-title">精准筛选服务档期</view>
                    <view class="query-hero__search-desc">按日期、地区、风格和预算快速匹配服务人员</view>
                </view>
            </view>
        </view>

        <view class="query-panel">
            <picker mode="date" :value="serviceDate" :start="minServiceDate" @change="handleDateChange">
                <view class="filter-row">
                    <view class="filter-row__label">婚礼日期</view>
                    <view class="filter-row__content">
                        <view class="filter-row__value" :class="{ 'filter-row__value--placeholder': !serviceDate }">
                            {{ serviceDate || '请输入日期' }}
                        </view>
                        <view class="filter-row__arrow">›</view>
                    </view>
                </view>
            </picker>

            <picker
                v-if="provinceOptions.length"
                mode="multiSelector"
                :range="regionPickerRange"
                :value="regionPickerIndex"
                @change="handleRegionChange"
                @columnchange="handleRegionColumnChange"
            >
                <view class="filter-row">
                    <view class="filter-row__label">服务地区</view>
                    <view class="filter-row__content">
                        <view class="filter-row__value" :class="{ 'filter-row__value--placeholder': !regionDisplayText }">
                            {{ regionDisplayText || '请选择地区' }}
                        </view>
                        <view class="filter-row__arrow">›</view>
                    </view>
                </view>
            </picker>
            <view v-else class="filter-row filter-row--disabled">
                <view class="filter-row__label">服务地区</view>
                <view class="filter-row__value filter-row__value--placeholder">暂无开放地区</view>
            </view>

            <view class="filter-row" @click="openPopup('category')">
                <view class="filter-row__label">服务分类</view>
                <view class="filter-row__content">
                    <view class="filter-row__value" :class="{ 'filter-row__value--placeholder': !selectedCategoryName }">
                        {{ selectedCategoryName || '请选择分类' }}
                    </view>
                    <view class="filter-row__arrow">›</view>
                </view>
            </view>

            <view class="filter-row" @click="openPopup('style')">
                <view class="filter-row__label">风格筛选</view>
                <view class="filter-row__content">
                    <view class="filter-row__value" :class="{ 'filter-row__value--placeholder': !selectedTagSummary }">
                        {{ selectedTagSummary || '请选择风格' }}
                    </view>
                    <view class="filter-row__arrow">›</view>
                </view>
            </view>

            <view class="filter-row" @click="openPopup('sort')">
                <view class="filter-row__label">价格排序</view>
                <view class="filter-row__content">
                    <view class="filter-row__value">{{ priceSortText }}</view>
                    <view class="filter-row__arrow">›</view>
                </view>
            </view>

            <view class="filter-row filter-row--input">
                <view class="filter-row__label">精确搜索</view>
                <view class="filter-row__content">
                    <input
                        v-model="keyword"
                        class="filter-row__input"
                        maxlength="30"
                        confirm-type="search"
                        placeholder="姓名/关键字检索"
                        placeholder-class="filter-row__input-placeholder"
                    />
                </view>
            </view>
        </view>

        <view class="query-footer">
            <button class="query-footer__btn query-footer__btn--reset" @click="handleReset">重置</button>
            <button class="query-footer__btn query-footer__btn--submit" @click="handleSubmit">筛选</button>
        </view>

        <u-popup v-model="popupVisible" mode="bottom" border-radius="24" safe-area-inset-bottom>
            <view class="filter-popup">
                <view class="filter-popup__head">
                    <view class="filter-popup__title">{{ popupTitle }}</view>
                    <view class="filter-popup__close" @click="handleClosePopup">关闭</view>
                </view>

                <template v-if="activePopupType === 'category'">
                    <view class="popup-chip-group">
                        <view
                            v-for="item in categoryOptions"
                            :key="item.id"
                            class="popup-chip"
                            :class="{ 'popup-chip--active': tempCategoryId === item.id }"
                            @click="tempCategoryId = item.id"
                        >
                            {{ item.name }}
                        </view>
                    </view>
                </template>

                <template v-else-if="activePopupType === 'style'">
                    <view v-if="!tagOptions.length" class="popup-empty">暂无可用风格标签</view>
                    <view v-else class="popup-chip-group">
                        <view
                            v-for="item in tagOptions"
                            :key="item.id"
                            class="popup-chip"
                            :class="{ 'popup-chip--active': tempTagIds.includes(item.id) }"
                            @click="handleTempTagToggle(item.id)"
                        >
                            {{ item.name }}
                        </view>
                    </view>
                </template>

                <template v-else-if="activePopupType === 'sort'">
                    <view class="popup-option-list">
                        <view
                            v-for="item in priceSortOptions"
                            :key="item.value || 'default'"
                            class="popup-option"
                            :class="{ 'popup-option--active': tempPriceSort === item.value }"
                            @click="tempPriceSort = item.value"
                        >
                            <view class="popup-option__label">{{ item.label }}</view>
                            <view class="popup-option__check">{{ tempPriceSort === item.value ? '已选' : '' }}</view>
                        </view>
                    </view>
                </template>

                <view class="filter-popup__footer">
                    <button class="filter-popup__btn filter-popup__btn--cancel" @click="handleClosePopup">取消</button>
                    <button class="filter-popup__btn filter-popup__btn--confirm" @click="handleConfirmPopup">确定</button>
                </view>
            </view>
        </u-popup>
    </view>
</template>

<script setup lang="ts">
import { getWeddingCategories, getWeddingOpenRegionTree, getWeddingTags } from '@/api/wedding'
import {
    buildWeddingTradeQueryUrl,
    clearWeddingOrderDraft,
    normalizeWeddingTradeQuery,
    patchWeddingOrderDraft,
    type WeddingTradePriceSort
} from '@/utils/wedding'
import { onLoad } from '@dcloudio/uni-app'
import { computed, ref } from 'vue'

type PopupType = '' | 'category' | 'style' | 'sort'

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

type OptionItem = {
    id: number
    name: string
}

const provinceOptions = ref<ProvinceItem[]>([])
const categoryOptions = ref<OptionItem[]>([])
const tagOptions = ref<OptionItem[]>([])
const provinceCode = ref('')
const cityCode = ref('')
const districtCode = ref('')
const serviceDate = ref('')
const activeCategoryId = ref(0)
const selectedTagIds = ref<number[]>([])
const keyword = ref('')
const priceSort = ref<WeddingTradePriceSort>('')

const popupVisible = ref(false)
const activePopupType = ref<PopupType>('')
const tempCategoryId = ref(0)
const tempTagIds = ref<number[]>([])
const tempPriceSort = ref<WeddingTradePriceSort>('')

const priceSortOptions: Array<{ label: string; value: WeddingTradePriceSort }> = [
    { label: '默认排序', value: '' },
    { label: '价格升序', value: 'asc' },
    { label: '价格降序', value: 'desc' }
]

const minServiceDate = computed(() => {
    const date = new Date()
    const year = date.getFullYear()
    const month = `${date.getMonth() + 1}`.padStart(2, '0')
    const day = `${date.getDate()}`.padStart(2, '0')
    return `${year}-${month}-${day}`
})

const currentProvinceName = computed(() => provinceOptions.value.find((item) => item.code === provinceCode.value)?.name || '')
const currentCityOptions = computed(() => provinceOptions.value.find((item) => item.code === provinceCode.value)?.children || [])
const currentCityName = computed(() => currentCityOptions.value.find((item) => item.code === cityCode.value)?.name || '')
const currentDistrictOptions = computed(() => currentCityOptions.value.find((item) => item.code === cityCode.value)?.children || [])
const currentDistrictName = computed(() => currentDistrictOptions.value.find((item) => item.code === districtCode.value)?.name || '')

const regionProvince = computed(() => {
    if (!provinceOptions.value.length) {
        return null
    }
    return provinceOptions.value.find((item) => item.code === provinceCode.value) || provinceOptions.value[0]
})
const regionCityOptions = computed(() => regionProvince.value?.children || [])
const regionCity = computed(() => {
    if (!regionCityOptions.value.length) {
        return null
    }
    return regionCityOptions.value.find((item) => item.code === cityCode.value) || regionCityOptions.value[0]
})
const regionDistrictOptions = computed(() => regionCity.value?.children || [])

const regionProvinceIndex = computed(() => {
    const code = regionProvince.value?.code || ''
    const index = provinceOptions.value.findIndex((item) => item.code === code)
    return index >= 0 ? index : 0
})
const regionCityIndex = computed(() => {
    const code = regionCity.value?.code || ''
    const index = regionCityOptions.value.findIndex((item) => item.code === code)
    return index >= 0 ? index : 0
})
const regionDistrictIndex = computed(() => {
    const index = regionDistrictOptions.value.findIndex((item) => item.code === districtCode.value)
    return index >= 0 ? index : 0
})
const regionPickerRange = computed(() => [
    provinceOptions.value.map((item) => item.name),
    regionCityOptions.value.map((item) => item.name),
    regionDistrictOptions.value.map((item) => item.name)
])
const regionPickerIndex = computed(() => [regionProvinceIndex.value, regionCityIndex.value, regionDistrictIndex.value])

const popupTitle = computed(() => {
    const map: Record<PopupType, string> = {
        '': '',
        category: '选择服务分类',
        style: '选择风格筛选',
        sort: '选择排序方式'
    }
    return map[activePopupType.value]
})

const regionDisplayText = computed(() => [currentProvinceName.value, currentCityName.value, currentDistrictName.value].filter(Boolean).join(' / '))
const selectedCategoryName = computed(() => categoryOptions.value.find((item) => item.id === activeCategoryId.value)?.name || '')
const selectedTagSummary = computed(() => {
    if (!selectedTagIds.value.length) {
        return ''
    }
    const names = tagOptions.value.filter((item) => selectedTagIds.value.includes(item.id)).map((item) => item.name)
    return names.join('、')
})
const priceSortText = computed(() => priceSortOptions.find((item) => item.value === priceSort.value)?.label || '默认排序')

const handleDateChange = (event: any) => {
    serviceDate.value = event.detail.value
}

const handleRegionColumnChange = (event: any) => {
    const column = Number(event?.detail?.column)
    const value = Number(event?.detail?.value)

    if (column === 0) {
        const province = provinceOptions.value[value]
        provinceCode.value = province?.code || ''
        const firstCity = (province?.children || [])[0]
        cityCode.value = firstCity?.code || ''
        const firstDistrict = (firstCity?.children || [])[0]
        districtCode.value = firstDistrict?.code || ''
        return
    }

    if (column === 1) {
        const city = regionCityOptions.value[value]
        cityCode.value = city?.code || ''
        const firstDistrict = (city?.children || [])[0]
        districtCode.value = firstDistrict?.code || ''
        return
    }

    if (column === 2) {
        const district = regionDistrictOptions.value[value]
        districtCode.value = district?.code || ''
    }
}

const handleRegionChange = (event: any) => {
    const values = (event?.detail?.value || []).map((item: any) => Number(item))
    const province = provinceOptions.value[values[0]]
    provinceCode.value = province?.code || ''

    const cityOptions = province?.children || []
    const city = cityOptions[values[1]] || cityOptions[0]
    cityCode.value = city?.code || ''

    const districtOptions = city?.children || []
    const district = districtOptions[values[2]] || districtOptions[0]
    districtCode.value = district?.code || ''
}

const handleTempTagToggle = (tagId: number) => {
    if (tempTagIds.value.includes(tagId)) {
        tempTagIds.value = tempTagIds.value.filter((item) => item !== tagId)
        return
    }
    tempTagIds.value = [...tempTagIds.value, tagId]
}

const resetPopupDraft = (type: PopupType) => {
    if (type === 'category') {
        tempCategoryId.value = activeCategoryId.value || categoryOptions.value[0]?.id || 0
        return
    }
    if (type === 'style') {
        tempTagIds.value = [...selectedTagIds.value]
        return
    }
    if (type === 'sort') {
        tempPriceSort.value = priceSort.value
    }
}

const openPopup = (type: PopupType) => {
    activePopupType.value = type
    resetPopupDraft(type)
    popupVisible.value = true
}

const handleClosePopup = () => {
    popupVisible.value = false
    activePopupType.value = ''
}

const handleConfirmPopup = () => {
    if (activePopupType.value === 'category') {
        activeCategoryId.value = tempCategoryId.value || categoryOptions.value[0]?.id || 0
    }
    if (activePopupType.value === 'style') {
        selectedTagIds.value = [...tempTagIds.value]
    }
    if (activePopupType.value === 'sort') {
        priceSort.value = tempPriceSort.value
    }
    handleClosePopup()
}

const findRegionByDistrict = (targetDistrictCode: string) => {
    if (!targetDistrictCode) {
        return null
    }
    for (const province of provinceOptions.value) {
        for (const city of province.children || []) {
            const district = (city.children || []).find((item) => item.code === targetDistrictCode)
            if (district) {
                return {
                    provinceCode: province.code,
                    cityCode: city.code,
                    districtCode: district.code
                }
            }
        }
    }
    return null
}

const applyInitialQuery = (rawQuery: Record<string, any>) => {
    const tradeQuery = normalizeWeddingTradeQuery(rawQuery)
    activeCategoryId.value = categoryOptions.value.find((item) => item.id === tradeQuery.category_id)?.id || categoryOptions.value[0]?.id || 0
    selectedTagIds.value = tradeQuery.tag_ids.filter((tagId) => tagOptions.value.some((item) => item.id === tagId))
    keyword.value = tradeQuery.keyword
    priceSort.value = tradeQuery.price_sort
    serviceDate.value = tradeQuery.service_date

    const matchedRegion = findRegionByDistrict(tradeQuery.district_code)
    provinceCode.value = matchedRegion?.provinceCode || ''
    cityCode.value = matchedRegion?.cityCode || ''
    districtCode.value = matchedRegion?.districtCode || ''
}

const handleReset = () => {
    provinceCode.value = ''
    cityCode.value = ''
    districtCode.value = ''
    serviceDate.value = ''
    selectedTagIds.value = []
    keyword.value = ''
    priceSort.value = ''
    activeCategoryId.value = categoryOptions.value[0]?.id || 0
}

const handleSubmit = () => {
    if (!activeCategoryId.value) {
        uni.showToast({ title: '请选择服务分类', icon: 'none' })
        return
    }
    if (!districtCode.value) {
        uni.showToast({ title: '请选择服务地区', icon: 'none' })
        return
    }
    if (!serviceDate.value) {
        uni.showToast({ title: '请选择服务日期', icon: 'none' })
        return
    }

    clearWeddingOrderDraft()
    patchWeddingOrderDraft({
        category_id: activeCategoryId.value
    })

    uni.redirectTo({
        url: buildWeddingTradeQueryUrl('/pages/wedding_provider_list/wedding_provider_list', {
            category_id: activeCategoryId.value,
            district_code: districtCode.value,
            province_name: currentProvinceName.value,
            city_name: currentCityName.value,
            district_name: currentDistrictName.value,
            service_date: serviceDate.value,
            tag_ids: selectedTagIds.value,
            keyword: keyword.value,
            price_sort: priceSort.value
        })
    })
}

const loadData = async (rawQuery: Record<string, any>) => {
    const [regionData, categories, tags] = await Promise.all([
        getWeddingOpenRegionTree(),
        getWeddingCategories(),
        getWeddingTags()
    ])

    provinceOptions.value = regionData?.tree || []
    categoryOptions.value = categories || []
    tagOptions.value = tags || []
    applyInitialQuery(rawQuery)
}

onLoad(async (options) => {
    await loadData((options || {}) as Record<string, any>)
})
</script>

<style lang="scss" scoped>
.wedding-region-page {
    min-height: 100vh;
    background: var(--w-bg-page);
    padding-bottom: calc(var(--window-bottom, 0px) + env(safe-area-inset-bottom) + 176rpx);
}

.query-hero {
    position: relative;
    margin: 0;
    border-radius: 0 0 32rpx 32rpx;
    overflow: hidden;
    background:
        radial-gradient(circle at 88% 12%, rgba(255, 255, 255, 0.34), transparent 36%),
        linear-gradient(140deg, #9d174d 0%, #be123c 46%, #b45309 100%);
    box-shadow: 0 20rpx 52rpx rgba(157, 23, 77, 0.24);
}

.query-hero::after {
    content: '';
    position: absolute;
    inset: 0;
    background:
        repeating-linear-gradient(
            -42deg,
            rgba(255, 255, 255, 0.08) 0,
            rgba(255, 255, 255, 0.08) 2rpx,
            transparent 2rpx,
            transparent 18rpx
        );
    opacity: 0.5;
}

.query-hero__search {
    position: relative;
    z-index: 1;
    padding: 28rpx 26rpx 36rpx;
    display: flex;
    align-items: flex-start;
    gap: 18rpx;
}

.query-hero__search-icon-wrap {
    width: 88rpx;
    height: 88rpx;
    border-radius: 24rpx;
    background: rgba(255, 255, 255, 0.2);
    border: 1rpx solid rgba(255, 255, 255, 0.34);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: inset 0 0 0 1rpx rgba(255, 255, 255, 0.08);
}

.query-hero__search-icon {
    width: 34rpx;
    height: 34rpx;
    border-radius: 50%;
    border: 4rpx solid #ffffff;
    position: relative;
}

.query-hero__search-icon::after {
    content: '';
    position: absolute;
    right: -9rpx;
    bottom: -9rpx;
    width: 13rpx;
    height: 4rpx;
    border-radius: 999rpx;
    background: #ffffff;
    transform: rotate(45deg);
}

.query-hero__search-main {
    min-width: 0;
}

.query-hero__eyebrow {
    color: rgba(255, 255, 255, 0.84);
    font-size: 22rpx;
    letter-spacing: 3rpx;
}

.query-hero__search-title {
    margin-top: 8rpx;
    color: #ffffff;
    font-size: 44rpx;
    font-weight: 600;
    line-height: 1.24;
}

.query-hero__search-desc {
    margin-top: 12rpx;
    color: rgba(255, 255, 255, 0.88);
    font-size: 24rpx;
    line-height: 1.7;
}

.query-panel {
    margin: 20rpx 24rpx 0;
    padding: 10rpx 26rpx;
    background: var(--w-bg-card);
    border: 1rpx solid var(--w-border-soft);
    border-radius: 28rpx;
    box-shadow: var(--w-shadow);
}

.filter-row {
    min-height: 108rpx;
    border-bottom: 1rpx solid rgba(219, 39, 119, 0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.query-panel .filter-row:last-child {
    border-bottom: none;
}

.filter-row--disabled {
    opacity: 0.72;
}

.filter-row__label {
    flex-shrink: 0;
    color: #4b5563;
    font-size: 27rpx;
    font-weight: 500;
}

.filter-row__content {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12rpx;
    min-width: 0;
}

.filter-row__value {
    color: #111827;
    font-size: 28rpx;
    line-height: 1.5;
    text-align: right;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.filter-row__value--placeholder {
    color: #9ca3af;
}

.filter-row__arrow {
    color: #9ca3af;
    font-size: 30rpx;
    line-height: 1;
}

.filter-row--input .filter-row__content {
    justify-content: flex-start;
}

.filter-row__input {
    width: 100%;
    color: #111827;
    font-size: 28rpx;
    text-align: left;
    min-height: 58rpx;
    line-height: 1.5;
}

.filter-row__input-placeholder {
    color: #9ca3af;
}

.query-footer {
    position: fixed;
    left: 24rpx;
    right: 24rpx;
    bottom: calc(var(--window-bottom, 0px) + env(safe-area-inset-bottom) + 16rpx);
    padding: 12rpx;
    border-radius: 26rpx;
    background: rgba(255, 255, 255, 0.88);
    border: 1rpx solid var(--w-border-soft);
    box-shadow: 0 20rpx 40rpx rgba(31, 41, 55, 0.07);
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14rpx;
    z-index: 88;
    backdrop-filter: blur(10rpx);
}

.query-footer__btn {
    margin: 0;
    height: 92rpx;
    border-radius: 999rpx;
    font-size: 28rpx;
    font-weight: 600;
    line-height: 92rpx;
}

.query-footer__btn--reset {
    background: rgba(255, 255, 255, 0.95);
    border: 1rpx solid var(--w-border-soft);
    color: #6b7280;
}

.query-footer__btn--submit {
    background: linear-gradient(135deg, #db2777, #ca8a04);
    color: #ffffff;
    border: 1rpx solid transparent;
    box-shadow: 0 14rpx 28rpx rgba(219, 39, 119, 0.22);
}

.filter-popup {
    background: #ffffff;
    border-radius: 30rpx 30rpx 0 0;
    padding: 30rpx 24rpx calc(30rpx + env(safe-area-inset-bottom));
}

.filter-popup__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 26rpx;
}

.filter-popup__title {
    color: #111827;
    font-size: 34rpx;
    font-weight: 600;
}

.filter-popup__close {
    min-width: 100rpx;
    text-align: center;
    padding: 10rpx 0;
    border-radius: 999rpx;
    border: 1rpx solid var(--w-border-soft);
    background: #ffffff;
    color: #6b7280;
    font-size: 24rpx;
}

.popup-chip-group {
    display: flex;
    flex-wrap: wrap;
    gap: 14rpx;
}

.popup-chip {
    min-height: 66rpx;
    padding: 0 26rpx;
    border-radius: 999rpx;
    border: 1rpx solid var(--w-border-soft);
    color: #6b7280;
    background: rgba(255, 255, 255, 0.9);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 24rpx;
}

.popup-chip--active {
    color: #ffffff;
    border-color: transparent;
    background: linear-gradient(135deg, #db2777, #ca8a04);
}

.popup-option-list {
    display: grid;
    gap: 12rpx;
}

.popup-option {
    min-height: 84rpx;
    border: 1rpx solid var(--w-border-soft);
    border-radius: 18rpx;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20rpx;
}

.popup-option--active {
    border-color: rgba(219, 39, 119, 0.32);
    background: rgba(253, 242, 248, 0.62);
}

.popup-option__label {
    color: #111827;
    font-size: 26rpx;
}

.popup-option__check {
    min-width: 56rpx;
    text-align: right;
    color: #be123c;
    font-size: 22rpx;
}

.popup-empty {
    color: #9ca3af;
    font-size: 24rpx;
    line-height: 1.7;
}

.filter-popup__footer {
    margin-top: 22rpx;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14rpx;
}

.filter-popup__btn {
    margin: 0;
    height: 82rpx;
    border-radius: 999rpx;
    font-size: 28rpx;
    font-weight: 600;
    line-height: 82rpx;
}

.filter-popup__btn--cancel {
    border: 1rpx solid var(--w-border-soft);
    color: #6b7280;
    background: #ffffff;
}

.filter-popup__btn--confirm {
    border: 1rpx solid transparent;
    color: #ffffff;
    background: linear-gradient(135deg, #db2777, #ca8a04);
}

@media screen and (max-width: 360px) {
    .query-hero__search-title {
        font-size: 40rpx;
    }

    .query-panel {
        margin-left: 20rpx;
        margin-right: 20rpx;
    }

    .query-footer {
        left: 20rpx;
        right: 20rpx;
    }
}
</style>
