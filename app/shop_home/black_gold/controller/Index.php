<?php

namespace app\shop_home\black_gold\controller;

use app\BaseController;
use app\service\DynamicShopTemplateService;

class Index extends BaseController
{
    public function index(string $slug = '', array $shop = [], bool $showNav = false)
    {
        return DynamicShopTemplateService::renderBuiltTemplate('black_gold', $slug, $shop, $showNav);
    }
}
