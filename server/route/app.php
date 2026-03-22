<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Console;
use think\facade\Route;

Route::get('api/wedding/provider/lists', 'api/Wedding/providerLists');
Route::get('api/wedding/provider/detail', 'api/Wedding/providerDetail');
Route::post('api/wedding/order/preview', 'api/Wedding/orderPreview');
Route::post('api/wedding/order/create', 'api/Wedding/orderCreate');
Route::get('api/wedding/order/lists', 'api/Wedding/orderLists');
Route::get('api/wedding/order/detail', 'api/Wedding/orderDetail');
Route::post('api/wedding/order/cancel', 'api/Wedding/orderCancel');
Route::post('api/wedding/order/offlineVoucher', 'api/Wedding/orderOfflineVoucher');
Route::post('api/wedding/order/rescheduleApply', 'api/Wedding/orderRescheduleApply');
Route::post('api/wedding/order/reviewSubmit', 'api/Wedding/orderReviewSubmit');
Route::get('api/wedding/provider/order/lists', 'api/Wedding/providerOrderLists');
Route::get('api/wedding/provider/order/pendingLists', 'api/Wedding/providerOrderPendingLists');
Route::get('api/wedding/provider/order/detail', 'api/Wedding/providerOrderDetail');
Route::post('api/wedding/provider/order/accept', 'api/Wedding/providerOrderAccept');
Route::post('api/wedding/provider/order/reject', 'api/Wedding/providerOrderReject');
Route::post('api/wedding/provider/order/rescheduleHandle', 'api/Wedding/providerOrderRescheduleHandle');
Route::post('api/wedding/provider/order/serviceComplete', 'api/Wedding/providerOrderServiceComplete');
Route::post('api/wedding/provider/order/reviewAudit', 'api/Wedding/providerOrderReviewAudit');
Route::get('api/notice/lists', 'api/Notice/lists');
Route::post('api/notice/read', 'api/Notice/read');
Route::post('api/notice/readAll', 'api/Notice/readAll');

// 管理后台
Route::rule('admin/:any', function () {
    return view(app()->getRootPath() . 'public/admin/index.html');
})->pattern(['any' => '\w+']);

// 手机端
Route::rule('mobile/:any', function () {
    return view(app()->getRootPath() . 'public/mobile/index.html');
})->pattern(['any' => '\w+']);

// PC端
Route::rule('pc/:any', function () {
    return view(app()->getRootPath() . 'public/pc/index.html');
})->pattern(['any' => '\w+']);

//定时任务
Route::rule('crontab', function () {
    Console::call('crontab');
});
