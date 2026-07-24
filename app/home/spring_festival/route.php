<?php

use app\service\HomeTemplateService;
use think\facade\Route;

Route::rule('', function () {
    return HomeTemplateService::dispatchController('spring_festival');
});
Route::rule('/', function () {
    return HomeTemplateService::dispatchController('spring_festival');
});
