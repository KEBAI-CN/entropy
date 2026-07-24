<?php

return [
    'code' => 'default',
    'name' => '默认模板',
    'description' => '店铺默认首页模板',
    'version' => '1.0.0',
    'author' => '熵',
    'module' => 'shop_home',
    'platform' => 'responsive',
    'devices' => ['desktop'],
    'variables' => [
        'product_cover_image' => [
            'name' => '商品封面',
            'description' => '控制默认桌面模板是否显示商品封面图',
            'type' => 'boolean',
            'default_enabled' => false,
            'default_user_customizable' => false,
            'default_admin_value' => false,
        ],
        'show_product_count' => [
            'name' => '商品数量',
            'description' => '控制默认模板是否显示商品数量',
            'type' => 'boolean',
            'default_enabled' => true,
            'default_user_customizable' => true,
            'default_admin_value' => true,
        ],
        'show_deposit' => [
            'name' => '保证金',
            'description' => '控制默认模板是否显示保证金标签',
            'type' => 'boolean',
            'default_enabled' => true,
            'default_user_customizable' => true,
            'default_admin_value' => true,
        ],
        'show_category_all' => [
            'name' => '分类全部按钮',
            'description' => '是否显示分类中的“全部”按钮',
            'type' => 'boolean',
            'default_enabled' => true,
            'default_user_customizable' => true,
            'default_admin_value' => true,
        ],
    ],
    'params' => []
];
