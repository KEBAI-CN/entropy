(function () {
  'use strict'

  var config = window.__SHOP_CUSTOMER_SERVICE__ || {}
  var shopId = Number(config.shopId || 0)
  var state = {
    open: false,
    loading: false,
    sending: false,
    sessionId: 0,
    session: null,
    messages: [],
    products: [],
    orders: [],
    productPage: 1,
    orderPage: 1,
    productKeyword: '',
    orderKeyword: '',
    productHasMore: false,
    orderHasMore: false,
    loadingProducts: false,
    loadingOrders: false,
    drawerMode: '',
    pollTimer: null,
    toastTimer: null,
    overlayObserver: null,
    floatHidden: false,
  }
  var dom = {}

  function isEnabled() {
    return Number(config.enabled || 0) === 1 && shopId > 0
  }

  function resolveAssetUrl(value) {
    var raw = String(value || '').trim()
    if (!raw) return ''
    if (/^(https?:)?\/\//i.test(raw) || raw.indexOf('data:') === 0 || raw.indexOf('blob:') === 0) return raw
    return raw.charAt(0) === '/' ? raw : '/' + raw
  }

  function getVisitorId() {
    var key = 'shop_customer_service_visitor_id_v1'
    try {
      var existing = localStorage.getItem(key)
      if (existing) return existing
      var id = ''
      if (window.crypto && typeof window.crypto.randomUUID === 'function') {
        id = window.crypto.randomUUID()
      } else {
        id = 'v_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 12)
      }
      id = id.replace(/[^a-zA-Z0-9_-]/g, '').slice(0, 96)
      localStorage.setItem(key, id)
      return id
    } catch (e) {
      return 'v_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 10)
    }
  }

  function apiUrl(path, params) {
    var query = new URLSearchParams(params || {})
    return path + (query.toString() ? '?' + query.toString() : '')
  }

  function request(path, options) {
    options = options || {}
    var headers = options.headers || {}
    if (options.body && !(options.body instanceof FormData)) {
      headers['Content-Type'] = 'application/json'
    }
    return fetch(path, {
      method: options.method || 'GET',
      credentials: 'same-origin',
      headers: headers,
      body: options.body && !(options.body instanceof FormData) ? JSON.stringify(options.body) : options.body,
    }).then(function (res) {
      return res.json().catch(function () {
        return { code: res.ok ? 200 : res.status, msg: res.statusText || '请求失败' }
      })
    }).then(function (json) {
      if (Number(json.code) !== 200) {
        var err = new Error(json.msg || '请求失败')
        err.payload = json
        throw err
      }
      return json.data
    })
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;')
  }

  function formatTime(value) {
    var raw = String(value || '').trim()
    if (!raw) return ''
    var d = new Date(raw.replace(/-/g, '/'))
    if (Number.isNaN(d.getTime())) return raw
    var hh = String(d.getHours()).padStart(2, '0')
    var mm = String(d.getMinutes()).padStart(2, '0')
    return hh + ':' + mm
  }

  function normalizeMessage(item) {
    item = item || {}
    var sender = String(item.sender_type || 'buyer')
    if (sender !== 'buyer' && sender !== 'seller' && sender !== 'system') sender = 'buyer'
    var type = String(item.message_type || 'text') === 'image' ? 'image' : 'text'
    return {
      id: Number(item.id || 0),
      senderType: sender,
      messageType: type,
      content: String(item.content || ''),
      createTime: String(item.create_time || ''),
    }
  }

  function getShopSlug() {
    return String(config.shopSlug || '').trim()
  }

  function formatMoney(value) {
    var num = Number(value || 0)
    if (!Number.isFinite(num)) num = 0
    return num.toFixed(2).replace(/\.00$/, '')
  }

  function resolveOrderStatusText(status) {
    var val = Number(status)
    if (val === 1) return '已支付'
    if (val === 2) return '已完成'
    if (val === 3) return '已退款'
    if (val === 4) return '待发货'
    if (val === 5) return '运输中'
    if (val === -1) return '已取消'
    return '待支付'
  }

  function formatStockText(item) {
    item = item || {}
    var mode = Number(item.stock_display_mode || 0)
    var stock = Number(item.stock || 0)
    if (mode === 1) return '不限量'
    if (stock >= 999999) return '不限量'
    return stock > 0 ? String(stock) : '暂无库存'
  }

  function normalizeOrderCards(value) {
    var result = []
    if (Array.isArray(value)) {
      value.forEach(function (item) {
        if (typeof item === 'string') {
          if (item.trim()) result.push(item.trim())
          return
        }
        if (item && typeof item === 'object') {
          var content = item.content || item.card || item.value || ''
          if (String(content || '').trim()) result.push(String(content).trim())
        }
      })
      return result
    }
    if (typeof value === 'string') {
      var text = value.trim()
      if (!text) return []
      try {
        return normalizeOrderCards(JSON.parse(text))
      } catch (e) {
        return text.split('\n').map(function (item) {
          return item.trim()
        }).filter(Boolean)
      }
    }
    if (value && typeof value === 'object') {
      return normalizeOrderCards(value.cards || value.card || value.content || '')
    }
    return []
  }

  function getLineValue(lines, prefixes) {
    for (var i = 0; i < lines.length; i += 1) {
      for (var j = 0; j < prefixes.length; j += 1) {
        if (lines[i].indexOf(prefixes[j]) === 0) {
          var idx = lines[i].indexOf('：')
          return idx >= 0 ? lines[i].slice(idx + 1).trim() : ''
        }
      }
    }
    return ''
  }

  function parseOrderCard(content) {
    var text = String(content || '').trim().replace(/\r\n/g, '\n').replace(/\r/g, '\n').replace(/\\n/g, '\n')
    if (text.indexOf('【历史订单】') !== 0) return null
    var lines = text.split('\n').map(function (line) { return line.trim() })
    var cards = []
    var match = text.match(/卡密内容开始([\s\S]*?)卡密内容结束/)
    if (match && match[1]) {
      cards = match[1].split('\n').map(function (item) { return item.trim() }).filter(Boolean)
    }
    return {
      tradeNo: getLineValue(lines, ['订单号：']),
      productName: getLineValue(lines, ['商品：']),
      amount: getLineValue(lines, ['金额：']).replace(/^¥/, ''),
      status: getLineValue(lines, ['状态：']),
      createTime: getLineValue(lines, ['下单时间：']),
      cards: cards,
    }
  }

  function parseProductCard(content) {
    var text = String(content || '').trim().replace(/\r\n/g, '\n').replace(/\r/g, '\n')
    if (text.indexOf('【店铺商品】') !== 0) return null
    var lines = text.split('\n').map(function (line) { return line.trim() })
    return {
      name: getLineValue(lines, ['商品：']),
      price: getLineValue(lines, ['价格：']).replace(/^¥/, ''),
      stock: getLineValue(lines, ['库存：']),
      url: getLineValue(lines, ['商品链接：', '链接：']),
    }
  }

  function renderRichMessage(content) {
    var order = parseOrderCard(content)
    if (order) {
      var cardLines = order.cards.length
        ? '<div class="shop-cs-card__secret">' + order.cards.map(escapeHtml).join('<br>') + '</div>'
        : ''
      return [
        '<div class="shop-cs-card-msg">',
        '  <div class="shop-cs-card-msg__label">历史订单</div>',
        '  <div class="shop-cs-card-msg__title">' + escapeHtml(order.productName || '未知商品') + '</div>',
        '  <div class="shop-cs-card-msg__meta">订单号：' + escapeHtml(order.tradeNo || '-') + '</div>',
        '  <div class="shop-cs-card-msg__row"><span>金额</span><strong>¥' + escapeHtml(order.amount || '0') + '</strong></div>',
        '  <div class="shop-cs-card-msg__row"><span>状态</span><em>' + escapeHtml(order.status || '-') + '</em></div>',
        '  <div class="shop-cs-card-msg__meta">' + escapeHtml(order.createTime || '') + '</div>',
        cardLines,
        '</div>',
      ].join('')
    }
    var product = parseProductCard(content)
    if (product) {
      var url = product.url ? '<a class="shop-cs-card-msg__link" href="' + escapeHtml(product.url) + '" target="_blank" rel="noopener">查看商品</a>' : ''
      return [
        '<div class="shop-cs-card-msg">',
        '  <div class="shop-cs-card-msg__label">店铺商品</div>',
        '  <div class="shop-cs-card-msg__title">' + escapeHtml(product.name || '未知商品') + '</div>',
        '  <div class="shop-cs-card-msg__row"><span>价格</span><strong>¥' + escapeHtml(product.price || '0') + '</strong></div>',
        '  <div class="shop-cs-card-msg__row"><span>库存</span><em>' + escapeHtml(product.stock || '-') + '</em></div>',
        url,
        '</div>',
      ].join('')
    }
    return escapeHtml(content)
  }

  function showToast(text) {
    if (!dom.toast) return
    dom.toast.textContent = text
    dom.toast.classList.add('is-visible')
    if (state.toastTimer) window.clearTimeout(state.toastTimer)
    state.toastTimer = window.setTimeout(function () {
      dom.toast.classList.remove('is-visible')
    }, 1800)
  }

  function createFloatButton() {
    var button = document.createElement('button')
    button.type = 'button'
    button.className = 'shop-cs-float'
    button.setAttribute('aria-label', '店铺客服')
    var iconUrl = resolveAssetUrl(config.iconUrl || '')
    var label = String(config.label || '客服').trim()
    var icon = iconUrl
      ? '<img class="shop-cs-float__icon" src="' + escapeHtml(iconUrl) + '" alt="">'
      : '<svg class="shop-cs-float__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12a8 8 0 0 1 16 0"/><path d="M4 12v3a2 2 0 0 0 2 2h1v-7H6a2 2 0 0 0-2 2Z"/><path d="M20 12v3a2 2 0 0 1-2 2h-1v-7h1a2 2 0 0 1 2 2Z"/><path d="M13.5 19h2.5a4 4 0 0 0 4-4"/><path d="M9 19h.01"/></svg>'
    button.innerHTML = icon + (label ? '<span class="shop-cs-float__label">' + escapeHtml(label) + '</span>' : '')
    button.addEventListener('click', openPanel)
    document.body.appendChild(button)
    dom.floatButton = button
    updateFloatVisibility()
  }

  function isVisibleElement(el) {
    if (!el || el === dom.overlay || (dom.overlay && dom.overlay.contains(el))) return false
    var style = window.getComputedStyle(el)
    if (style.display === 'none' || style.visibility === 'hidden' || Number(style.opacity || 1) === 0) return false
    if (el.getAttribute('aria-hidden') === 'true') return false
    var rect = el.getBoundingClientRect()
    return rect.width > 0 && rect.height > 0
  }

  function hasPageOverlay() {
    var selectors = [
      '.arco-modal-wrapper',
      '.arco-modal-container',
      '.arco-modal',
      '.arco-modal-mask',
      '.arco-drawer-wrapper',
      '.arco-drawer-container',
      '.arco-drawer',
      '.arco-drawer-mask',
      '.arco-trigger-popup',
      '.shop-notice-modal-overlay.is-visible',
    ]
    for (var i = 0; i < selectors.length; i += 1) {
      var nodes = document.querySelectorAll(selectors[i])
      for (var j = 0; j < nodes.length; j += 1) {
        if (isVisibleElement(nodes[j])) return true
      }
    }
    return false
  }

  function updateFloatVisibility() {
    if (!dom.floatButton) return
    var shouldHide = hasPageOverlay()
    if (state.floatHidden === shouldHide) return
    state.floatHidden = shouldHide
    dom.floatButton.classList.toggle('is-hidden', shouldHide)
    dom.floatButton.setAttribute('aria-hidden', shouldHide ? 'true' : 'false')
  }

  function watchPageOverlays() {
    if (state.overlayObserver || !document.body || typeof MutationObserver === 'undefined') {
      updateFloatVisibility()
      return
    }
    var scheduled = false
    var scheduleUpdate = function () {
      if (scheduled) return
      scheduled = true
      window.requestAnimationFrame(function () {
        scheduled = false
        updateFloatVisibility()
      })
    }
    state.overlayObserver = new MutationObserver(scheduleUpdate)
    state.overlayObserver.observe(document.body, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['class', 'style', 'aria-hidden'],
    })
    window.addEventListener('resize', scheduleUpdate)
    updateFloatVisibility()
  }

  function createPanel() {
    var overlay = document.createElement('div')
    overlay.className = 'shop-cs-overlay'
    overlay.innerHTML = [
      '<section class="shop-cs-panel" role="dialog" aria-modal="true" aria-labelledby="shop-cs-title">',
      '  <header class="shop-cs-header">',
      '    <div class="shop-cs-avatar"></div>',
      '    <div class="shop-cs-title-wrap">',
      '      <h2 class="shop-cs-title" id="shop-cs-title">店铺客服</h2>',
      '      <div class="shop-cs-status"><span class="shop-cs-status__dot"></span><span class="shop-cs-status__text">连接中</span></div>',
      '    </div>',
      '    <button class="shop-cs-close" type="button" aria-label="关闭">×</button>',
      '  </header>',
      '  <div class="shop-cs-body"></div>',
      '  <footer class="shop-cs-footer">',
      '    <div class="shop-cs-tools">',
      '      <button class="shop-cs-tool" type="button" data-action="image">图片</button>',
      '      <button class="shop-cs-tool" type="button" data-action="order">订单</button>',
      '      <button class="shop-cs-tool" type="button" data-action="product">商品</button>',
      '      <input class="shop-cs-file" type="file" accept="image/*">',
      '    </div>',
      '    <textarea class="shop-cs-input" maxlength="1000" rows="3" placeholder="请输入咨询内容"></textarea>',
      '    <div class="shop-cs-actions">',
      '      <span class="shop-cs-tip">Enter 发送，Shift + Enter 换行</span>',
      '      <button class="shop-cs-send" type="button">发送</button>',
      '    </div>',
      '  </footer>',
      '</section>',
      '<section class="shop-cs-drawer" aria-hidden="true">',
      '  <header class="shop-cs-drawer__header">',
      '    <strong class="shop-cs-drawer__title">选择内容</strong>',
      '    <button class="shop-cs-drawer__close" type="button" aria-label="关闭">×</button>',
      '  </header>',
      '  <div class="shop-cs-drawer__search"></div>',
      '  <div class="shop-cs-drawer__body"></div>',
      '</section>',
    ].join('')
    document.body.appendChild(overlay)

    dom.overlay = overlay
    dom.panel = overlay.querySelector('.shop-cs-panel')
    dom.avatar = overlay.querySelector('.shop-cs-avatar')
    dom.title = overlay.querySelector('.shop-cs-title')
    dom.status = overlay.querySelector('.shop-cs-status')
    dom.statusText = overlay.querySelector('.shop-cs-status__text')
    dom.body = overlay.querySelector('.shop-cs-body')
    dom.input = overlay.querySelector('.shop-cs-input')
    dom.send = overlay.querySelector('.shop-cs-send')
    dom.file = overlay.querySelector('.shop-cs-file')
    dom.drawer = overlay.querySelector('.shop-cs-drawer')
    dom.drawerTitle = overlay.querySelector('.shop-cs-drawer__title')
    dom.drawerSearch = overlay.querySelector('.shop-cs-drawer__search')
    dom.drawerBody = overlay.querySelector('.shop-cs-drawer__body')

    var avatar = resolveAssetUrl(config.shopAvatar || '')
    dom.avatar.innerHTML = avatar
      ? '<img src="' + escapeHtml(avatar) + '" alt="">'
      : escapeHtml(String(config.shopName || '店').trim().slice(0, 1) || '店')
    dom.title.textContent = String(config.shopName || '店铺客服').trim() || '店铺客服'

    overlay.querySelector('.shop-cs-close').addEventListener('click', closePanel)
    overlay.addEventListener('click', function (event) {
      if (event.target === overlay && window.matchMedia('(max-width: 768px)').matches) {
        closePanel()
      }
    })
    dom.send.addEventListener('click', sendMessage)
    overlay.querySelectorAll('.shop-cs-tool').forEach(function (button) {
      button.addEventListener('click', function () {
        var action = button.getAttribute('data-action')
        if (action === 'image') {
          if (dom.file) dom.file.click()
          return
        }
        if (action === 'order' || action === 'product') {
          openDrawer(action)
        }
      })
    })
    if (dom.file) {
      dom.file.addEventListener('change', function () {
        var file = dom.file && dom.file.files && dom.file.files[0]
        if (file) sendImage(file)
        if (dom.file) dom.file.value = ''
      })
    }
    var drawerClose = overlay.querySelector('.shop-cs-drawer__close')
    if (drawerClose) drawerClose.addEventListener('click', closeDrawer)
    dom.input.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault()
        sendMessage()
      }
    })
  }

  function createToast() {
    var toast = document.createElement('div')
    toast.className = 'shop-cs-toast'
    document.body.appendChild(toast)
    dom.toast = toast
  }

  function renderStatus() {
    var session = state.session || {}
    var blacklisted = Number(session.is_blacklisted || 0) === 1
    var online = Boolean(session.seller_online)
    dom.status.classList.toggle('is-online', online && !blacklisted)
    if (blacklisted) {
      dom.statusText.textContent = '已被拉黑'
    } else if (online) {
      dom.statusText.textContent = '商家在线'
    } else {
      dom.statusText.textContent = '商家离线'
    }
    var disabled = blacklisted || state.sending
    dom.input.disabled = disabled
    dom.send.disabled = disabled
    if (dom.file) dom.file.disabled = disabled
    if (dom.overlay) {
      dom.overlay.querySelectorAll('.shop-cs-tool').forEach(function (button) {
        button.disabled = disabled
      })
    }
  }

  function renderMessages() {
    if (!dom.body) return
    var session = state.session || {}
    var html = ''
    if (Number(session.is_blacklisted || 0) === 1) {
      var reason = String(session.blacklist_reason || '').trim()
      html += '<div class="shop-cs-notice">您已被商家拉黑，当前无法继续发送消息' + (reason ? '。原因：' + escapeHtml(reason) : '') + '</div>'
    }
    if (state.loading) {
      html += '<div class="shop-cs-loading">正在连接客服...</div>'
    } else if (!state.messages.length) {
      html += '<div class="shop-cs-empty">暂无消息，发送第一条消息开始沟通</div>'
    } else {
      html += state.messages.map(function (msg) {
        var cls = msg.senderType === 'buyer' ? 'is-buyer' : (msg.senderType === 'system' ? 'is-system' : 'is-seller')
        var body = ''
        if (msg.messageType === 'image') {
          body = '<img class="shop-cs-image" src="' + escapeHtml(msg.content) + '" alt="图片消息">'
        } else {
          body = renderRichMessage(msg.content)
        }
        return [
          '<div class="shop-cs-message ' + cls + '">',
          '  <div>',
          '    <div class="shop-cs-bubble">' + body + '</div>',
          '    <div class="shop-cs-time">' + escapeHtml(formatTime(msg.createTime)) + '</div>',
          '  </div>',
          '</div>',
        ].join('')
      }).join('')
    }
    dom.body.innerHTML = html
    dom.body.scrollTop = dom.body.scrollHeight
  }

  function loadMessages(silent) {
    if (!shopId) return Promise.resolve()
    if (!silent) {
      state.loading = true
      renderMessages()
    }
    var visitorId = getVisitorId()
    return request(apiUrl('/api/customer_service/buyer/session', {
      shop_id: String(shopId),
      visitor_id: visitorId,
    })).then(function (session) {
      state.session = session || {}
      state.sessionId = Number(session && session.id || 0)
      return request(apiUrl('/api/customer_service/buyer/messages', {
        shop_id: String(shopId),
        session_id: String(state.sessionId || ''),
        limit: '200',
        visitor_id: visitorId,
      }))
    }).then(function (data) {
      state.session = data && data.session ? data.session : state.session
      state.messages = Array.isArray(data && data.messages) ? data.messages.map(normalizeMessage) : []
      if (state.sessionId) {
        request('/api/customer_service/buyer/read', {
          method: 'POST',
          body: {
            session_id: state.sessionId,
            visitor_id: visitorId,
          },
        }).catch(function () {})
      }
    }).catch(function (error) {
      if (!silent) showToast(error.message || '客服连接失败')
    }).finally(function () {
      state.loading = false
      renderStatus()
      renderMessages()
    })
  }

  function openPanel() {
    updateFloatVisibility()
    if (!dom.overlay) createPanel()
    state.open = true
    dom.overlay.classList.add('is-open')
    if (window.matchMedia('(max-width: 768px)').matches) {
      document.documentElement.classList.add('shop-cs-lock')
    }
    loadMessages(false).then(startPolling)
    window.setTimeout(function () {
      if (dom.input && !dom.input.disabled) dom.input.focus()
    }, 220)
  }

  function closePanel() {
    state.open = false
    closeDrawer()
    if (dom.overlay) dom.overlay.classList.remove('is-open')
    document.documentElement.classList.remove('shop-cs-lock')
    if (document.activeElement && dom.overlay && dom.overlay.contains(document.activeElement)) {
      document.activeElement.blur()
    }
    stopPolling()
    window.setTimeout(updateFloatVisibility, 220)
  }

  function startPolling() {
    stopPolling()
    var seconds = Number(config.refreshInterval || 8)
    var delay = Math.max(1000, Math.min(60000, Math.floor(seconds) * 1000))
    state.pollTimer = window.setInterval(function () {
      if (state.open) loadMessages(true)
    }, delay)
  }

  function stopPolling() {
    if (state.pollTimer) {
      window.clearInterval(state.pollTimer)
      state.pollTimer = null
    }
  }

  function validateMessage(text) {
    var value = String(text || '').replace(/\r\n/g, '\n').trim()
    if (!value) return { valid: false, message: '请输入咨询内容' }
    if (value.length > 1000) return { valid: false, message: '消息长度不能超过1000字符' }
    return { valid: true, content: value }
  }

  function ensureCanSend() {
    if (state.sending) return
    if (state.session && Number(state.session.is_blacklisted || 0) === 1) {
      showToast('您已被商家拉黑，无法继续发送消息')
      return false
    }
    return true
  }

  function setSending(value) {
    state.sending = Boolean(value)
    renderStatus()
  }

  function sendCustomerMessage(content, messageType) {
    if (!ensureCanSend()) return Promise.resolve(null)
    setSending(true)
    return request('/api/customer_service/buyer/send', {
      method: 'POST',
      body: {
        shop_id: shopId,
        visitor_id: getVisitorId(),
        content: content,
        message_type: messageType || 'text',
      },
    }).then(function (message) {
      if (message) state.messages.push(normalizeMessage(message))
      renderMessages()
      return loadMessages(true).then(function () {
        return message
      })
    }).catch(function (error) {
      showToast(error.message || '发送失败，请稍后重试')
      return null
    }).finally(function () {
      setSending(false)
    })
  }

  function sendMessage() {
    if (!ensureCanSend()) return
    var validation = validateMessage(dom.input && dom.input.value)
    if (!validation.valid) {
      showToast(validation.message)
      return
    }
    sendCustomerMessage(validation.content, 'text').then(function (message) {
      if (message && dom.input) dom.input.value = ''
    })
  }

  function sendImage(file) {
    if (!ensureCanSend()) return
    if (!file || !/^image\//i.test(String(file.type || ''))) {
      showToast('请选择图片文件')
      return
    }
    var formData = new FormData()
    formData.append('file', file)
    setSending(true)
    request('/api/upload/image', {
      method: 'POST',
      body: formData,
    }).then(function (data) {
      var url = data && (data.url || data.full_url || data.path)
      if (!url) throw new Error('图片上传失败')
      setSending(false)
      return sendCustomerMessage(String(url), 'image')
    }).catch(function (error) {
      showToast(error.message || '图片发送失败')
    }).finally(function () {
      if (state.sending) setSending(false)
    })
  }

  function closeDrawer() {
    state.drawerMode = ''
    if (dom.drawer) {
      dom.drawer.classList.remove('is-open')
      dom.drawer.setAttribute('aria-hidden', 'true')
    }
  }

  function getDrawerKeyword() {
    if (state.drawerMode === 'order') return state.orderKeyword
    if (state.drawerMode === 'product') return state.productKeyword
    return ''
  }

  function setDrawerKeyword(value) {
    if (state.drawerMode === 'order') state.orderKeyword = value
    if (state.drawerMode === 'product') state.productKeyword = value
  }

  function normalizeListResponse(data) {
    data = data || {}
    var records = []
    if (Array.isArray(data.records)) records = data.records
    else if (Array.isArray(data.data)) records = data.data
    else if (Array.isArray(data.list)) records = data.list
    else if (Array.isArray(data)) records = data
    var current = Number(data.current || data.current_page || 1)
    var size = Number(data.size || data.per_page || records.length || 10)
    var total = Number(data.total || 0)
    var hasMore = data.has_more !== undefined ? Boolean(data.has_more) : (total > 0 ? current * size < total : records.length >= size)
    return { records: records, current: current, size: size, total: total, hasMore: hasMore }
  }

  function renderDrawer() {
    if (!dom.drawer || !dom.drawerBody || !dom.drawerTitle || !dom.drawerSearch) return
    var mode = state.drawerMode
    if (!mode) return
    var isOrder = mode === 'order'
    var loading = isOrder ? state.loadingOrders : state.loadingProducts
    var records = isOrder ? state.orders : state.products
    if (isOrder && state.orderKeyword) {
      var keyword = state.orderKeyword.toLowerCase()
      records = records.filter(function (item) {
        return [
          item.trade_no,
          item.contact,
          item.product_name,
          item.product_title,
        ].some(function (value) {
          return String(value || '').toLowerCase().indexOf(keyword) >= 0
        })
      })
    }
    var hasMore = isOrder ? state.orderHasMore : state.productHasMore
    dom.drawerTitle.textContent = isOrder ? '选择历史订单' : '选择店铺商品'
    dom.drawerSearch.innerHTML = [
      '<div class="shop-cs-search-row">',
      '  <input class="shop-cs-search-input" type="search" value="' + escapeHtml(getDrawerKeyword()) + '" placeholder="' + (isOrder ? '搜索订单号或联系方式' : '搜索商品名称') + '">',
      '  <button class="shop-cs-search-button" type="button">搜索</button>',
      '</div>',
    ].join('')

    var html = ''
    if (loading && records.length === 0) {
      html = '<div class="shop-cs-picker-empty">正在加载...</div>'
    } else if (!records.length) {
      html = '<div class="shop-cs-picker-empty">' + (isOrder ? '暂无可发送的历史订单' : '暂无可发送的商品') + '</div>'
    } else if (isOrder) {
      html = records.map(function (item, index) {
        var tradeNo = String(item.trade_no || '').trim()
        var title = String(item.product_name || item.product_title || '未知商品')
        return [
          '<button class="shop-cs-picker-item" type="button" data-index="' + index + '">',
          '  <div class="shop-cs-picker-item__main">',
          '    <div class="shop-cs-picker-item__title">' + escapeHtml(title) + '</div>',
          '    <div class="shop-cs-picker-item__meta">' + escapeHtml(tradeNo || '-') + '</div>',
          '    <div class="shop-cs-picker-item__meta">' + escapeHtml(String(item.create_time || '')) + '</div>',
          '  </div>',
          '  <div class="shop-cs-picker-item__side">',
          '    <strong>¥' + escapeHtml(formatMoney(item.total_price || item.amount || 0)) + '</strong>',
          '    <span>' + escapeHtml(resolveOrderStatusText(item.status)) + '</span>',
          '  </div>',
          '</button>',
        ].join('')
      }).join('')
    } else {
      html = records.map(function (item, index) {
        var cover = resolveAssetUrl(item.cover_image || item.cover || '')
        var coverHtml = cover ? '<img src="' + escapeHtml(cover) + '" alt="">' : '<span>' + escapeHtml(String(item.name || '商').slice(0, 1) || '商') + '</span>'
        return [
          '<button class="shop-cs-picker-item" type="button" data-index="' + index + '">',
          '  <div class="shop-cs-picker-cover">' + coverHtml + '</div>',
          '  <div class="shop-cs-picker-item__main">',
          '    <div class="shop-cs-picker-item__title">' + escapeHtml(item.name || '未命名商品') + '</div>',
          '    <div class="shop-cs-picker-item__meta">库存：' + escapeHtml(formatStockText(item)) + '</div>',
          '  </div>',
          '  <div class="shop-cs-picker-item__side"><strong>¥' + escapeHtml(formatMoney(item.price || 0)) + '</strong></div>',
          '</button>',
        ].join('')
      }).join('')
    }
    if (hasMore || loading) {
      html += '<button class="shop-cs-load-more" type="button" ' + (loading ? 'disabled' : '') + '>' + (loading ? '加载中...' : '加载更多') + '</button>'
    }
    dom.drawerBody.innerHTML = html

    var searchInput = dom.drawerSearch.querySelector('.shop-cs-search-input')
    var searchButton = dom.drawerSearch.querySelector('.shop-cs-search-button')
    if (searchButton) {
      searchButton.addEventListener('click', function () {
        setDrawerKeyword(searchInput ? searchInput.value.trim() : '')
        loadDrawerItems(true)
      })
    }
    if (searchInput) {
      searchInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
          setDrawerKeyword(searchInput.value.trim())
          loadDrawerItems(true)
        }
      })
    }
    dom.drawerBody.querySelectorAll('.shop-cs-picker-item').forEach(function (button) {
      button.addEventListener('click', function () {
        var index = Number(button.getAttribute('data-index') || -1)
        var item = records[index]
        if (!item) return
        if (isOrder) sendOrderCard(item)
        else sendProductCard(item)
      })
    })
    var more = dom.drawerBody.querySelector('.shop-cs-load-more')
    if (more) {
      more.addEventListener('click', function () {
        loadDrawerItems(false)
      })
    }
  }

  function openDrawer(mode) {
    if (!ensureCanSend()) return
    state.drawerMode = mode
    if (dom.drawer) {
      dom.drawer.classList.add('is-open')
      dom.drawer.setAttribute('aria-hidden', 'false')
    }
    loadDrawerItems(true)
  }

  function loadDrawerItems(refresh) {
    if (state.drawerMode === 'order') return loadOrders(refresh)
    if (state.drawerMode === 'product') return loadProducts(refresh)
    return Promise.resolve()
  }

  function loadOrders(refresh) {
    if (state.loadingOrders) return Promise.resolve()
    if (refresh) {
      state.orderPage = 1
      state.orders = []
    } else if (!state.orderHasMore) {
      return Promise.resolve()
    }
    state.loadingOrders = true
    renderDrawer()
    return request(apiUrl('/api/order/query', {
      keyword: '',
      fingerprint: getVisitorId(),
      page: String(state.orderPage),
      size: '10',
      is_auto: '1',
    })).then(function (data) {
      var res = normalizeListResponse(data)
      state.orders = refresh ? res.records : state.orders.concat(res.records)
      state.orderPage = res.current + 1
      state.orderHasMore = res.hasMore
    }).catch(function (error) {
      showToast(error.message || '历史订单加载失败')
    }).finally(function () {
      state.loadingOrders = false
      renderDrawer()
    })
  }

  function loadProducts(refresh) {
    if (state.loadingProducts) return Promise.resolve()
    var slug = getShopSlug()
    if (!slug) {
      showToast('店铺信息缺失，无法加载商品')
      return Promise.resolve()
    }
    if (refresh) {
      state.productPage = 1
      state.products = []
    } else if (!state.productHasMore) {
      return Promise.resolve()
    }
    state.loadingProducts = true
    renderDrawer()
    return request(apiUrl('/api/shop/' + encodeURIComponent(slug) + '/products', {
      current: String(state.productPage),
      size: '10',
      name: state.productKeyword,
      include_stock: '1',
    })).then(function (data) {
      var res = normalizeListResponse(data)
      state.products = refresh ? res.records : state.products.concat(res.records)
      state.productPage = res.current + 1
      state.productHasMore = res.hasMore
    }).catch(function (error) {
      showToast(error.message || '商品加载失败')
    }).finally(function () {
      state.loadingProducts = false
      renderDrawer()
    })
  }

  function sendOrderCard(item) {
    if (!ensureCanSend()) return
    var tradeNo = String(item && item.trade_no || '').trim()
    if (!tradeNo) {
      showToast('订单信息异常')
      return
    }
    var cards = Number(item.status) === 1 ? normalizeOrderCards(item.cards || item.card_keys || item.notify_info) : []
    var lines = [
      '【历史订单】',
      '订单号：' + tradeNo,
      '商品：' + String(item.product_name || item.product_title || '未知商品'),
      '金额：¥' + formatMoney(item.total_price || item.amount || 0),
      '状态：' + resolveOrderStatusText(item.status),
      '下单时间：' + String(item.create_time || '-'),
    ]
    if (cards.length > 0) {
      lines.push('卡密内容开始')
      cards.forEach(function (card) { lines.push(card) })
      lines.push('卡密内容结束')
    }
    sendCustomerMessage(lines.join('\n'), 'text').then(function (message) {
      if (message) closeDrawer()
    })
  }

  function sendProductCard(item) {
    if (!ensureCanSend()) return
    var key = String((item && (item.uuid || item.id)) || '').trim()
    var name = String((item && item.name) || '').trim()
    if (!key || !name) {
      showToast('商品信息异常')
      return
    }
    var url = window.location.origin + '/detail/' + encodeURIComponent(key)
    var lines = [
      '【店铺商品】',
      '商品：' + name,
      '价格：¥' + formatMoney(item.price || 0),
      '库存：' + formatStockText(item),
      '商品链接：' + url,
    ]
    sendCustomerMessage(lines.join('\n'), 'text').then(function (message) {
      if (message) closeDrawer()
    })
  }

  function init() {
    if (!isEnabled()) return
    createFloatButton()
    createToast()
    watchPageOverlays()
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init)
  } else {
    init()
  }
})()
