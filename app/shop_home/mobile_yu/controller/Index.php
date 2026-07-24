<?php

namespace app\shop_home\mobile_yu\controller;

use app\BaseController;
use app\service\DynamicShopTemplateService;

class Index extends BaseController
{
    public function index(string $slug = '', array $shop = [], bool $showNav = false)
    {
        return DynamicShopTemplateService::renderBuiltTemplate('mobile_yu', $slug, $shop, false);
    }
}
