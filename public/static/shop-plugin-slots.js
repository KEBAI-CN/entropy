(function () {
  'use strict';

  var preload = window.__preloadedShopPublicIndex || {};
  var data = preload.data || {};
  var slotMap = data.plugin_slots || {};
  var loadedCss = {};
  var loadedJs = {};
  var mounted = {};

  var anchorSelectors = {
    'shop.home.header.after': ['.shop-header', '.shop-header-card', '.shop-header-container', '.yu-hero'],
    'shop.home.category.after': ['.shop-categories', '.shop-category-list', '.yu-category-row'],
    'shop.home.productList.before': ['.shop-product-list', '.shop-product-area', '.shop-content-body'],
    'shop.home.productCard.extra': ['.shop-product-card', '.shop-product-item'],
    'shop.order.confirm.extra': ['.order-modal .om-body', '.order-modal', '.om-body', '.arco-modal .om-body']
  };

  function hasItems(slot) {
    return Array.isArray(slotMap[slot]) && slotMap[slot].length > 0;
  }

  function loadCss(href) {
    if (!href || loadedCss[href]) return;
    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = href;
    document.head.appendChild(link);
    loadedCss[href] = true;
  }

  function loadJs(code, src) {
    if (!src) return Promise.resolve();
    var key = code + ':' + src;
    if (loadedJs[key]) return loadedJs[key];
    loadedJs[key] = new Promise(function (resolve, reject) {
      var script = document.createElement('script');
      script.src = src;
      script.async = true;
      script.onload = resolve;
      script.onerror = function () {
        reject(new Error('Failed to load plugin script: ' + src));
      };
      document.body.appendChild(script);
    });
    return loadedJs[key];
  }

  function normalizeSlotName(slot) {
    return String(slot || '').replace(/[^A-Za-z0-9_-]/g, '-');
  }

  function getProductContext(element) {
    var product = {};
    var id = element.getAttribute('data-product-id') || element.getAttribute('data-id') || '';
    if (!id) {
      var clickable = element.closest('[data-product-id],[data-id]');
      id = clickable ? (clickable.getAttribute('data-product-id') || clickable.getAttribute('data-id') || '') : '';
    }
    if (id) product.id = id;
    return product;
  }

  function getItemIdentity(item, index) {
    return normalizeSlotName(String(item.code || 'plugin') + '-' + String(item.key || item.id || item.js || index || '0'));
  }

  function createHost(slot, item, anchor, index) {
    var code = item.code || 'plugin';
    var itemIdentity = getItemIdentity(item, index);
    var key = slot + ':' + itemIdentity + ':' + index;
    if (anchor.getAttribute('data-plugin-slot-mounted-' + normalizeSlotName(slot) + '-' + itemIdentity)) {
      return null;
    }
    anchor.setAttribute('data-plugin-slot-mounted-' + normalizeSlotName(slot) + '-' + itemIdentity, '1');

    var host = document.createElement('div');
    host.className = 'shop-plugin-slot-host';
    host.setAttribute('data-plugin-slot', slot);
    host.setAttribute('data-plugin-code', code);
    host.id = 'shop-plugin-slot-' + normalizeSlotName(slot) + '-' + itemIdentity + '-' + index;

    var inner = document.createElement('div');
    inner.className = 'shop-plugin-slot-item';
    host.appendChild(inner);

    mounted[key] = mounted[key] || {};
    mounted[key].host = inner;
    return host;
  }

  function insertHost(slot, anchor, host) {
    if (!anchor || !host) return;
    if (slot === 'shop.home.productList.before') {
      anchor.parentNode && anchor.parentNode.insertBefore(host, anchor);
      return;
    }
    if (slot === 'shop.home.productCard.extra' || slot === 'shop.order.confirm.extra') {
      anchor.appendChild(host);
      return;
    }
    anchor.parentNode && anchor.parentNode.insertBefore(host, anchor.nextSibling);
  }

  function mountPlugin(slot, item, host, anchor) {
    if (!host || !item || !item.code || !item.js) return;
    loadCss(item.css);
    loadJs(item.code, item.js)
      .then(function () {
        var registry = window.EntropySlotPlugins || {};
        var renderer = registry[item.code];
        if (!renderer || typeof renderer.mount !== 'function') return;
        renderer.mount(host, item.payload || {}, {
          slot: slot,
          context: {
            shop: (data.shop || {}),
            categories: (data.categories || []),
            products: (data.products || {}),
            product: slot === 'shop.home.productCard.extra' ? getProductContext(anchor) : null,
            element: anchor
          },
          navigate: function (path) {
            if (path) window.location.href = path;
          }
        });
      })
      .catch(function (error) {
        console.error('[shop-plugin-slots] failed to mount ' + item.code, error);
      });
  }

  function findAnchors(slot) {
    var selectors = anchorSelectors[slot] || [];
    for (var i = 0; i < selectors.length; i += 1) {
      var nodes = Array.prototype.slice.call(document.querySelectorAll(selectors[i]));
      if (nodes.length > 0) return nodes;
    }
    return [];
  }

  function mountSlot(slot) {
    if (!hasItems(slot)) return;
    var anchors = findAnchors(slot);
    if (!anchors.length) return;
    var items = slotMap[slot];
    var targetAnchors = (slot === 'shop.home.productCard.extra' || slot === 'shop.order.confirm.extra')
      ? anchors
      : anchors.slice(0, 1);

    targetAnchors.forEach(function (anchor, anchorIndex) {
      items.forEach(function (item, itemIndex) {
        var host = createHost(slot, item, anchor, anchorIndex + '-' + itemIndex);
        if (!host) return;
        insertHost(slot, anchor, host);
        mountPlugin(slot, item, host.firstChild, anchor);
      });
    });
  }

  function mountAll() {
    Object.keys(anchorSelectors).forEach(mountSlot);
  }

  function boot() {
    mountAll();
    var observer = new MutationObserver(function () {
      mountAll();
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
