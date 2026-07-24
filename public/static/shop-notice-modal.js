(function () {
  'use strict'

  var config = window.__SHOP_NOTICE_MODAL__ || {}
  var platformNotice = config.platformNotice || {}
  var shopPopup = config.shopPopup || {}
  var queue = []
  var timer = null

  function stripHtml(value) {
    var div = document.createElement('div')
    div.innerHTML = decodeHtml(String(value || ''))
    return (div.textContent || div.innerText || '').trim()
  }

  function hasContent(value) {
    return stripHtml(value).length > 0
  }

  function decodeHtml(value) {
    var current = String(value || '')
    for (var i = 0; i < 3; i += 1) {
      if (current.indexOf('&') === -1) break
      var textarea = document.createElement('textarea')
      textarea.innerHTML = current
      var next = textarea.value
      if (next === current) break
      current = next
    }
    return current
  }

  function createOverlay() {
    var overlay = document.createElement('div')
    overlay.className = 'shop-notice-modal-overlay'
    overlay.innerHTML = [
      '<div class="shop-notice-modal" role="dialog" aria-modal="true" aria-labelledby="shop-notice-modal-title">',
      '  <div class="shop-notice-modal__header">',
      '    <h2 class="shop-notice-modal__title" id="shop-notice-modal-title"></h2>',
      '    <button class="shop-notice-modal__close" type="button" aria-label="关闭">×</button>',
      '  </div>',
      '  <div class="shop-notice-modal__body"></div>',
      '  <div class="shop-notice-modal__footer">',
      '    <span class="shop-notice-modal__hint"></span>',
      '    <button class="shop-notice-modal__button" type="button">我已知晓</button>',
      '  </div>',
      '</div>',
    ].join('')
    document.body.appendChild(overlay)
    return overlay
  }

  function closeCurrent(overlay) {
    if (timer) {
      window.clearInterval(timer)
      timer = null
    }
    overlay.classList.remove('is-visible')
    document.documentElement.classList.remove('shop-notice-modal-lock')
    window.setTimeout(function () {
      if (overlay.parentNode) {
        overlay.parentNode.removeChild(overlay)
      }
      showNext()
    }, 180)
  }

  function showNext() {
    var item = queue.shift()
    if (!item) return

    var overlay = createOverlay()
    var title = overlay.querySelector('.shop-notice-modal__title')
    var body = overlay.querySelector('.shop-notice-modal__body')
    var button = overlay.querySelector('.shop-notice-modal__button')
    var closeButton = overlay.querySelector('.shop-notice-modal__close')
    var hint = overlay.querySelector('.shop-notice-modal__hint')
    var countdown = Math.max(0, Math.floor(Number(item.countdown || 0)))

    title.textContent = item.title || '公告'
    body.innerHTML = decodeHtml(item.content || '')

    function setClosable(closable) {
      button.disabled = !closable
      button.textContent = closable ? '我已知晓' : '请等待倒计时结束'
      closeButton.classList.toggle('is-visible', !!closable)
      hint.textContent = countdown > 0 ? countdown + 's' : ''
    }

    function requestClose() {
      if (button.disabled) return
      closeCurrent(overlay)
    }

    closeButton.addEventListener('click', requestClose)
    button.addEventListener('click', requestClose)
    overlay.addEventListener('click', function (event) {
      if (event.target === overlay && !button.disabled) {
        requestClose()
      }
    })

    setClosable(countdown <= 0)
    if (countdown > 0) {
      timer = window.setInterval(function () {
        countdown -= 1
        if (countdown <= 0) {
          countdown = 0
          window.clearInterval(timer)
          timer = null
          setClosable(true)
          return
        }
        setClosable(false)
      }, 1000)
    }

    document.documentElement.classList.add('shop-notice-modal-lock')
    window.requestAnimationFrame(function () {
      overlay.classList.add('is-visible')
    })
  }

  function init() {
    if (hasContent(platformNotice.content)) {
      queue.push({
        title: platformNotice.title || '平台须知',
        content: platformNotice.content,
        countdown: platformNotice.countdown || 0,
      })
    }

    if (Number(shopPopup.enabled) === 1 && hasContent(shopPopup.content)) {
      queue.push({
        title: shopPopup.title || '店铺公告',
        content: shopPopup.content,
        countdown: 0,
      })
    }

    showNext()
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init)
  } else {
    init()
  }
})()
