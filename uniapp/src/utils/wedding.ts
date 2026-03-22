import cache from '@/utils/cache'
import { getWeddingSubscribeTemplates } from '@/api/wedding'

export const WEDDING_ORDER_DRAFT_KEY = 'wedding_order_draft'

export function getSelectedRegion(): Record<string, any> {
    return cache.get('selected_region') || {}
}

export function getSelectedServiceDate(): string {
    return cache.get('selected_service_date') || ''
}

export function hasWeddingTradeSelection(): boolean {
    const region = getSelectedRegion()
    return !!region.district_code && !!getSelectedServiceDate()
}

export function buildWeddingSelectionSummary(): string {
    const region = getSelectedRegion()
    const regionText = [region.province_name, region.city_name, region.district_name].filter(Boolean).join(' / ')
    return [regionText, getSelectedServiceDate()].filter(Boolean).join('，')
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
