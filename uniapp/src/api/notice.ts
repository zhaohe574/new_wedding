import request from '@/utils/request'

export function getNoticeLists(data: Record<string, any>) {
    return request.get({ url: '/notice/lists', data }, { isAuth: true })
}

export function readNotice(data: Record<string, any>) {
    return request.post({ url: '/notice/read', data }, { isAuth: true })
}

export function readAllNotice() {
    return request.post({ url: '/notice/readAll', data: {} }, { isAuth: true })
}
