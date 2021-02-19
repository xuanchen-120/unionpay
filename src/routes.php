<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

//总后台
Route::group([
    'prefix'     => config('admin.route.prefix'),
    'namespace'  => 'XuanChen\UnionPay\Controllers\Admin',
    'middleware' => config('admin.route.middleware'),
], function (Router $router) {
    $router->resource('unionpays', 'IndexController');
    $router->resource('unionpaycoupons', 'CouponController');
    $router->resource('unionchecks', 'CheckController');
    $router->resource('unionchecklogs', 'CheckLogController');
});

//手机端
Route::group([
    'prefix'    => 'api/V1',
    'namespace' => 'XuanChen\UnionPay\Controllers\Api',
], function (Router $router) {
    //银联相关
    Route::post('unionpay/index', 'IndexController@index');
    Route::post('unionpay/query', 'IndexController@query');

    //本时生活
    Route::post('unionpay/openid', 'IndexController@openid');
    Route::post('unionpay/code', 'IndexController@code');
});
