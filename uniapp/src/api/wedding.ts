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

export function createWeddingOrder(data: Record<string, any>) {
    return request.post({ url: '/wedding/order/create', data }, { isAuth: true })
}

export function getWeddingOrderLists(data: Record<string, any>) {
    return request.get({ url: '/wedding/order/lists', data }, { isAuth: true })
}

export function getWeddingOrderDetail(data: Record<string, any>) {
    return request.get({ url: '/wedding/order/detail', data }, { isAuth: true })
}

export function cancelWeddingOrder(data: Record<string, any>) {
    return request.post({ url: '/wedding/order/cancel', data }, { isAuth: true })
}

export function submitWeddingOfflineVoucher(data: Record<string, any>) {
    return request.post({ url: '/wedding/order/offlineVoucher', data }, { isAuth: true })
}

export function getProviderPendingOrderLists(data: Record<string, any>) {
    return request.get({ url: '/wedding/provider/order/pendingLists', data }, { isAuth: true })
}

export function getProviderOrderDetail(data: Record<string, any>) {
    return request.get({ url: '/wedding/provider/order/detail', data }, { isAuth: true })
}

export function acceptProviderOrder(data: Record<string, any>) {
    return request.post({ url: '/wedding/provider/order/accept', data }, { isAuth: true })
}

export function rejectProviderOrder(data: Record<string, any>) {
    return request.post({ url: '/wedding/provider/order/reject', data }, { isAuth: true })
}
