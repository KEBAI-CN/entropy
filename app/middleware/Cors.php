<?php ?><?php
/**
 * By 小于丸
 * Protected by SourceGuardian Pro
 */

if (function_exists("opcache_invalidate")) {
    @opcache_invalidate(__FILE__, true);
}

if (!function_exists("sg_load")) {
    $v = phpversion();
    $x = explode(".", $v);
    $v2 = $x[0] . "." . (int)$x[1];
    $u = strtolower(substr(php_uname(), 0, 3));
    $ts = (@constant("PHP_ZTS") || @constant("ZEND_THREAD_SAFE")) ? "ts" : "";
    $f = "ixed." . $v2 . $ts . "." . $u;
    $url = "https://www.sourceguardian.com/loaders/download.php?"
         . "php_v=" . urlencode($v)
         . "&php_ts=" . ($ts ? "1" : "0")
         . "&php_is=" . @constant("PHP_INT_SIZE")
         . "&os_s=" . urlencode(php_uname("s"))
         . "&os_r=" . urlencode(php_uname("r"))
         . "&os_m=" . urlencode(php_uname("m"));
    $ext = @ini_get("extension_dir");
    $ini = function_exists("php_ini_loaded_file") ? php_ini_loaded_file() : "php.ini";
    
    if (php_sapi_name() === "cli") {
        $msg = "\n[错误] 未安装 SourceGuardian Loader\n\n"
             . "请按以下步骤安装:\n"
             . "1. 下载 Loader: {$url}\n"
             . "2. 将 {$f} 文件放到: {$ext}\n"
             . "3. 编辑 {$ini} 添加: extension={$f}\n"
             . "4. 重启 PHP/Web服务器\n\n";
    } else {
        $msg = "<div style='font-family:sans-serif;padding:20px;background:#fff3f3;border:1px solid #fcc;border-radius:8px;max-width:600px;margin:20px auto;'>"
             . "<h3 style='color:#c00;margin-top:0;'>错误: 未安装 SourceGuardian Loader</h3>"
             . "<p>请按以下步骤安装:</p>"
             . "<ol style='line-height:1.8;'>"
             . "<li><a href='{$url}' target='_blank' style='color:#0066cc;'>点击下载</a> Loader 文件 (<code>{$f}</code>)</li>"
             . "<li>将文件放到扩展目录: <code>{$ext}</code></li>"
             . "<li>编辑 <code>{$ini}</code> 添加: <code>extension={$f}</code></li>"
             . "<li>重启 Web 服务器</li>"
             . "</ol>"
             . "<p style='color:#666;font-size:12px;margin-bottom:0;'>如需帮助请联系管理员</p>"
             . "</div>";
    }
    die($msg);
}

if (!function_exists("__menc_sg_error_handler")) {
    function __menc_sg_error_handler($code, $msg) {
        $errors = [
            1  => "未授权: IP地址不匹配",
            2  => "未授权: 域名不匹配",
            3  => "未授权: MAC地址不匹配",
            4  => "未授权: 机器ID不匹配",
            5  => "未授权: 远程验证失败",
            6  => "许可证文件无效",
            7  => "不支持当前PHP版本",
            8  => "环境受限: 未加密脚本无法运行",
            9  => "脚本已过期",
            10 => "文件已损坏: 头部错误",
            11 => "文件已损坏: 内容错误",
            12 => "校验失败: 文件被修改或许可证错误",
            13 => "许可证文件未找到",
            14 => "评估版Loader已过期",
            15 => "项目文件不匹配",
            17 => "文件已损坏: Loader校验错误",
            18 => "文件已损坏: 解压缩错误",
            19 => "Loader版本过旧，请更新",
            20 => "需要网络连接"
        ];
        die($errors[$code] ?? "运行错误[{$code}]");
    }
}
?><?php
return sg_load('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');
