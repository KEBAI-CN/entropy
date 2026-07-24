<?php

use app\service\HomeTemplateService;
use think\facade\Route;

Route::rule('', function () {
    return HomeTemplateService::dispatchController('default');
});
Route::rule('/', function () {
    return HomeTemplateService::dispatchController('default');
});
