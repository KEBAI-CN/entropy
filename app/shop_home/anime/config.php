<?php

return [
    'code' => 'anime',
    'name' => '二次元模板',
    'description' => '店铺二次元风格首页模板',
    'version' => '1.0.0',
    'author' => '熵',
    'module' => 'shop_home',
    'platform' => 'desktop',
    'devices' => ['desktop'],
    'variables' => [
        'background_image' => [
            'name' => '顶部背景图',
            'description' => '二次元模板顶部背景图地址',
            'type' => 'string',
            'default_enabled' => true,
            'default_user_customizable' => true
        ],
        'product_cover_image' => [
            'name' => '商品封面图',
            'description' => '控制二次元模板是否显示商品封面图',
            'type' => 'boolean',
            'default_enabled' => true,
            'default_user_customizable' => true,
            'default_admin_value' => true
        ],
        'show_category_all' => [
            'name' => '分类全部按钮',
            'description' => '是否显示分类中的“全部”按钮',
            'type' => 'boolean',
            'default_enabled' => true,
            'default_user_customizable' => true,
            'default_admin_value' => true,
        ],
        'show_product_count' => [
            'name' => '商品数量',
            'description' => '控制二次元模板是否显示商品数量',
            'type' => 'boolean',
            'default_enabled' => true,
            'default_user_customizable' => true,
            'default_admin_value' => true
        ],
        'show_sold_count' => [
            'name' => '成交数量',
            'description' => '控制二次元模板是否显示成交数量',
            'type' => 'boolean',
            'default_enabled' => true,
            'default_user_customizable' => true,
            'default_admin_value' => true
        ],
        'show_deposit' => [
            'name' => '保证金',
            'description' => '控制二次元模板是否显示保证金标签',
            'type' => 'boolean',
            'default_enabled' => true,
            'default_user_customizable' => true,
            'default_admin_value' => true
        ],
    ],
    'params' => []
];
