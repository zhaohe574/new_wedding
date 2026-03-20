import request from '@/utils/request'

export function getServiceBusinessConfig() {
    return request.get({ url: '/setting.service_business/getConfig' })
}

export function setServiceBusinessConfig(params: Record<string, any>) {
    return request.post({ url: '/setting.service_business/setConfig', params })
}
