<?php

namespace app\shop_home\default\controller;

use app\BaseController;
use app\service\DynamicShopTemplateService;

class Index extends BaseController
{
    public function index(string $slug = '', array $shop = [], bool $showNav = false)
    {
        return DynamicShopTemplateService::renderBuiltTemplate('default', $slug, $shop, $showNav);
    }
}
