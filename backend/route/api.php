<?php
declare(strict_types=1);

use think\facade\Route;

// ============================================
// API V1 路由 - 前台用户接口
// ============================================

Route::group('api/v1', function () {

    // -------- 认证 (无需登录) --------
    Route::group('auth', function () {
        Route::post('register', 'api.Auth/register')
            ->middleware(app\common\middleware\RateLimitMiddleware::class, '5,1');
        Route::post('login',    'api.Auth/login')
            ->middleware(app\common\middleware\RateLimitMiddleware::class, '10,1');
        Route::post('refresh',  'api.Auth/refresh');
    });

    // -------- 公开接口 --------
    // 分类
    Route::get('categories',        'api.Category/tree');

    // 软件
    Route::get('software',          'api.Software/index');
    Route::get('software/hot',      'api.Software/hot');
    Route::get('software/recommend','api.Software/recommend');
    Route::get('software/search',   'api.Software/search');
    Route::get('software/<id>',     'api.Software/detail')->pattern(['id' => '\d+']);

    // 评论
    Route::get('comments',      'api.Comment/list');

    // 全站搜索（hot在前，避免被 search 匹配）
    Route::get('search/hot',    'api.Search/hot');
    Route::get('search',        'api.Search/index');

    // 万能会员整合登录
    Route::post('integration/login', 'api.Integration/login');

    // 下载文件（通过Token，无需登录）
    Route::get('download/file', 'api.Download/download');

    // 在线支付 - 套餐/渠道查询（公开）
    Route::get('payment/packages', 'api.Payment/packages');
    Route::get('payment/channels', 'api.Payment/channels');

    // 支付报表（公开）
    Route::get('report/summary',  '\\app\\api\\controller\\ReportController@summary');
    Route::get('report/trend',    '\\app\\api\\controller\\ReportController@trend');
    Route::get('report/channels', '\\app\\api\\controller\\ReportController@channels');
    Route::get('report/packages', '\\app\\api\\controller\\ReportController@packages');

    // 支付回调通知（公开，无需认证）
    Route::post('notify/alipay', 'api.Notify/alipay');
    Route::post('notify/wechat', 'api.Notify/wechat');
    Route::get('notify/return/alipay', 'api.Notify/alipayReturn');

    // -------- 需登录认证 --------
    Route::group('', function () {

        // 退出登录
        Route::post('auth/logout', 'api.Auth/logout');

        // 用户个人信息
        Route::get('user/me',         'api.User/profile');
        Route::put('user/me',         'api.User/updateProfile');
        Route::put('user/password',   'api.User/changePassword');
        Route::get('user/points-log', 'api.User/pointsLog');

        // 下载
        Route::post('download/url',   'api.Download/getUrl');

        // 充值
        Route::post('recharge/card',  'api.Recharge/cardRecharge');
        Route::get('recharge/records','api.Recharge/records');

        // 在线支付
        Route::post('payment/create',  'api.Payment/create');
        Route::get('payment/query/<orderNo>', 'api.Payment/query');

        // 收藏
        Route::get('favorites',       'api.Favorite/list');
        Route::post('favorites',      'api.Favorite/add');
        Route::delete('favorites/<id>','api.Favorite/remove')->pattern(['id' => '\d+']);

        // 评论
        Route::post('comments',       'api.Comment/create');

    })->middleware([app\common\middleware\AuthMiddleware::class]);

});
