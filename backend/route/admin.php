<?php
declare(strict_types=1);

use think\facade\Route;

// ============================================
// Admin API 路由 - 后台管理接口
// ============================================

Route::group('admin/api', function () {

    // -------- 管理认证 (无需登录) --------
    Route::post('auth/login', 'admin.Auth/login')
        ->middleware(app\common\middleware\RateLimitMiddleware::class, '10,1');

    // -------- 需管理认证 + 权限校验 --------
    Route::group('', function () {

        Route::post('auth/logout', 'admin.Auth/logout');
        Route::get('auth/me',     'admin.Auth/profile');

        // 分类管理
        Route::get('categories',            'admin.Category/tree');
        Route::post('categories',           'admin.Category/create');
        Route::put('categories/<id>',       'admin.Category/update')->pattern(['id' => '\d+']);
        Route::delete('categories/<id>',    'admin.Category/delete')->pattern(['id' => '\d+']);

        // 软件管理
        Route::get('software',              'admin.Software/index');
        Route::get('software/<id>',         'admin.Software/detail')->pattern(['id' => '\d+']);
        Route::post('software',             'admin.Software/create');
        Route::put('software/<id>',         'admin.Software/update')->pattern(['id' => '\d+']);
        Route::delete('software/<id>',      'admin.Software/delete')->pattern(['id' => '\d+']);
        Route::put('software/<id>/audit',   'admin.Software/audit')->pattern(['id' => '\d+']);

        // 会员管理
        Route::get('users',                 'admin.User/index');
        Route::get('users/<id>',            'admin.User/detail')->pattern(['id' => '\d+']);
        Route::put('users/<id>/points',     'admin.User/adjustPoints')->pattern(['id' => '\d+']);
        Route::put('users/<id>/group',      'admin.User/changeGroup')->pattern(['id' => '\d+']);
        Route::put('users/<id>/ban',        'admin.User/ban')->pattern(['id' => '\d+']);

        // 点卡管理
        Route::get('point-cards/batches',       'admin.PointCard/batchList');
        Route::post('point-cards/batches',      'admin.PointCard/createBatch');
        Route::get('point-cards/batches/<id>/cards', 'admin.PointCard/cardList')->pattern(['id' => '\d+']);
        Route::post('point-cards/batches/<id>/generate', 'admin.PointCard/generateCards')->pattern(['id' => '\d+']);
        Route::get('point-cards/batches/<id>/export', 'admin.PointCard/exportCards')->pattern(['id' => '\d+']);

        // 支付渠道配置管理
        Route::get('pay/channels',              'admin.PayChannel/index');
        Route::post('pay/channels',             'admin.PayChannel/create');
        Route::put('pay/channels/<id>',         'admin.PayChannel/update')->pattern(['id' => '\d+']);
        Route::delete('pay/channels/<id>',      'admin.PayChannel/delete')->pattern(['id' => '\d+']);

        // 充值套餐管理
        Route::get('pay/packages',              'admin.PayPackage/index');
        Route::post('pay/packages',             'admin.PayPackage/create');
        Route::put('pay/packages/<id>',         'admin.PayPackage/update')->pattern(['id' => '\d+']);
        Route::delete('pay/packages/<id>',      'admin.PayPackage/delete')->pattern(['id' => '\d+']);

        // 充值管理
        Route::get('recharges',             'admin.Recharge/index');
        Route::post('recharges/manual',     'admin.Recharge/manualRecharge');
        // 日志管理
        Route::get('logs/operation',         '\app\admin\controller\LogController@operation');
        Route::get('logs/login',            '\app\admin\controller\LogController@login');
        Route::get('logs/modules',          '\app\admin\controller\LogController@modules');
        Route::get('logs/stats',            '\app\admin\controller\LogController@stats');
        // 数据库备份管理
        Route::post('db/backup',            'admin.Db/backup');
        Route::get('db/list',               'admin.Db/list');
        Route::post('db/restore',           'admin.Db/restore');
        Route::delete('db/backup/<id>',     'admin.Db/delete')->pattern(['id' => '\d+']);
        Route::post('db/optimize',          'admin.Db/optimize');
        Route::post('db/repair',            'admin.Db/repair');

        // 万能会员整合配置管理
        Route::get('integration/configs',         'admin.Integration/configs');
        Route::post('integration/config',         'admin.Integration/createConfig');
        Route::put('integration/config/<id>',     'admin.Integration/updateConfig')->pattern(['id' => '\d+']);
        Route::delete('integration/config/<id>',  'admin.Integration/deleteConfig')->pattern(['id' => '\d+']);
        Route::post('integration/test/<id>',      'admin.Integration/testConnection')->pattern(['id' => '\d+']);

        // 静态化生成
        Route::post('static/generate',      'admin.Static/generate');
        Route::get('static/status',         'admin.Static/status');

        // RBAC - 角色管理
        Route::get('roles',                   '\app\admin\controller\RoleController@index');
        Route::get('roles/permissions',       '\app\admin\controller\RoleController@permissions');
        Route::get('roles/<id>',              '\app\admin\controller\RoleController@detail')->pattern(['id' => '\d+']);
        Route::post('roles',                  '\app\admin\controller\RoleController@create');
        Route::put('roles/<id>',              '\app\admin\controller\RoleController@update')->pattern(['id' => '\d+']);
        Route::delete('roles/<id>',           '\app\admin\controller\RoleController@delete')->pattern(['id' => '\d+']);

        // RBAC - 管理员管理
        Route::get('admin-users',             '\app\admin\controller\AdminUserController@index');
        Route::get('admin-users/roles',       '\app\admin\controller\AdminUserController@roles');
        Route::get('admin-users/<id>',        '\app\admin\controller\AdminUserController@detail')->pattern(['id' => '\d+']);
        Route::post('admin-users',            '\app\admin\controller\AdminUserController@create');
        Route::put('admin-users/<id>',        '\app\admin\controller\AdminUserController@update')->pattern(['id' => '\d+']);
        Route::delete('admin-users/<id>',     '\app\admin\controller\AdminUserController@delete')->pattern(['id' => '\d+']);

        // Phase 5 报表统计
        Route::get('report/summary',        '\app\admin\controller\PaymentReportController@summary');
        Route::get('report/trend',          '\app\admin\controller\PaymentReportController@trend');
        Route::get('report/channels',       '\app\admin\controller\PaymentReportController@channels');
        Route::get('report/packages',       '\app\admin\controller\PaymentReportController@packages');

        // 下载统计
        Route::get('download-stats/summary',     '\app\admin\controller\DownloadStatController@summary');
        Route::get('download-stats/trend',       '\app\admin\controller\DownloadStatController@trend');
        Route::get('download-stats/top-software','\app\admin\controller\DownloadStatController@topSoftware');
        Route::get('download-stats/by-group',    '\app\admin\controller\DownloadStatController@byGroup');

        // 附件管理
        Route::get('attachments',            '\app\admin\controller\AttachmentController@index');
        Route::post('attachments/upload',    '\app\admin\controller\AttachmentController@upload');
        Route::delete('attachments/<id>',    '\app\admin\controller\AttachmentController@delete')->pattern(['id' => '\d+']);
        Route::put('attachments/<id>/sort',  '\app\admin\controller\AttachmentController@updateSort')->pattern(['id' => '\d+']);

        // 广告管理
        Route::get('ads/places',             '\app\admin\controller\AdController@places');
        Route::post('ads/places',            '\app\admin\controller\AdController@createPlace');
        Route::put('ads/places/<id>',        '\app\admin\controller\AdController@updatePlace')->pattern(['id' => '\d+']);
        Route::delete('ads/places/<id>',     '\app\admin\controller\AdController@deletePlace')->pattern(['id' => '\d+']);
        Route::get('ads/records',            '\app\admin\controller\AdController@records');
        Route::post('ads/records',           '\app\admin\controller\AdController@createRecord');
        Route::put('ads/records/<id>',       '\app\admin\controller\AdController@updateRecord')->pattern(['id' => '\d+']);
        Route::delete('ads/records/<id>',    '\app\admin\controller\AdController@deleteRecord')->pattern(['id' => '\d+']);
        Route::get('ads/stats',             '\app\admin\controller\AdController@stats');

        // 模板变量管理
        Route::get('template-vars',          '\app\admin\controller\TemplateVarController@index');
        Route::get('template-vars/map',      '\app\admin\controller\TemplateVarController@map');
        Route::post('template-vars',         '\app\admin\controller\TemplateVarController@create');
        Route::put('template-vars/<id>',     '\app\admin\controller\TemplateVarController@update')->pattern(['id' => '\d+']);
        Route::delete('template-vars/<id>',  '\app\admin\controller\TemplateVarController@delete')->pattern(['id' => '\d+']);

        // 系统配置管理
        Route::get('system-configs',         '\app\admin\controller\SystemConfigController@index');
        Route::get('system-configs/groups',  '\app\admin\controller\SystemConfigController@groups');
        Route::post('system-configs',        '\app\admin\controller\SystemConfigController@create');
        Route::put('system-configs/<id>',    '\app\admin\controller\SystemConfigController@update')->pattern(['id' => '\d+']);
        Route::delete('system-configs/<id>', '\app\admin\controller\SystemConfigController@delete')->pattern(['id' => '\d+']);
        Route::put('system-configs/batch',   '\app\admin\controller\SystemConfigController@batchUpdate');

    })->middleware([app\common\middleware\AdminAuthMiddleware::class, app\common\middleware\LogMiddleware::class]);

});
