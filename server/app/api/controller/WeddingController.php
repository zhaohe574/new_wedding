<?php

declare(strict_types=1);

namespace app\api\controller;

use app\adminapi\logic\wedding\ServiceCategoryLogic;
use app\adminapi\logic\wedding\ServiceTagLogic;
use app\api\validate\WeddingProfileValidate;
use app\api\validate\WeddingOrderValidate;
use app\api\validate\WeddingTradeValidate;
use app\common\model\wedding\ServiceOpenCity;
use app\common\service\ServiceContentTemplateService;
use app\common\service\ServiceRegionService;
use app\common\service\ServiceOrderService;
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
            trim((string)$params['service_date'])
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
}
