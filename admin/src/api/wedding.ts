import request from '@/utils/request'

export function getServiceCategoryLists(params?: any) {
    return request.get({ url: '/wedding.service_category/lists', params })
}

export function addServiceCategory(params: any) {
    return request.post({ url: '/wedding.service_category/add', params })
}

export function editServiceCategory(params: any) {
    return request.post({ url: '/wedding.service_category/edit', params })
}

export function deleteServiceCategory(params: any) {
    return request.post({ url: '/wedding.service_category/delete', params })
}

export function detailServiceCategory(params: any) {
    return request.get({ url: '/wedding.service_category/detail', params })
}

export function updateServiceCategoryStatus(params: any) {
    return request.post({ url: '/wedding.service_category/updateStatus', params })
}

export function getServiceTagLists(params?: any) {
    return request.get({ url: '/wedding.service_tag/lists', params })
}

export function addServiceTag(params: any) {
    return request.post({ url: '/wedding.service_tag/add', params })
}

export function editServiceTag(params: any) {
    return request.post({ url: '/wedding.service_tag/edit', params })
}

export function deleteServiceTag(params: any) {
    return request.post({ url: '/wedding.service_tag/delete', params })
}

export function detailServiceTag(params: any) {
    return request.get({ url: '/wedding.service_tag/detail', params })
}

export function updateServiceTagStatus(params: any) {
    return request.post({ url: '/wedding.service_tag/updateStatus', params })
}

export function getServiceProviderLists(params?: any) {
    return request.get({ url: '/wedding.service_provider/lists', params })
}

export function addServiceProvider(params: any) {
    return request.post({ url: '/wedding.service_provider/add', params })
}

export function editServiceProvider(params: any) {
    return request.post({ url: '/wedding.service_provider/edit', params })
}

export function deleteServiceProvider(params: any) {
    return request.post({ url: '/wedding.service_provider/delete', params })
}

export function detailServiceProvider(params: any) {
    return request.get({ url: '/wedding.service_provider/detail', params })
}

export function updateServiceProviderStatus(params: any) {
    return request.post({ url: '/wedding.service_provider/updateStatus', params })
}

export function getServiceProviderUserOptions(params?: any) {
    return request.get({ url: '/wedding.service_provider/userOptions', params })
}

export function getServiceProviderCategoryOptions() {
    return request.get({ url: '/wedding.service_provider/categoryOptions' })
}

export function getServiceProviderTagOptions() {
    return request.get({ url: '/wedding.service_provider/tagOptions' })
}

export function getServiceOpenCityLists(params?: any) {
    return request.get({ url: '/wedding.service_open_city/lists', params })
}

export function addServiceOpenCity(params: any) {
    return request.post({ url: '/wedding.service_open_city/add', params })
}

export function editServiceOpenCity(params: any) {
    return request.post({ url: '/wedding.service_open_city/edit', params })
}

export function deleteServiceOpenCity(params: any) {
    return request.post({ url: '/wedding.service_open_city/delete', params })
}

export function detailServiceOpenCity(params: any) {
    return request.get({ url: '/wedding.service_open_city/detail', params })
}

export function updateServiceOpenCityStatus(params: any) {
    return request.post({ url: '/wedding.service_open_city/updateStatus', params })
}

export function getServiceOpenCityOptions() {
    return request.get({ url: '/wedding.service_open_city/cityOptions' })
}

export function getProviderPackageLists(params?: any) {
    return request.get({ url: '/wedding.provider_package/lists', params })
}

export function addProviderPackage(params: any) {
    return request.post({ url: '/wedding.provider_package/add', params })
}

export function editProviderPackage(params: any) {
    return request.post({ url: '/wedding.provider_package/edit', params })
}

export function deleteProviderPackage(params: any) {
    return request.post({ url: '/wedding.provider_package/delete', params })
}

export function detailProviderPackage(params: any) {
    return request.get({ url: '/wedding.provider_package/detail', params })
}

export function updateProviderPackageStatus(params: any) {
    return request.post({ url: '/wedding.provider_package/updateStatus', params })
}

export function getProviderPackageProviderOptions() {
    return request.get({ url: '/wedding.provider_package/providerOptions' })
}

export function getProviderPackageOpenRegionOptions() {
    return request.get({ url: '/wedding.provider_package/openRegionOptions' })
}

export function getProviderScheduleLists(params?: any) {
    return request.get({ url: '/wedding.provider_schedule/lists', params })
}

export function addProviderSchedule(params: any) {
    return request.post({ url: '/wedding.provider_schedule/add', params })
}

export function editProviderSchedule(params: any) {
    return request.post({ url: '/wedding.provider_schedule/edit', params })
}

export function deleteProviderSchedule(params: any) {
    return request.post({ url: '/wedding.provider_schedule/delete', params })
}

export function detailProviderSchedule(params: any) {
    return request.get({ url: '/wedding.provider_schedule/detail', params })
}

export function getProviderScheduleProviderOptions() {
    return request.get({ url: '/wedding.provider_schedule/providerOptions' })
}

export function getWeddingProfileLists(params?: any) {
    return request.get({ url: '/wedding.wedding_profile/lists', params })
}

export function detailWeddingProfile(params: any) {
    return request.get({ url: '/wedding.wedding_profile/detail', params })
}

export function getServiceContentTemplateLists(params?: any) {
    return request.get({ url: '/wedding.service_content_template/lists', params })
}

export function addServiceContentTemplate(params: any) {
    return request.post({ url: '/wedding.service_content_template/add', params })
}

export function editServiceContentTemplate(params: any) {
    return request.post({ url: '/wedding.service_content_template/edit', params })
}

export function deleteServiceContentTemplate(params: any) {
    return request.post({ url: '/wedding.service_content_template/delete', params })
}

export function detailServiceContentTemplate(params: any) {
    return request.get({ url: '/wedding.service_content_template/detail', params })
}

export function updateServiceContentTemplateStatus(params: any) {
    return request.post({ url: '/wedding.service_content_template/updateStatus', params })
}

export function getServiceContentTemplateCategoryOptions() {
    return request.get({ url: '/wedding.service_content_template/categoryOptions' })
}
