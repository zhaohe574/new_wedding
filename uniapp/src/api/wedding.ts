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

export function applyWeddingOrderRefund(data: Record<string, any>) {
    return request.post({ url: '/wedding/order/refundApply', data }, { isAuth: true })
}

export function getProviderPendingOrderLists(data: Record<string, any>) {
    return request.get({ url: '/wedding/provider/order/pendingLists', data }, { isAuth: true })
}

export function applyWeddingOrderReschedule(data: Record<string, any>) {
    return request.post({ url: '/wedding/order/rescheduleApply', data }, { isAuth: true })
}

export function submitWeddingOrderReview(data: Record<string, any>) {
    return request.post({ url: '/wedding/order/reviewSubmit', data }, { isAuth: true })
}

export function getWeddingDashboardOverview() {
    return request.get({ url: '/wedding/dashboard/overview' }, { isAuth: true })
}

export function getProviderOrderLists(data: Record<string, any>) {
    return request.get({ url: '/wedding/provider/order/lists', data }, { isAuth: true })
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

export function handleProviderOrderReschedule(data: Record<string, any>) {
    return request.post({ url: '/wedding/provider/order/rescheduleHandle', data }, { isAuth: true })
}

export function completeProviderOrderService(data: Record<string, any>) {
    return request.post({ url: '/wedding/provider/order/serviceComplete', data }, { isAuth: true })
}

export function auditProviderOrderReview(data: Record<string, any>) {
    return request.post({ url: '/wedding/provider/order/reviewAudit', data }, { isAuth: true })
}

export function getProviderScheduleMonth(data: Record<string, any>) {
    return request.get({ url: '/wedding/provider/schedule/month', data }, { isAuth: true })
}

export function upsertProviderSchedule(data: Record<string, any>) {
    return request.post({ url: '/wedding/provider/schedule/upsert', data }, { isAuth: true })
}

export function deleteProviderScheduleDates(data: Record<string, any>) {
    return request.post({ url: '/wedding/provider/schedule/delete', data }, { isAuth: true })
}

export function getProviderCenterDetail() {
    return request.get({ url: '/wedding/provider/center/detail' }, { isAuth: true })
}

export function getProviderChangeRequestLists(data: Record<string, any>) {
    return request.get({ url: '/wedding/provider/change-request/lists', data }, { isAuth: true })
}

export function getProviderChangeRequestDetail(data: Record<string, any>) {
    return request.get({ url: '/wedding/provider/change-request/detail', data }, { isAuth: true })
}

export function submitProviderChangeRequest(data: Record<string, any>) {
    return request.post({ url: '/wedding/provider/change-request/submit', data }, { isAuth: true })
}

export function getProviderPostLists(data: Record<string, any>) {
    return request.get({ url: '/wedding/provider/post/lists', data }, { isAuth: true })
}

export function saveProviderPost(data: Record<string, any>) {
    return request.post({ url: '/wedding/provider/post/save', data }, { isAuth: true })
}

export function deleteProviderPost(data: Record<string, any>) {
    return request.post({ url: '/wedding/provider/post/delete', data }, { isAuth: true })
}

export function getProviderCommentPendingLists(data: Record<string, any>) {
    return request.get({ url: '/wedding/provider/comment/pendingLists', data }, { isAuth: true })
}

export function auditProviderComment(data: Record<string, any>) {
    return request.post({ url: '/wedding/provider/comment/audit', data }, { isAuth: true })
}

export function getProviderPublicPostLists(data: Record<string, any>) {
    return request.get({ url: '/wedding/provider/publicPostLists', data }, { isAuth: true })
}

export function createProviderPostComment(data: Record<string, any>) {
    return request.post({ url: '/wedding/provider/post/comment/create', data }, { isAuth: true })
}

export function getWeddingSubscribeTemplates(data?: Record<string, any>) {
    return request.get({ url: '/wedding/notice/subscribeTemplates', data }, { isAuth: true })
}
