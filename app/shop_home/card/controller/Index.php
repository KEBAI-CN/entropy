<?php

namespace app\shop_home\card\controller;

use app\BaseController;
use app\service\DynamicShopTemplateService;

class Index extends BaseController
{
    public function index(string $slug = '', array $shop = [], bool $showNav = false)
    {
        return DynamicShopTemplateService::renderBuiltTemplate('card', $slug, $shop, $showNav);
    }
}
