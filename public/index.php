<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\App;

// [ 应用入口文件 ]

require __DIR__ . '/../vendor/autoload.php';

if (!function_exists('shouldServeFrontendApp')) {
    function shouldServeFrontendApp(string $indexHtml): bool
    {
        if (PHP_SAPI === 'cli' || !is_file($indexHtml)) {
            return false;
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (!in_array($method, ['GET', 'HEAD'], true)) {
            return false;
        }

        $requestPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $normalizedPath = '/' . ltrim($requestPath, '/');

        if (strpos($normalizedPath, '/api/') === 0 || $normalizedPath === '/api') {
            return false;
        }

        $segments = array_values(array_filter(explode('/', trim($normalizedPath, '/'))));
        $firstSegment = $segments[0] ?? '';
        if ($firstSegment !== '' && !in_array($firstSegment, ['api', 'user', 'order', 'detail', 'category', 'knowledge', 'payment', 'short-link', 'outside', 'auth', 'install', 'debug'], true) && count($segments) >= 2) {
            return false;
        }

        $passthroughPaths = [
            '/debug/docking',
            '/debug/fix',
            '/notification-test',
        ];
        if (in_array($normalizedPath, $passthroughPaths, true)) {
            return false;
        }

        $publicNavPaths = [
            '/order/search',
            '/order/list',
            '/order/detail',
            '/detail',
            '/category',
            '/knowledge',
            '/distribution/login',
            '/distribution/center',
        ];
        foreach ($publicNavPaths as $prefix) {
            if ($normalizedPath === $prefix || strpos($normalizedPath, $prefix . '/') === 0) {
                return false;
            }
        }

        $extension = pathinfo($normalizedPath, PATHINFO_EXTENSION);
        if ($extension !== '' && strtolower($extension) !== 'html') {
            return false;
        }

        $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
        if ($accept !== '' && strpos($accept, 'text/html') === false && strpos($accept, '*/*') === false) {
            return false;
        }

        return true;
    }
}

if (!function_exists('escapeHtmlContent')) {
    function escapeHtmlContent(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('upsertMetaTag')) {
    function upsertMetaTag(string $html, string $name, string $content): string
    {
        $escapedContent = escapeHtmlContent($content);
        $pattern = '/<meta\s+name=["\']' . preg_quote($name, '/') . '["\']\s+content=["\'][^"\']*["\']\s*\/?>/i';
        $replacement = '<meta name="' . $name . '" content="' . $escapedContent . '" />';

        if (preg_match($pattern, $html) === 1) {
            return preg_replace($pattern, $replacement, $html, 1) ?? $html;
        }

        return preg_replace('/<\/head>/i', "    {$replacement}\n  </head>", $html, 1) ?? $html;
    }
}

if (!function_exists('replaceFaviconLink')) {
    function replaceFaviconLink(string $html, string $iconUrl): string
    {
        $emptyIcon = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 1 1%22%3E%3C/svg%3E';
        $iconUrl = trim($iconUrl) !== '' ? trim($iconUrl) : $emptyIcon;

        $escapedUrl = escapeHtmlContent($iconUrl);
        $shortcutIcon = '<link rel="shortcut icon" type="image/x-icon" href="' . $escapedUrl . '" />';
        $icon = '<link rel="icon" href="' . $escapedUrl . '" />';

        $html = preg_replace('/<link[^>]*rel=["\'][^"\']*icon[^"\']*["\'][^>]*\/?>\s*/i', '', $html) ?? $html;

        return preg_replace('/<\/head>/i', "    {$shortcutIcon}\n    {$icon}\n  </head>", $html, 1) ?? $html;
    }
}

if (!function_exists('resolveAssetUrl')) {
    function resolveAssetUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (preg_match('/^(https?:)?\/\//i', $path) === 1 || str_starts_with($path, 'data:')) {
            return $path;
        }

        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('getFrontendSeoConfig')) {
    function getFrontendSeoConfig(): array
    {
        $seo = [
            'title' => 'Entropy',
            'description' => '',
            'keywords' => '',
            'icon' => '',
        ];

        try {
            $settings = \app\service\SettingService::getMany([
                'site_name',
                'site_description',
                'site_keywords',
                'site_icon',
            ]);

            $seo['title'] = trim((string)($settings['site_name'] ?? $seo['title'])) ?: 'Entropy';
            $seo['description'] = trim((string)($settings['site_description'] ?? ''));
            $seo['keywords'] = trim((string)($settings['site_keywords'] ?? ''));
            $seo['icon'] = resolveAssetUrl((string)($settings['site_icon'] ?? ''));
        } catch (\Throwable $exception) {
            error_log('Frontend SEO config load failed: ' . $exception->getMessage());
        }

        return $seo;
    }
}

if (!function_exists('injectFrontendSeo')) {
    function injectFrontendSeo(string $html): string
    {
        $seo = getFrontendSeoConfig();
        $title = escapeHtmlContent($seo['title']);

        if (preg_match('/<title>.*?<\/title>/is', $html) === 1) {
            $html = preg_replace('/<title>.*?<\/title>/is', "<title>{$title}</title>", $html, 1) ?? $html;
        } else {
            $html = preg_replace('/<\/head>/i', "    <title>{$title}</title>\n  </head>", $html, 1) ?? $html;
        }

        if ($seo['description'] !== '') {
            $html = upsertMetaTag($html, 'description', $seo['description']);
        }

        if ($seo['keywords'] !== '') {
            $html = upsertMetaTag($html, 'keywords', $seo['keywords']);
        }

        $html = replaceFaviconLink($html, $seo['icon']);

        return $html;
    }
}

if (!function_exists('serveFrontendApp')) {
    function serveFrontendApp(string $indexHtml): void
    {
        $content = file_get_contents($indexHtml);
        if ($content === false) {
            return;
        }

        $content = injectFrontendSeo($content);

        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
            return;
        }

        echo $content;
    }
}

if (!function_exists('resolveFrontendIndexFile')) {
    function resolveFrontendIndexFile(): ?string
    {
        $requestPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $normalizedPath = '/' . ltrim($requestPath, '/');

        $serverHandledPrefixes = [
            '/payment/scan',
            '/payment/mobile-launch',
            '/payment/result',
            '/payment/callback',
        ];

        $publicPagesPrefixes = [
            '/install',
            '/order/search',
            '/order/list',
            '/order/detail',
            '/order/complaint',
            '/detail',
            '/category',
            '/knowledge',
            '/distribution/login',
            '/distribution/center',
        ];
        $homePagesPrefixes = [
            '/payment',
            '/short-link',
            '/outside',
            '/404',
            '/500',
            '/maintenance',
        ];

        foreach ($serverHandledPrefixes as $prefix) {
            if ($normalizedPath === $prefix || strpos($normalizedPath, $prefix . '/') === 0) {
                return null;
            }
        }

        foreach ($publicPagesPrefixes as $prefix) {
            if ($normalizedPath === $prefix || strpos($normalizedPath, $prefix . '/') === 0) {
                return __DIR__ . '/pages/public/index.html';
            }
        }

        foreach ($homePagesPrefixes as $prefix) {
            if ($normalizedPath === $prefix || strpos($normalizedPath, $prefix . '/') === 0) {
                return __DIR__ . '/pages/home/index.html';
            }
        }

        return null;
    }
}

$app = new App();
$frontendIndexFile = resolveFrontendIndexFile();
if ($frontendIndexFile && shouldServeFrontendApp($frontendIndexFile)) {
    serveFrontendApp($frontendIndexFile);
    return;
}

if (PHP_SAPI !== 'cli' && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
    ini_set('zlib.output_compression', 'On');
    ini_set('zlib.output_compression_level', '5');
    header('Vary: Accept-Encoding');
}

// 执行HTTP应用并响应
$http = $app->http;

$response = $http->run();

$response->send();

$http->end($response);
