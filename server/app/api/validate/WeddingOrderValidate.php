<?php

declare(strict_types=1);

namespace app\api\validate;

use app\common\service\ServiceRegionService;
use app\common\validate\BaseValidate;

class WeddingOrderValidate extends BaseValidate
{
    protected $rule = [
        'provider_id' => 'require|integer|gt:0',
        'package_id' => 'require|integer|gt:0',
        'district_code' => 'require|checkDistrictCode',
        'service_date' => 'require|dateFormat:Y-m-d',
        'template_form_data' => 'array',
        'payment_type' => 'require|in:1,2',
        'order_id' => 'require|integer|gt:0',
        'order_status' => 'integer',
        'page_no' => 'integer|gt:0',
        'page_size' => 'integer|between:1,50',
        'cancel_reason' => 'max:500',
        'voucher_images' => 'require|array|checkVoucherImages',
        'voucher_remark' => 'max:500',
        'reject_reason' => 'require|max:500',
        'change_id' => 'require|integer|gt:0',
        'new_service_date' => 'require|dateFormat:Y-m-d',
        'apply_reason' => 'max:500',
        'audit_status' => 'require|in:1,2',
        'audit_remark' => 'max:500',
        'view_tab' => 'in:all,pending_confirm,wait_service,reschedule_pending,review_pending',
        'review_score' => 'require|integer|between:1,5',
        'review_content' => 'max:1000',
        'review_images' => 'array|checkVoucherImagesAllowEmpty',
    ];

    protected $message = [
        'provider_id.require' => '服务人员不能为空',
        'provider_id.integer' => '服务人员参数不正确',
        'provider_id.gt' => '服务人员参数不正确',
        'package_id.require' => '服务套餐不能为空',
        'package_id.integer' => '服务套餐参数不正确',
        'package_id.gt' => '服务套餐参数不正确',
        'district_code.require' => '请选择服务区县',
        'service_date.require' => '请选择服务日期',
        'service_date.dateFormat' => '服务日期格式不正确',
        'template_form_data.array' => '服务内容格式不正确',
        'payment_type.require' => '请选择支付方式',
        'payment_type.in' => '支付方式不正确',
        'order_id.require' => '订单参数缺失',
        'order_id.integer' => '订单参数不正确',
        'order_id.gt' => '订单参数不正确',
        'order_status.integer' => '订单状态参数不正确',
        'page_no.integer' => '页码参数不正确',
        'page_no.gt' => '页码参数不正确',
        'page_size.integer' => '分页参数不正确',
        'page_size.between' => '分页参数不正确',
        'cancel_reason.max' => '取消原因最多500个字符',
        'voucher_images.require' => '请上传线下凭证',
        'voucher_images.array' => '线下凭证格式不正确',
        'voucher_remark.max' => '凭证说明最多500个字符',
        'reject_reason.require' => '请输入拒单原因',
        'reject_reason.max' => '拒单原因最多500个字符',
        'change_id.require' => '改期申请参数缺失',
        'change_id.integer' => '改期申请参数不正确',
        'change_id.gt' => '改期申请参数不正确',
        'new_service_date.require' => '请选择新的服务日期',
        'new_service_date.dateFormat' => '新的服务日期格式不正确',
        'apply_reason.max' => '改期原因最多500个字符',
        'audit_status.require' => '审核结果不能为空',
        'audit_status.in' => '审核结果不正确',
        'audit_remark.max' => '审核说明最多500个字符',
        'view_tab.in' => '筛选标签不正确',
        'review_score.require' => '请填写评分',
        'review_score.integer' => '评分格式不正确',
        'review_score.between' => '评分范围不正确',
        'review_content.max' => '评价内容最多1000个字符',
        'review_images.array' => '评价图片格式不正确',
    ];

    protected $scene = [
        'create' => ['provider_id', 'package_id', 'district_code', 'service_date', 'template_form_data', 'payment_type'],
        'lists' => ['order_status', 'page_no', 'page_size'],
        'detail' => ['order_id'],
        'cancel' => ['order_id', 'cancel_reason'],
        'offlineVoucher' => ['order_id', 'voucher_images', 'voucher_remark'],
        'providerPendingLists' => ['page_no', 'page_size'],
        'providerOrderLists' => ['view_tab', 'order_status', 'page_no', 'page_size'],
        'providerOrderDetail' => ['order_id'],
        'providerAccept' => ['order_id'],
        'providerReject' => ['order_id', 'reject_reason'],
        'rescheduleApply' => ['order_id', 'new_service_date', 'apply_reason'],
        'providerRescheduleHandle' => ['change_id', 'audit_status', 'audit_remark'],
        'providerServiceComplete' => ['order_id'],
        'reviewSubmit' => ['order_id', 'review_score', 'review_content', 'review_images'],
        'providerReviewAudit' => ['order_id', 'audit_status', 'audit_remark'],
    ];

    public function checkDistrictCode($value): bool|string
    {
        $districtCode = trim((string)$value);
        if ($districtCode === '') {
            return '请选择服务区县';
        }

        return empty(ServiceRegionService::getDistrictDetail($districtCode)) ? '服务区县不正确' : true;
    }

    public function checkVoucherImages($value): bool|string
    {
        if (!is_array($value) || empty($value)) {
            return '请上传线下凭证';
        }

        foreach ($value as $item) {
            if (trim((string)$item) === '') {
                return '线下凭证格式不正确';
            }
        }

        return true;
    }

    public function checkVoucherImagesAllowEmpty($value): bool|string
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!is_array($value)) {
            return '评价图片格式不正确';
        }
        foreach ($value as $item) {
            if (trim((string)$item) === '') {
                return '评价图片格式不正确';
            }
        }

        return true;
    }
}
