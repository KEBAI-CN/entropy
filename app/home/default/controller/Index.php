<?php

namespace app\home\default\controller;

use app\BaseController;
use app\service\HomeTemplateService;

class Index extends BaseController
{
    public function index()
    {
        $file = app_path() . 'home/default/view/index/index.html';
        $html = is_file($file) ? (string)file_get_contents($file) : '';
        $siteConfig = $this->siteConfig();

        $html = str_replace(
            [
                '<title>云寄售 - 企业级云寄售平台</title>',
                '<meta name="description" content="企业级云寄售平台，安全稳定的数字寄售系统。" />',
                '<meta name="keywords" content="云寄售,自动发卡,虚拟商品寄售" />',
                '<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />',
                'window.__SERVER_SITE_CONFIG__ = {};',
            ],
            [
                '<title>' . $this->escape($siteConfig['siteName']) . ' - 企业级云寄售平台</title>',
                '<meta name="description" content="' . $this->escape($siteConfig['siteDescription'] ?: '企业级云寄售平台，安全稳定的数字寄售系统。') . '" />',
                '<meta name="keywords" content="' . $this->escape($siteConfig['siteKeywords'] ?: '云寄售,自动发卡,虚拟商品寄售') . '" />',
                '<link rel="shortcut icon" type="image/x-icon" href="' . $this->escape($siteConfig['siteIcon'] ?: '/favicon.ico') . '" />',
                'window.__SERVER_SITE_CONFIG__ = ' . json_encode($siteConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';',
            ],
            $html
        );
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    private function siteConfig(): array
    {
        return [
            'siteName' => (string)(setting('site_name') ?: '云寄售'),
            'siteLogo' => $this->cleanUrl(setting('site_logo')),
            'siteIcon' => $this->cleanUrl(setting('site_icon') ?: setting('site_favicon') ?: setting('site_logo')),
            'siteLogoTitleEnabled' => (string)(setting('site_logo_title_enabled') ?? '1'),
            'siteKeywords' => (string)(setting('site_keywords') ?: ''),
            'siteDescription' => (string)(setting('site_description') ?: ''),
            'siteBeian' => (string)(setting('site_beian') ?: ''),
            'siteDianzeng' => (string)(setting('site_dianzeng') ?: ''),
            'siteCopyright' => (string)(setting('site_copyright') ?: ''),
            'contactEmail' => (string)(setting('contact_email') ?: ''),
            'contactPhone' => (string)(setting('contact_phone') ?: ''),
            'templateCode' => 'default',
            'templateParams' => HomeTemplateService::getConfig('default'),
        ];
    }

    private function cleanUrl($value): string
    {
        return trim(trim((string)($value ?: '')), "` \t\n\r\0\x0B");
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
