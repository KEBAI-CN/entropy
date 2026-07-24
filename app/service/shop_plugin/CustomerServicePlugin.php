<?php
namespace app\service\shop_plugin;

class CustomerServicePlugin implements ShopPluginInterface
{
    public function getCode(): string
    {
        return 'customer_service';
    }

    public function getName(): string
    {
        return '客服浮窗';
    }

    public function getDescription(): string
    {
        return '在店铺首页展示客服悬浮入口和买家聊天窗口';
    }

    public function getType(): string
    {
        return 'widget';
    }

    public function getEntry(): string
    {
        return '/api/shop/effects/customer_service';
    }

    public function getDefaultConfig(): array
    {
        return [
            'label' => '',
            'icon_url' => '',
            'refresh_interval' => 8,
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            [
                'key' => 'label',
                'label' => '按钮文案',
                'type' => 'text',
                'placeholder' => '默认读取系统客服按钮文案',
            ],
            [
                'key' => 'icon_url',
                'label' => '按钮图标 URL',
                'type' => 'text',
                'placeholder' => '默认读取系统客服悬浮图标',
            ],
            [
                'key' => 'refresh_interval',
                'label' => '消息刷新间隔',
                'type' => 'number',
                'min' => 1,
                'max' => 60,
                'step' => 1,
                'default' => 8,
            ],
        ];
    }

    private function isSwitchEnabled($value, bool $default = true): bool
    {
        if ($value === null) {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return ((int)$value === 1);
        }

        $normalized = strtolower(trim((string)$value));
        if ($normalized === '') {
            return false;
        }

        return in_array($normalized, ['1', 'true', 'on', 'yes', 'enabled'], true);
    }

    public function renderScript(array $config): string
    {
        $defaultConfig = $this->getDefaultConfig();
        $config = array_merge($defaultConfig, $config);

        $enabled = $this->isSwitchEnabled(setting('customer_service_status'), true)
            && $this->isSwitchEnabled(setting('customer_service_entry_enabled'), true);

        $systemIconImage = trim((string)(setting('customer_service_float_icon_image') ?: ''));
        $systemIconUrl = trim((string)(setting('customer_service_float_icon_url') ?: ''));
        $iconUrl = trim((string)($config['icon_url'] ?? ''));
        if ($iconUrl === '') {
            $iconUrl = $systemIconImage !== '' ? $systemIconImage : $systemIconUrl;
        }

        $label = trim((string)($config['label'] ?? ''));
        if ($label === '') {
            $label = trim((string)(setting('customer_service_float_label') ?: '客服'));
        }

        $refreshInterval = max(1, min(60, (int)($config['refresh_interval'] ?? setting('customer_service_refresh_interval') ?: 8)));

        $runtimeConfig = [
            'enabled' => $enabled ? 1 : 0,
            'label' => $label,
            'iconUrl' => $iconUrl,
            'refreshInterval' => $refreshInterval,
            'assetVersion' => '20260630_cs_clickfix1',
        ];
        $runtimeConfigJson = json_encode($runtimeConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}';

        return <<<JS
(function(){
  var pluginConfig = {$runtimeConfigJson};
  function cleanupCustomerServicePlugin() {
    var script = document.getElementById('shop-customer-service-plugin-script');
    if (script && script.parentNode) script.parentNode.removeChild(script);
    var styles = document.querySelectorAll('link[data-shop-customer-service-plugin-css="1"]');
    for (var i = 0; i < styles.length; i += 1) {
      if (styles[i].parentNode) styles[i].parentNode.removeChild(styles[i]);
    }
    var nodes = document.querySelectorAll('.shop-cs-float,.shop-cs-overlay,.shop-cs-toast');
    for (var j = 0; j < nodes.length; j += 1) {
      if (nodes[j].parentNode) nodes[j].parentNode.removeChild(nodes[j]);
    }
    document.documentElement.classList.remove('shop-cs-lock');
    document.body && document.body.classList.remove('shop-cs-lock');
    delete window.__SHOP_CUSTOMER_SERVICE__;
    window.__shopCustomerServicePluginLoaded = false;
  }
  if (Number(pluginConfig.enabled || 0) !== 1) {
    cleanupCustomerServicePlugin();
    return;
  }
  if (window.__shopCustomerServicePluginLoaded) return;
  window.__shopCustomerServicePluginLoaded = true;

  function resolveShopPayload() {
    var preload = window.__preloadedShopPublicIndex || {};
    var data = preload.data || preload || {};
    return data.shop || {};
  }

  function normalizeUrl(value) {
    var raw = String(value || '').trim();
    if (!raw) return '';
    if (/^(https?:)?\\/\\//i.test(raw) || raw.indexOf('data:') === 0 || raw.indexOf('blob:') === 0) return raw;
    return raw.charAt(0) === '/' ? raw : '/' + raw;
  }

  function loadCss(href) {
    if (!href) return;
    var exists = document.querySelector('link[data-shop-customer-service-plugin-css="1"]');
    if (exists) return;
    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = href;
    link.setAttribute('data-shop-customer-service-plugin-css', '1');
    document.head.appendChild(link);
  }

  function loadScript(src) {
    if (!src) return;
    if (document.getElementById('shop-customer-service-plugin-script')) return;
    var script = document.createElement('script');
    script.id = 'shop-customer-service-plugin-script';
    script.src = src;
    script.defer = true;
    document.body.appendChild(script);
  }

  function boot() {
    var shop = resolveShopPayload();
    var shopId = Number(shop.id || shop.__original_id || 0);
    if (!shopId) {
      window.__shopCustomerServicePluginLoaded = false;
      return;
    }
    var version = String(pluginConfig.assetVersion || '1');
    window.__SHOP_CUSTOMER_SERVICE__ = {
      enabled: 1,
      shopId: shopId,
      shopSlug: String(shop.slug || ''),
      shopName: String(shop.name || '店铺客服'),
      shopAvatar: normalizeUrl(shop.avatar || ''),
      label: String(pluginConfig.label || '客服'),
      iconUrl: normalizeUrl(pluginConfig.iconUrl || ''),
      refreshInterval: Math.max(1, Math.min(60, Number(pluginConfig.refreshInterval || 8)))
    };
    loadCss('/static/shop-customer-service.css?v=' + encodeURIComponent(version));
    loadScript('/static/shop-customer-service.js?v=' + encodeURIComponent(version));
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }

  window.__shopPluginCleanup_customer_service = cleanupCustomerServicePlugin;
})();
JS;
    }
}
