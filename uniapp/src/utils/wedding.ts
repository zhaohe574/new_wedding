import cache from '@/utils/cache'
import { getWeddingSubscribeTemplates } from '@/api/wedding'

export const WEDDING_ORDER_DRAFT_KEY = 'wedding_order_draft'
export const BUYER_ORDER_SUBSCRIBE_SCENES = [201, 202, 203, 204, 205, 207, 208, 211]
export const BUYER_RESCHEDULE_SUBSCRIBE_SCENES = [210]
export const BUYER_REFUND_SUBSCRIBE_SCENES = [214, 215]
export const BUYER_REVIEW_SUBSCRIBE_SCENES = [213]
export const WEDDING_TRADE_PRICE_SORT_OPTIONS = ['asc', 'desc'] as const

export type WeddingTradePriceSort = '' | (typeof WEDDING_TRADE_PRICE_SORT_OPTIONS)[number]

export type WeddingTradeQuery = {
    category_id: number
    district_code: string
    province_name: string
    city_name: string
    district_name: string
    service_date: string
    tag_ids: number[]
    keyword: string
    price_sort: WeddingTradePriceSort
}

function normalizeString(value: any): string {
    const text = String(value ?? '').trim()
    if (!text) {
        return ''
    }

    try {
        return decodeURIComponent(text)
    } catch (error) {
        return text
    }
}

function normalizeTagIds(value: any): number[] {
    if (Array.isArray(value)) {
        return Array.from(new Set(value.map((item) => Number(item)).filter((item) => item > 0)))
    }

    const text = normalizeString(value)
    if (!text) {
        return []
    }

    return Array.from(
        new Set(
            text
                .split(/[,，]/)
                .map((item) => Number(item))
                .filter((item) => item > 0)
        )
    )
}

function normalizePriceSort(value: any): WeddingTradePriceSort {
    const sort = normalizeString(value)
    return sort === 'asc' || sort === 'desc' ? sort : ''
}

function encodeQueryValue(value: string): string {
    return encodeURIComponent(value)
}

export function normalizeWeddingTradeQuery(query: Record<string, any> = {}): WeddingTradeQuery {
    return {
        category_id: Number(query.category_id || 0),
        district_code: normalizeString(query.district_code),
        province_name: normalizeString(query.province_name),
        city_name: normalizeString(query.city_name),
        district_name: normalizeString(query.district_name),
        service_date: normalizeString(query.service_date),
        tag_ids: normalizeTagIds(query.tag_ids),
        keyword: normalizeString(query.keyword),
        price_sort: normalizePriceSort(query.price_sort)
    }
}

export function hasRequiredWeddingTradeQuery(query: Record<string, any> = {}): boolean {
    const normalized = normalizeWeddingTradeQuery(query)
    return normalized.category_id > 0 && !!normalized.district_code && !!normalized.service_date
}

export function buildWeddingSelectionSummary(query: Record<string, any> = {}): string {
    const normalized = normalizeWeddingTradeQuery(query)
    const regionText = [normalized.province_name, normalized.city_name, normalized.district_name]
        .filter(Boolean)
        .join(' / ')
    return [regionText, normalized.service_date].filter(Boolean).join('，')
}

export function buildWeddingTradeQueryUrl(path: string, query: Partial<WeddingTradeQuery> = {}): string {
    const normalized = normalizeWeddingTradeQuery(query as Record<string, any>)
    const params: string[] = []

    if (normalized.category_id > 0) {
        params.push(`category_id=${normalized.category_id}`)
    }
    if (normalized.district_code) {
        params.push(`district_code=${encodeQueryValue(normalized.district_code)}`)
    }
    if (normalized.province_name) {
        params.push(`province_name=${encodeQueryValue(normalized.province_name)}`)
    }
    if (normalized.city_name) {
        params.push(`city_name=${encodeQueryValue(normalized.city_name)}`)
    }
    if (normalized.district_name) {
        params.push(`district_name=${encodeQueryValue(normalized.district_name)}`)
    }
    if (normalized.service_date) {
        params.push(`service_date=${encodeQueryValue(normalized.service_date)}`)
    }
    if (normalized.tag_ids.length) {
        params.push(`tag_ids=${normalized.tag_ids.join(',')}`)
    }
    if (normalized.keyword) {
        params.push(`keyword=${encodeQueryValue(normalized.keyword)}`)
    }
    if (normalized.price_sort) {
        params.push(`price_sort=${normalized.price_sort}`)
    }

    if (!params.length) {
        return path
    }

    return `${path}${path.includes('?') ? '&' : '?'}${params.join('&')}`
}

export function buildWeddingProviderListParams(query: Partial<WeddingTradeQuery> = {}): Record<string, any> {
    const normalized = normalizeWeddingTradeQuery(query as Record<string, any>)
    return {
        category_id: normalized.category_id,
        district_code: normalized.district_code,
        service_date: normalized.service_date,
        tag_ids: normalized.tag_ids.join(','),
        keyword: normalized.keyword,
        price_sort: normalized.price_sort
    }
}

export function getWeddingOrderDraft(): Record<string, any> {
    const draft = cache.get(WEDDING_ORDER_DRAFT_KEY) || {}
    return {
        category_id: Number(draft.category_id || 0),
        provider_id: Number(draft.provider_id || 0),
        package_id: Number(draft.package_id || 0),
        payment_type: Number(draft.payment_type || 1),
        template_form_data: normalizeTemplateFormData(draft.template_form_data)
    }
}

export function setWeddingOrderDraft(draft: Record<string, any>) {
    cache.set(WEDDING_ORDER_DRAFT_KEY, {
        category_id: Number(draft.category_id || 0),
        provider_id: Number(draft.provider_id || 0),
        package_id: Number(draft.package_id || 0),
        payment_type: Number(draft.payment_type || 1),
        template_form_data: normalizeTemplateFormData(draft.template_form_data)
    })
}

export function patchWeddingOrderDraft(patch: Record<string, any>) {
    const draft = getWeddingOrderDraft()
    setWeddingOrderDraft({
        ...draft,
        ...patch,
        template_form_data:
            patch.template_form_data === undefined
                ? draft.template_form_data
                : normalizeTemplateFormData(patch.template_form_data)
    })
}

export function clearWeddingOrderDraft() {
    cache.remove(WEDDING_ORDER_DRAFT_KEY)
}

export function mergeWeddingSubscribeScenes(...sceneGroups: Array<number[]>) {
    const merged: number[] = []
    sceneGroups.forEach((group) => {
        const safeGroup = group || []
        safeGroup.forEach((item) => {
            merged.push(Number(item))
        })
    })

    return Array.from(new Set(merged.filter((item) => item > 0)))
}

export async function requestWeddingSubscribeMessages(sceneIds: number[] = []) {
    // #ifndef MP-WEIXIN
    return
    // #endif

    // #ifdef MP-WEIXIN
    const ids = Array.from(new Set((sceneIds || []).map((item) => Number(item)).filter((item) => item > 0)))
    if (!ids.length) {
        return
    }

    try {
        const data = await getWeddingSubscribeTemplates({
            scene_ids: ids
        })
        const templateIds = Array.from(
            new Set((data?.lists || []).map((item: any) => String(item.template_id || '')).filter(Boolean))
        )
        if (!templateIds.length) {
            return
        }
        await uni.requestSubscribeMessage({
            tmplIds: templateIds
        })
    } catch (error) {
        console.log('requestWeddingSubscribeMessages error', error)
    }
    // #endif
}

function normalizeTemplateFormData(value: any): Record<string, any> {
    if (!value || Array.isArray(value) || typeof value !== 'object') {
        return {}
    }

    return value
}
