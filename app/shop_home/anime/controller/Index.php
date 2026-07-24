<?php

namespace app\shop_home\anime\controller;

use app\BaseController;
use app\service\DynamicShopTemplateService;

class Index extends BaseController
{
    public function index(string $slug = '', array $shop = [])
    {
        return DynamicShopTemplateService::renderBuiltTemplate('anime', $slug, $shop);
    }
}
