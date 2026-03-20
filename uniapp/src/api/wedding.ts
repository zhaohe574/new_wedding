import request from '@/utils/request'

export function getWeddingCategories() {
    return request.get({ url: '/wedding/categories' })
}

export function getWeddingOpenRegionTree() {
    return request.get({ url: '/wedding/openRegionTree' })
}

export function getWeddingProfile() {
    return request.get({ url: '/wedding/profile' }, { isAuth: true })
}

export function saveWeddingProfile(data: Record<string, any>) {
    return request.post({ url: '/wedding/saveProfile', data }, { isAuth: true })
}

export function getWeddingTemplate(data: Record<string, any>) {
    return request.get({ url: '/wedding/template', data })
}

export function getWeddingProviderLists(data: Record<string, any>) {
    return request.get({ url: '/wedding/provider/lists', data }, { isAuth: true })
}

export function getWeddingProviderDetail(data: Record<string, any>) {
    return request.get({ url: '/wedding/provider/detail', data }, { isAuth: true })
}

export function previewWeddingOrder(data: Record<string, any>) {
    return request.post({ url: '/wedding/order/preview', data }, { isAuth: true })
}
