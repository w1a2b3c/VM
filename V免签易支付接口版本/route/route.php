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

Route::get('think', function () {
    return 'hello,ThinkPHP5!';
});

Route::any('login','index/login/index');//这里的login是后台登录入口，可自行修改

Route::any('getMenu','admin/index/getMenu');
Route::any('enQrcode','index/index/enQrcode');
Route::any('createOrder','index/index/createOrder');


Route::any('getOrder','index/index/getOrder');
Route::any('checkOrder','index/index/checkOrder');
Route::any('getState','index/index/getState');

Route::any('appHeart','index/index/appHeart');
Route::any('appPush','index/index/appPush');

Route::any('submitBd','index/index/submitBd');

Route::any('closeEndOrder','index/index/closeEndOrder');

Route::any('alipayInfo','index/index/alipayInfo');
Route::any('cron','index/index/cron');

