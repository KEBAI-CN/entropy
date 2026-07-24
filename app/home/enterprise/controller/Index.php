<?php

namespace app\home\enterprise\controller;

use app\BaseController;
use app\service\HomeTemplateService;

class Index extends BaseController
{
    public function index()
    {
        return view(app_path() . 'home/enterprise/view/index/index.html', [
            'templateCode' => 'enterprise',
            'params' => HomeTemplateService::getConfig('enterprise'),
        ]);
    }
}
