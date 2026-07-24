<?php

use app\service\HomeTemplateService;
use think\facade\Route;

Route::rule('', function () {
    return HomeTemplateService::dispatchController('black_gold');
});
Route::rule('/', function () {
    return HomeTemplateService::dispatchController('black_gold');
});
