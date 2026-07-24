<?php

namespace app\home\black_gold\controller;

use app\BaseController;
use app\service\HomeTemplateService;

class Index extends BaseController
{
    public function index()
    {
        return view(app_path() . 'home/black_gold/view/index/index.html', [
            'templateCode' => 'black_gold',
            'params' => HomeTemplateService::getConfig('black_gold'),
        ]);
    }
}
