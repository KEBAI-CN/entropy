<?php

namespace app\home\spring_festival\controller;

use app\BaseController;
use app\service\HomeTemplateService;

class Index extends BaseController
{
    public function index()
    {
        return view(app_path() . 'home/spring_festival/view/index/index.html', [
            'templateCode' => 'spring_festival',
            'params' => HomeTemplateService::getConfig('spring_festival'),
        ]);
    }
}
