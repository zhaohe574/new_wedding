<template>
    <div class="wedding-profile-page">
        <el-card class="hero-card !border-none" shadow="never">
            <div class="hero-grid">
                <div>
                    <div class="hero-eyebrow">Wedding Profile</div>
                    <h1 class="hero-title">婚礼档案管理</h1>
                    <p class="hero-desc">
                        这里查看用户在小程序维护的婚礼基础档案，用于后续订单预填、地区选择和转化判断。后台仅查看，不直接改写用户档案。
                    </p>
                </div>
                <div class="hero-side">
                    <div class="hero-stat">
                        <span class="hero-stat__label">维护端</span>
                        <span class="hero-stat__value">uniapp 用户侧</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat__label">后台职责</span>
                        <span class="hero-stat__value">查看与筛选</span>
                    </div>
                </div>
            </div>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-form :model="queryParams" :inline="true" class="mb-[-16px]">
                <el-form-item class="w-[280px]" label="关键字">
                    <el-input
                        v-model="queryParams.keyword"
                        placeholder="用户昵称、账号、城市、联系人"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[220px]" label="宴会城市">
                    <el-input v-model="queryParams.city_name" placeholder="请输入城市名称" clearable @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="婚礼日期">
                    <el-date-picker v-model="queryParams.wedding_date" type="date" value-format="YYYY-MM-DD" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table v-loading="pager.loading" :data="pager.lists" size="large">
                <el-table-column label="用户" min-width="220">
                    <template #default="{ row }">
                        <div>{{ row.user_nickname || `用户 #${row.user_id}` }}</div>
                        <div class="table-sub">{{ row.user_account || row.user_mobile || '-' }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="婚礼日期" prop="wedding_date" min-width="120" />
                <el-table-column label="宴会城市" min-width="180">
                    <template #default="{ row }">
                        <div>{{ row.city_name || '-' }}</div>
                        <div class="table-sub">{{ row.district_name || '未选区县' }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="联系人" min-width="160">
                    <template #default="{ row }">
                        <div>{{ row.contact_name || '-' }}</div>
                        <div class="table-sub">{{ row.contact_mobile || '-' }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="90" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="openDetail(row.id)">详情</el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <wedding-profile-detail ref="detailRef" />
    </div>
</template>

<script lang="ts" setup name="weddingProfileIndex">
import { getWeddingProfileLists } from '@/api/wedding'
import { usePaging } from '@/hooks/usePaging'

import WeddingProfileDetail from './detail.vue'

const queryParams = reactive({
    keyword: '',
    city_name: '',
    wedding_date: ''
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getWeddingProfileLists,
    params: queryParams
})

const detailRef = shallowRef<InstanceType<typeof WeddingProfileDetail>>()

const openDetail = async (id: number) => {
    await nextTick()
    detailRef.value?.open(id)
}

getLists()
</script>

<style lang="scss" scoped>
.wedding-profile-page {
    padding-bottom: 36px;
}

.hero-card {
    background:
        radial-gradient(circle at top left, rgba(219, 39, 119, 0.16), transparent 34%),
        radial-gradient(circle at right bottom, rgba(202, 138, 4, 0.14), transparent 28%),
        linear-gradient(135deg, #fffdf8, #fff7fb 50%, #fffef8);
}

.hero-grid {
    display: grid;
    gap: 24px;
    grid-template-columns: minmax(0, 1.5fr) minmax(280px, 0.8fr);
    align-items: end;
}

.hero-eyebrow {
    color: #9d174d;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.hero-title {
    margin: 10px 0 14px;
    color: #1f2937;
    font-size: 30px;
    line-height: 1.2;
}

.hero-desc {
    max-width: 760px;
    color: #6b7280;
    line-height: 1.8;
}

.hero-side {
    display: grid;
    gap: 14px;
}

.hero-stat {
    border: 1px solid rgba(219, 39, 119, 0.12);
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.88);
    padding: 18px 20px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
}

.hero-stat__label {
    display: block;
    color: #9ca3af;
    font-size: 12px;
    margin-bottom: 8px;
}

.hero-stat__value {
    color: #111827;
    font-size: 18px;
    font-weight: 600;
}

.table-sub {
    color: #9ca3af;
    font-size: 12px;
    line-height: 1.6;
}
</style>
