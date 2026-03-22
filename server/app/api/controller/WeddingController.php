<?php

declare(strict_types=1);

namespace app\api\controller;

use app\adminapi\logic\wedding\ServiceCategoryLogic;
use app\adminapi\logic\wedding\ServiceTagLogic;
use app\api\validate\WeddingProviderCenterValidate;
use app\api\validate\WeddingProviderPostValidate;
use app\api\validate\WeddingProviderScheduleValidate;
use app\api\validate\WeddingProfileValidate;
use app\api\validate\WeddingOrderValidate;
use app\api\validate\WeddingTradeValidate;
use app\common\model\notice\NoticeSetting;
use app\common\model\wedding\ServiceOpenCity;
use app\common\service\ProviderPostService;
use app\common\service\ProviderProfileChangeService;
use app\common\service\ProviderScheduleService;
use app\common\service\ServiceBusinessConfigService;
use app\common\service\ServiceContentTemplateService;
use app\common\service\ServiceRegionService;
use app\common\service\ServiceOrderService;
use app\common\service\WeddingWorkbenchStatService;
use app\common\service\WeddingTradeService;
use app\common\service\WeddingProfileService;
use think\response\Json;

class WeddingController extends BaseApiController
{
    public array $notNeedLogin = ['categories', 'tags', 'openRegionTree', 'template'];

    public function categories(): Json
    {
        return $this->data(ServiceCategoryLogic::getAllData());
    }

    public function tags(): Json
    {
        return $this->data(ServiceTagLogic::getAllData());
    }

    public function openRegionTree(): Json
    {
        $openCityCodes = ServiceOpenCity::where(['status' => 1])
            ->whereNull('delete_time')
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->column('city_code');

        return $this->data([
            'open_city_codes' => array_values(array_map('strval', $openCityCodes)),
            'tree' => ServiceRegionService::getOpenRegionTree($openCityCodes),
        ]);
    }

    public function profile(): Json
    {
        return $this->data(WeddingProfileService::getUserProfile($this->userId));
    }

    public function saveProfile(): Json
    {
        $params = (new WeddingProfileValidate())->post()->goCheck();
        return $this->success('保存成功', WeddingProfileService::saveUserProfile($this->userId, $params), 1, 1);
    }

    public function template(): Json
    {
        $categoryId = (int)$this->request->get('category_id/d', 0);
        return $this->data(ServiceContentTemplateService::getFrontendTemplateByCategory($categoryId));
    }

    public function providerLists(): Json
    {
        $params = (new WeddingTradeValidate())->get()->goCheck('providerLists');
        return $this->data(WeddingTradeService::getProviderLists(
            (int)$params['category_id'],
            trim((string)$params['district_code']),
            trim((string)$params['service_date']),
            array_values(array_unique(array_filter(array_map(
                'intval',
                explode(',', trim((string)($params['tag_ids'] ?? '')))
            )))),
            trim((string)($params['keyword'] ?? '')),
            trim((string)($params['price_sort'] ?? ''))
        ));
    }

    public function providerDetail(): Json
    {
        $params = (new WeddingTradeValidate())->get()->goCheck('providerDetail');
        return $this->data(WeddingTradeService::getProviderDetail(
            (int)$params['provider_id'],
            trim((string)$params['district_code']),
            trim((string)$params['service_date'])
        ));
    }

    public function orderPreview(): Json
    {
        $params = (new WeddingTradeValidate())->post()->goCheck('orderPreview');
        return $this->data(WeddingTradeService::buildOrderPreview(
            $this->userId,
            (int)$params['provider_id'],
            (int)$params['package_id'],
            trim((string)$params['district_code']),
            trim((string)$params['service_date']),
            $params['template_form_data'] ?? []
        ));
    }

    public function orderCreate(): Json
    {
        $params = (new WeddingOrderValidate())->post()->goCheck('create');
        $data = ServiceOrderService::createOrder($this->userId, (int)($this->userInfo['terminal'] ?? 0), $params);
        return $this->success('下单成功', $data, 1, 1);
    }

    public function orderLists(): Json
    {
        $params = (new WeddingOrderValidate())->get()->goCheck('lists');
        return $this->data(ServiceOrderService::getUserOrderLists($this->userId, $params));
    }

    public function orderDetail(): Json
    {
        $params = (new WeddingOrderValidate())->get()->goCheck('detail');
        return $this->data(ServiceOrderService::getUserOrderDetail($this->userId, (int)$params['order_id']));
    }

    public function orderCancel(): Json
    {
        $params = (new WeddingOrderValidate())->post()->goCheck('cancel');
        ServiceOrderService::cancelByUser(
            $this->userId,
            (int)$params['order_id'],
            trim((string)($params['cancel_reason'] ?? ''))
        );
        return $this->success('订单已取消', [], 1, 1);
    }

    public function orderOfflineVoucher(): Json
    {
        $params = (new WeddingOrderValidate())->post()->goCheck('offlineVoucher');
        ServiceOrderService::submitOfflineVoucher(
            $this->userId,
            (int)$params['order_id'],
            $params['voucher_images'] ?? [],
            trim((string)($params['voucher_remark'] ?? ''))
        );
        return $this->success('线下凭证提交成功', [], 1, 1);
    }

    public function orderRefundApply(): Json
    {
        $params = (new WeddingOrderValidate())->post()->goCheck('refundApply');
        ServiceOrderService::refundApplyByUser(
            $this->userId,
            (int)$params['order_id'],
            trim((string)($params['apply_reason'] ?? ''))
        );
        return $this->success('退款申请已提交', [], 1, 1);
    }

    public function orderRescheduleApply(): Json
    {
        $params = (new WeddingOrderValidate())->post()->goCheck('rescheduleApply');
        ServiceOrderService::rescheduleApplyByUser(
            $this->userId,
            (int)$params['order_id'],
            trim((string)$params['new_service_date']),
            trim((string)($params['apply_reason'] ?? ''))
        );
        return $this->success('改期申请已提交', [], 1, 1);
    }

    public function orderReviewSubmit(): Json
    {
        $params = (new WeddingOrderValidate())->post()->goCheck('reviewSubmit');
        ServiceOrderService::submitReviewByUser(
            $this->userId,
            (int)$params['order_id'],
            (int)$params['review_score'],
            trim((string)($params['review_content'] ?? '')),
            $params['review_images'] ?? []
        );
        return $this->success('评价提交成功', [], 1, 1);
    }

    public function dashboardOverview(): Json
    {
        $ability = ServiceBusinessConfigService::getUserAbility($this->userId);
        if (empty($ability['can_view_dashboard'])) {
            return $this->fail('当前账号无经营驾驶舱查看权限');
        }

        return $this->data(WeddingWorkbenchStatService::getOverview());
    }

    public function providerCenterDetail(): Json
    {
        return $this->data(ProviderProfileChangeService::getProviderCenterDetail($this->userId));
    }

    public function providerChangeRequestLists(): Json
    {
        $params = (new WeddingProviderCenterValidate())->get()->goCheck('lists');
        return $this->data(ProviderProfileChangeService::getChangeRequestLists($this->userId, $params));
    }

    public function providerChangeRequestDetail(): Json
    {
        $params = (new WeddingProviderCenterValidate())->get()->goCheck('detail');
        return $this->data(ProviderProfileChangeService::getChangeRequestDetail((int)$params['id']));
    }

    public function providerChangeRequestSubmit(): Json
    {
        $params = (new WeddingProviderCenterValidate())->post()->goCheck('submit');
        $data = ProviderProfileChangeService::submitChange(
            $this->userId,
            trim((string)$params['change_type']),
            is_array($params['payload']) ? $params['payload'] : []
        );

        return $this->success('变更申请已提交', $data, 1, 1);
    }

    public function providerPostLists(): Json
    {
        $params = (new WeddingProviderPostValidate())->get()->goCheck('providerLists');
        return $this->data(ProviderPostService::getProviderPostLists($this->userId, $params));
    }

    public function providerPostSave(): Json
    {
        $params = (new WeddingProviderPostValidate())->post()->goCheck('save');
        $data = ProviderPostService::savePost($this->userId, $params);
        return $this->success('动态保存成功', $data, 1, 1);
    }

    public function providerPostDelete(): Json
    {
        $params = (new WeddingProviderPostValidate())->post()->goCheck('delete');
        ProviderPostService::deletePost($this->userId, (int)$params['id']);
        return $this->success('动态已删除', [], 1, 1);
    }

    public function providerCommentPendingLists(): Json
    {
        $params = (new WeddingProviderPostValidate())->get()->goCheck('pendingComments');
        return $this->data(ProviderPostService::getProviderPendingCommentLists($this->userId, $params));
    }

    public function providerCommentAudit(): Json
    {
        $params = (new WeddingProviderPostValidate())->post()->goCheck('auditComment');
        ProviderPostService::auditCommentByProvider(
            $this->userId,
            (int)$params['comment_id'],
            (int)$params['audit_status'],
            trim((string)($params['audit_remark'] ?? ''))
        );
        return $this->success('评论处理成功', [], 1, 1);
    }

    public function providerPublicPostLists(): Json
    {
        $params = (new WeddingProviderPostValidate())->get()->goCheck('publicLists');
        return $this->data(ProviderPostService::getPublicPostLists((int)$params['provider_id'], $params));
    }

    public function providerPostCommentCreate(): Json
    {
        $params = (new WeddingProviderPostValidate())->post()->goCheck('createComment');
        $data = ProviderPostService::createComment(
            $this->userId,
            (int)$params['post_id'],
            trim((string)$params['comment_content']),
            (int)($params['parent_id'] ?? 0)
        );
        return $this->success('评论提交成功', $data, 1, 1);
    }

    public function noticeSubscribeTemplates(): Json
    {
        $sceneIds = $this->request->get('scene_ids/a', []);
        if (!is_array($sceneIds) || empty($sceneIds)) {
            $sceneIds = array_values(array_filter(array_map('intval', explode(',', (string)$this->request->get('scene_ids/s', '')))));
        }

        $query = NoticeSetting::field('scene_id,scene_name,mnp_notice')
            ->whereNotNull('mnp_notice');
        if (!empty($sceneIds)) {
            $query->whereIn('scene_id', $sceneIds);
        }

        $lists = $query->select()->toArray();
        $lists = array_values(array_filter(array_map(static function ($item) {
            $mnpNotice = $item['mnp_notice'] ?? [];
            if ((int)($mnpNotice['status'] ?? 0) !== 1 || trim((string)($mnpNotice['template_id'] ?? '')) === '') {
                return null;
            }

            return [
                'scene_id' => (int)($item['scene_id'] ?? 0),
                'scene_name' => (string)($item['scene_name'] ?? ''),
                'template_id' => (string)($mnpNotice['template_id'] ?? ''),
                'template_sn' => (string)($mnpNotice['template_sn'] ?? ''),
                'name' => (string)($mnpNotice['name'] ?? ''),
            ];
        }, $lists)));

        return $this->data([
            'lists' => $lists,
        ]);
    }

    public function providerOrderLists(): Json
    {
        $params = (new WeddingOrderValidate())->get()->goCheck('providerOrderLists');
        return $this->data(ServiceOrderService::getProviderOrderLists($this->userId, $params));
    }

    public function providerOrderPendingLists(): Json
    {
        $params = (new WeddingOrderValidate())->get()->goCheck('providerPendingLists');
        return $this->data(ServiceOrderService::getProviderPendingOrderLists($this->userId, $params));
    }

    public function providerOrderDetail(): Json
    {
        $params = (new WeddingOrderValidate())->get()->goCheck('providerOrderDetail');
        return $this->data(ServiceOrderService::getProviderOrderDetail($this->userId, (int)$params['order_id']));
    }

    public function providerOrderAccept(): Json
    {
        $params = (new WeddingOrderValidate())->post()->goCheck('providerAccept');
        ServiceOrderService::providerAcceptOrder($this->userId, (int)$params['order_id']);
        return $this->success('接单成功', [], 1, 1);
    }

    public function providerOrderReject(): Json
    {
        $params = (new WeddingOrderValidate())->post()->goCheck('providerReject');
        ServiceOrderService::providerRejectOrder(
            $this->userId,
            (int)$params['order_id'],
            trim((string)$params['reject_reason'])
        );
        return $this->success('拒单成功', [], 1, 1);
    }

    public function providerOrderRescheduleHandle(): Json
    {
        $params = (new WeddingOrderValidate())->post()->goCheck('providerRescheduleHandle');
        ServiceOrderService::providerHandleReschedule(
            $this->userId,
            (int)$params['change_id'],
            (int)$params['audit_status'],
            trim((string)($params['audit_remark'] ?? ''))
        );
        return $this->success('改期申请处理成功', [], 1, 1);
    }

    public function providerOrderServiceComplete(): Json
    {
        $params = (new WeddingOrderValidate())->post()->goCheck('providerServiceComplete');
        ServiceOrderService::providerCompleteService($this->userId, (int)$params['order_id']);
        return $this->success('履约状态已更新', [], 1, 1);
    }

    public function providerOrderReviewAudit(): Json
    {
        $params = (new WeddingOrderValidate())->post()->goCheck('providerReviewAudit');
        ServiceOrderService::providerAuditReview(
            $this->userId,
            (int)$params['order_id'],
            (int)$params['audit_status'],
            trim((string)($params['audit_remark'] ?? ''))
        );
        return $this->success('评价审核成功', [], 1, 1);
    }

    public function providerScheduleMonth(): Json
    {
        $params = (new WeddingProviderScheduleValidate())->get()->goCheck('month');
        $provider = $this->getCurrentProvider();
        $data = ProviderScheduleService::getMonthCalendar(
            (int)$provider['id'],
            trim((string)($params['month'] ?? ''))
        );

        $data['provider'] = [
            'provider_id' => (int)$provider['id'],
            'name' => (string)($provider['name'] ?? ''),
            'category_id' => (int)($provider['category_id'] ?? 0),
        ];

        return $this->data($data);
    }

    public function providerScheduleUpsert(): Json
    {
        $params = (new WeddingProviderScheduleValidate())->post()->goCheck('upsert');
        $provider = $this->getCurrentProvider();
        $data = ProviderScheduleService::saveProviderDates(
            (int)$provider['id'],
            $params['service_dates'] ?? [],
            (string)$params['status'],
            trim((string)($params['remark'] ?? ''))
        );

        return $this->success('档期保存成功', $data, 1, 1);
    }

    public function providerScheduleDelete(): Json
    {
        $params = (new WeddingProviderScheduleValidate())->post()->goCheck('delete');
        $provider = $this->getCurrentProvider();
        $data = ProviderScheduleService::deleteProviderDates(
            (int)$provider['id'],
            $params['service_dates'] ?? []
        );

        return $this->success('档期已恢复为可预约', $data, 1, 1);
    }

    private function getCurrentProvider(): array
    {
        $provider = ServiceOrderService::getProviderByUserId($this->userId);
        if ($provider->isEmpty()) {
            throw new \RuntimeException('当前账号未绑定可用服务人员');
        }

        return $provider->toArray();
    }
}
