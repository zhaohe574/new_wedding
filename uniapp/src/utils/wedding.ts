import cache from '@/utils/cache'

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
        template_form_data: normalizeTemplateFormData(draft.template_form_data)
    }
}

export function setWeddingOrderDraft(draft: Record<string, any>) {
    cache.set(WEDDING_ORDER_DRAFT_KEY, {
        category_id: Number(draft.category_id || 0),
        provider_id: Number(draft.provider_id || 0),
        package_id: Number(draft.package_id || 0),
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

function normalizeTemplateFormData(value: any): Record<string, any> {
    if (!value || Array.isArray(value) || typeof value !== 'object') {
        return {}
    }

    return value
}
