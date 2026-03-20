<template>
    <popup ref="popupRef" title="婚礼档案详情" width="720px" :confirm-button-text="false" :cancel-button-text="false" @close="void 0">
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-item__label">婚礼日期</span>
                <span class="detail-item__value">{{ detailData.wedding_date || '-' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-item__label">宴会地区</span>
                <span class="detail-item__value">{{ regionText }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-item__label">宴会场地</span>
                <span class="detail-item__value">{{ detailData.banquet_hotel || '-' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-item__label">桌数规模</span>
                <span class="detail-item__value">{{ detailData.table_count || 0 }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-item__label">预算区间</span>
                <span class="detail-item__value">{{ budgetText }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-item__label">联系人</span>
                <span class="detail-item__value">{{ detailData.contact_name || '-' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-item__label">联系方式</span>
                <span class="detail-item__value">{{ detailData.contact_mobile || '-' }}</span>
            </div>
            <div class="detail-item detail-item--full">
                <span class="detail-item__label">风格偏好</span>
                <div class="tag-list">
                    <el-tag v-for="item in detailData.style_preference || []" :key="item" type="danger" effect="plain">
                        {{ item }}
                    </el-tag>
                    <span v-if="!(detailData.style_preference || []).length" class="detail-item__value">未填写</span>
                </div>
            </div>
            <div class="detail-item detail-item--full">
                <span class="detail-item__label">备注</span>
                <span class="detail-item__value">{{ detailData.remark || '-' }}</span>
            </div>
        </div>
    </popup>
</template>

<script lang="ts" setup name="weddingProfileDetail">
import { detailWeddingProfile } from '@/api/wedding'
import Popup from '@/components/popup/index.vue'

const popupRef = shallowRef<InstanceType<typeof Popup>>()
const detailData = ref<Record<string, any>>({})

const regionText = computed(() => {
    return [detailData.value.province_name, detailData.value.city_name, detailData.value.district_name].filter(Boolean).join(' / ') || '-'
})

const budgetText = computed(() => {
    const min = Number(detailData.value.budget_min || 0)
    const max = Number(detailData.value.budget_max || 0)
    if (!min && !max) {
        return '-'
    }

    return `${min || 0} - ${max || 0}`
})

const open = async (id: number) => {
    detailData.value = await detailWeddingProfile({ id })
    popupRef.value?.open()
}

defineExpose({
    open
})
</script>

<style lang="scss" scoped>
.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.detail-item {
    padding: 18px;
    border-radius: 18px;
    background: linear-gradient(180deg, #fff9fb, #fffdf9);
    border: 1px solid rgba(219, 39, 119, 0.12);
}

.detail-item--full {
    grid-column: 1 / -1;
}

.detail-item__label {
    display: block;
    color: #9ca3af;
    font-size: 12px;
    margin-bottom: 8px;
}

.detail-item__value {
    color: #111827;
    line-height: 1.8;
}

.tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
</style>
