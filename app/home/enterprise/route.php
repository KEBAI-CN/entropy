<?php

use app\service\HomeTemplateService;
use think\facade\Route;

Route::rule('', function () {
    return HomeTemplateService::dispatchController('enterprise');
});
Route::rule('/', function () {
    return HomeTemplateService::dispatchController('enterprise');
});
