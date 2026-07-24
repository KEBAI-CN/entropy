<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

$path = $_SERVER["DOCUMENT_ROOT"] . $_SERVER["SCRIPT_NAME"];

if (is_file($path)) {
    // Handle static files with CORS
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    $corsExts = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ttf', 'woff', 'woff2'];
    
    if (in_array(strtolower($ext), $corsExts)) {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: *");
        
        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'ttf' => 'font/ttf',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
        ];
        
        if (isset($mimeTypes[strtolower($ext)])) {
            header("Content-Type: " . $mimeTypes[strtolower($ext)]);
        }
        
        readfile($path);
        return true;
    }
    
    return false;
} else {
    $_SERVER["SCRIPT_FILENAME"] = __DIR__ . '/index.php';

    require __DIR__ . "/index.php";
}
