document.documentElement.setAttribute('data-order-theme', 'simple_order')

const widgets = root.querySelectorAll('[data-ext-order-widget]')
const COMPLAINT_TYPES = ['卡密无效', '卡密错误', '卡密已使用', '商品与描述不符', '其他问题']

const getComplaintStatusText = (status) => {
  const map = { 0: '待处理', 1: '处理中', 2: '商家胜诉', 3: '买家胜诉' }
  return map[Number(status)] || '未知'
}

const getOrderStatusText = (status) => {
  if (Number(status) === 1) return '已支付'
  if (Number(status) === 4) return '待发货'
  if (Number(status) === 3) return '已退款'
  return '待支付'
}

const getOrderStatusClass = (status) => {
  if (Number(status) === 1) return 'order-status-paid'
  if (Number(status) === 4) return 'order-status-pending'
  if (Number(status) === 3) return 'order-status-refunded'
  return 'order-status-pending'
}

const getUserTypeText = (userType) => {
  const map = { buyer: '买家', seller: '商家', admin: '管理员' }
  return map[String(userType || '')] || String(userType || '未知')
}

const parseDateTime = (value) => {
  if (!value) return Date.now()
  if (typeof value === 'number') return value < 10000000000 ? value * 1000 : value
  const ts = Date.parse(String(value).replace(/-/g, '/'))
  return Number.isNaN(ts) ? Date.now() : ts
}

const getComplaintCountdown = (startTime) => {
  const timeoutSeconds = 4 * 3600
  const elapsed = Math.floor((Date.now() - parseDateTime(startTime)) / 1000)
  const remain = Math.max(0, timeoutSeconds - elapsed)
  const h = String(Math.floor(remain / 3600)).padStart(2, '0')
  const m = String(Math.floor((remain % 3600) / 60)).padStart(2, '0')
  const s = String(remain % 60).padStart(2, '0')
  return `${h}:${m}:${s}`
}

const escapeHtml = (value) =>
  String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')

const ensureComplaintModal = () => {
  let modal = document.getElementById('simple-order-complaint-modal')
  if (modal) return modal
  modal = document.createElement('div')
  modal.id = 'simple-order-complaint-modal'
  modal.style.cssText =
    'position:fixed;inset:0;background:rgba(0,0,0,.45);display:none;align-items:center;justify-content:center;z-index:9999;padding:16px;'
  modal.innerHTML =
    '<div style="width:100%;max-width:520px;background:#fff;border-radius:12px;padding:16px;">' +
    '<div style="font-size:16px;font-weight:600;color:#111827;">订单投诉</div>' +
    '<div style="margin-top:10px;display:flex;flex-direction:column;gap:10px;">' +
    '<input data-field="tradeNo" type="text" readonly style="height:38px;border:1px solid #d1d5db;border-radius:8px;padding:0 10px;background:#f9fafb;" />' +
    '<select data-field="type" style="height:38px;border:1px solid #d1d5db;border-radius:8px;padding:0 10px;background:#fff;">' +
    '<option value="">请选择投诉类型</option>' +
    COMPLAINT_TYPES.map((item) => '<option value="' + item + '">' + item + '</option>').join('') +
    '</select>' +
    '<textarea data-field="reason" placeholder="投诉原因" style="min-height:88px;border:1px solid #d1d5db;border-radius:8px;padding:8px 10px;resize:vertical;"></textarea>' +
    '<input data-field="contact" type="text" placeholder="联系方式（邮箱或手机号）" style="height:38px;border:1px solid #d1d5db;border-radius:8px;padding:0 10px;" />' +
    '<input data-field="password" type="password" placeholder="投诉查询密码" style="height:38px;border:1px solid #d1d5db;border-radius:8px;padding:0 10px;" />' +
    '<div data-field="error" style="color:#dc2626;font-size:12px;min-height:18px;"></div>' +
    '<div style="display:flex;justify-content:flex-end;gap:8px;">' +
    '<button data-action="cancel" type="button" style="height:34px;padding:0 14px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#374151;">取消</button>' +
    '<button data-action="submit" type="button" style="height:34px;padding:0 14px;border:none;border-radius:8px;background:#2563eb;color:#fff;">提交投诉</button>' +
    '</div>' +
    '</div>' +
    '</div>'
  document.body.appendChild(modal)
  modal.addEventListener('click', (evt) => {
    if (evt.target === modal) modal.style.display = 'none'
  })
  modal.querySelector('[data-action="cancel"]')?.addEventListener('click', () => {
    modal.style.display = 'none'
  })
  return modal
}

widgets.forEach((widget) => {
  const input = widget.querySelector('[data-field="keyword"]')
  const submitBtn = widget.querySelector('[data-action="submit"]')
  const errorBox = widget.querySelector('[data-field="error"]')
  const resultBox = widget.querySelector('[data-field="result"]')
  const complaintStatusMap = {}
  const orderMap = {}
  const canRetryComplaint = (order) => {
    if (!order?.complaint) return false
    const status = Number(order?.complaint?.status)
    if (status !== 2) return false
    const count = Number(order?.complaint_count || 1)
    return count < 2
  }
  const fingerprintKey = 'ext_order_fingerprint_v2'
  const fingerprintLoaderKey = '__ext_fp_loader_local__'
  const loadFingerprintLibrary = async () => {
    if (window.FingerprintJS) return window.FingerprintJS
    if (window[fingerprintLoaderKey]) return window[fingerprintLoaderKey]
    window[fingerprintLoaderKey] = new Promise((resolve, reject) => {
      const script = document.createElement('script')
      script.src = '/static/vendor/fingerprintjs/fp.min.js'
      script.async = true
      script.onload = () => resolve(window.FingerprintJS || null)
      script.onerror = () => reject(new Error('load local fingerprint sdk failed'))
      document.head.appendChild(script)
    })
    return window[fingerprintLoaderKey]
  }
  const makeFallbackFingerprint = async () => {
    const base = [
      navigator.userAgent || '',
      navigator.language || '',
      navigator.platform || '',
      String(new Date().getTimezoneOffset()),
      String(screen.width || 0),
      String(screen.height || 0),
      String(window.devicePixelRatio || 1)
    ].join('|')
    if (window.crypto?.subtle) {
      const data = new TextEncoder().encode(base)
      const hash = await crypto.subtle.digest('SHA-256', data)
      const arr = Array.from(new Uint8Array(hash))
      return arr.map((b) => b.toString(16).padStart(2, '0')).join('').slice(0, 32)
    }
    let hash = 2166136261
    for (let i = 0; i < base.length; i += 1) {
      hash ^= base.charCodeAt(i)
      hash += (hash << 1) + (hash << 4) + (hash << 7) + (hash << 8) + (hash << 24)
    }
    return `${(hash >>> 0).toString(16).padStart(8, '0')}${Date.now().toString(16)}`.slice(
      0,
      32
    )
  }
  const getFingerprint = async () => {
    let fp = localStorage.getItem(fingerprintKey) || ''
    const isValid = /^[a-f0-9]{32}$/i.test(fp)
    if (!isValid) {
      try {
        const FingerprintJS = await loadFingerprintLibrary()
        if (FingerprintJS && FingerprintJS.load) {
          const fpAgent = await FingerprintJS.load()
          const result = await fpAgent.get()
          fp = String(result?.visitorId || '')
        }
      } catch (e) {}
      if (!/^[a-f0-9]{32}$/i.test(fp)) {
        fp = await makeFallbackFingerprint()
      }
      localStorage.setItem(fingerprintKey, fp)
    }
    return fp
  }
  const postForm = async (url, payload) => {
    const formData = new URLSearchParams()
    Object.keys(payload || {}).forEach((key) => {
      const value = payload[key]
      if (value !== undefined && value !== null) formData.set(key, String(value))
    })
    const res = await fetch(url, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: formData.toString()
    })
    return res.json()
  }

  const showError = (message) => {
    if (errorBox) errorBox.textContent = message || ''
  }

  const setLoading = (loading) => {
    if (!submitBtn) return
    submitBtn.disabled = !!loading
    submitBtn.textContent = loading ? '查询中...' : '查询订单'
  }

  const renderOrders = (records) => {
    if (!resultBox) return
    if (!Array.isArray(records) || records.length === 0) {
      resultBox.innerHTML = '<div class="text-gray-400 text-sm">未找到相关订单</div>'
      return
    }
    resultBox.innerHTML = records
      .map((order) => {
        if (order?.trade_no) {
          orderMap[String(order.trade_no)] = order
        }
        const statusHtml =
          '<span class="simple-order-status ' +
          getOrderStatusClass(order?.status) +
          '">' +
          escapeHtml(getOrderStatusText(order?.status)) +
          '</span>'
        const cardsHtml =
          Array.isArray(order?.cards) && order.cards.length > 0
            ? '<div class="simple-order-cards">' +
              '<div class="simple-order-cards-title">卡密内容</div>' +
              order.cards
                .map(
                  (card) =>
                    '<div class="simple-order-card-item">' + escapeHtml(card) + '</div>'
                )
                .join('') +
              '</div>'
            : ''
        const instructionHtml =
          order?.status === 1 && order?.instruction
            ? '<div class="simple-order-instruction">' +
              '<div class="simple-order-instruction-title">使用说明</div>' +
              '<div class="simple-order-instruction-content">' +
              String(order.instruction || '') +
              '</div>' +
              '</div>'
            : ''
        const tradeNo = String(order?.trade_no || '')
        const hasComplaint =
          !!order?.complaint ||
          Object.prototype.hasOwnProperty.call(complaintStatusMap, tradeNo)
        const complaintStatus = hasComplaint
          ? order?.complaint?.status ?? complaintStatusMap[tradeNo] ?? 0
          : null
        const allowRetry = canRetryComplaint(order)
        const isExpired = order?.is_complaint_expired === true
        const showComplaintBtn = !isExpired && (!hasComplaint || allowRetry)
        const complaintHtml =
          order?.status === 1
            ? '<div class="simple-order-actions">' +
              (hasComplaint
                ? '<button class="simple-order-btn simple-order-btn-view" data-action="view-complaint" data-trade-no="' +
                  escapeHtml(tradeNo) +
                  '">查看投诉：' +
                  escapeHtml(getComplaintStatusText(complaintStatus)) +
                  '</button>'
                : '') +
              (showComplaintBtn
                ? '<button class="simple-order-btn simple-order-btn-danger" data-action="create-complaint" data-retry="' +
                  (allowRetry ? '1' : '0') +
                  '" data-trade-no="' +
                  escapeHtml(tradeNo) +
                  '" data-contact="' +
                  escapeHtml(order?.contact || '') +
                  '">' +
                  (allowRetry ? '重新投诉' : '订单投诉') +
                  '</button>'
                : '') +
              '</div>'
            : ''
        return (
          '<div class="simple-order-result-item">' +
          '<div class="simple-order-result-head">' +
          '<div>' +
          '<div class="simple-order-result-label">订单号</div>' +
          '<div class="simple-order-result-trade">' +
          escapeHtml(order?.trade_no || '') +
          '</div>' +
          '</div>' +
          statusHtml +
          '</div>' +
          '<div class="simple-order-result-main">' +
          escapeHtml(order?.product_name || '') +
          ' x ' +
          escapeHtml(order?.quantity || '') +
          '</div>' +
          '<div class="simple-order-result-meta">金额：¥' +
          escapeHtml(order?.total_price || '') +
          '</div>' +
          '<div class="simple-order-result-meta">' +
          escapeHtml(order?.create_time || '') +
          '</div>' +
          cardsHtml +
          instructionHtml +
          complaintHtml +
          '</div>'
        )
      })
      .join('')
  }

  const queryOrders = async ({ keyword = '', useFingerprint = false, silent = false } = {}) => {
    const queryText = String(keyword || '').trim()
    if (!queryText && !useFingerprint) {
      if (!silent) showError('请输入订单号或联系方式')
      return
    }
    showError('')
    setLoading(true)
    try {
      const params = new URLSearchParams({ page: '1', size: '10', keyword: queryText })
      if (queryText) {
        params.set('keyword', queryText)
      } else {
        params.set('fingerprint', await getFingerprint())
      }
      const res = await fetch('/api/order/query?' + params.toString(), {
        credentials: 'include'
      })
      const json = await res.json()
      if (Number(json?.code) !== 200) {
        if (!silent) showError(json?.msg || '查询失败')
        renderOrders([])
        return
      }
      renderOrders(json?.data?.records || [])
    } catch (e) {
      if (!silent) showError('查询失败，请稍后再试')
      renderOrders([])
    } finally {
      setLoading(false)
    }
  }

  const ensureCheckModal = () => {
    let modal = document.getElementById('simple-order-check-modal')
    if (modal) return modal
    modal = document.createElement('div')
    modal.id = 'simple-order-check-modal'
    modal.style.cssText =
      'position:fixed;inset:0;background:rgba(0,0,0,.45);display:none;align-items:center;justify-content:center;z-index:9999;padding:16px;'
    modal.innerHTML =
      '<div style="width:100%;max-width:420px;background:#fff;border-radius:12px;padding:16px;">' +
      '<div style="font-size:16px;font-weight:600;color:#111827;">验证投诉密码</div>' +
      '<div style="margin-top:8px;color:#6b7280;font-size:12px;">请输入投诉时设置的密码以查看详情</div>' +
      '<div style="margin-top:10px;display:flex;flex-direction:column;gap:10px;">' +
      '<input data-field="tradeNo" type="text" readonly style="height:38px;border:1px solid #d1d5db;border-radius:8px;padding:0 10px;background:#f9fafb;" />' +
      '<input data-field="password" type="password" placeholder="请输入投诉密码" style="height:38px;border:1px solid #d1d5db;border-radius:8px;padding:0 10px;" />' +
      '<div data-field="error" style="color:#dc2626;font-size:12px;min-height:18px;"></div>' +
      '<div style="display:flex;justify-content:flex-end;gap:8px;">' +
      '<button data-action="cancel" type="button" style="height:34px;padding:0 14px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#374151;">取消</button>' +
      '<button data-action="submit" type="button" style="height:34px;padding:0 14px;border:none;border-radius:8px;background:#2563eb;color:#fff;">查看详情</button>' +
      '</div>' +
      '</div>' +
      '</div>'
    document.body.appendChild(modal)
    modal.addEventListener('click', (evt) => {
      if (evt.target === modal) modal.style.display = 'none'
    })
    modal.querySelector('[data-action="cancel"]')?.addEventListener('click', () => {
      modal.style.display = 'none'
    })
    return modal
  }

  const ensureDetailModal = () => {
    let modal = document.getElementById('simple-order-detail-modal')
    if (modal) return modal
    modal = document.createElement('div')
    modal.id = 'simple-order-detail-modal'
    modal.style.cssText =
      'position:fixed;inset:0;background:rgba(0,0,0,.45);display:none;align-items:center;justify-content:center;z-index:9999;padding:16px;'
    modal.innerHTML =
      '<div style="width:100%;max-width:720px;max-height:88vh;overflow:auto;background:#fff;border-radius:12px;padding:16px;">' +
      '<div style="font-size:16px;font-weight:600;color:#111827;">投诉详情</div>' +
      '<div data-field="content" style="margin-top:10px;"></div>' +
      '<div style="margin-top:12px;display:flex;justify-content:flex-end;">' +
      '<button data-action="close" type="button" style="height:34px;padding:0 14px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#374151;">关闭</button>' +
      '</div>' +
      '</div>'
    document.body.appendChild(modal)
    modal.addEventListener('click', (evt) => {
      if (evt.target === modal) modal.style.display = 'none'
    })
    modal.querySelector('[data-action="close"]')?.addEventListener('click', () => {
      modal.style.display = 'none'
    })
    return modal
  }

  const openDetailModal = (complaint, tradeNo, password) => {
    const modal = ensureDetailModal()
    const content = modal.querySelector('[data-field="content"]')
    const logs = Array.isArray(complaint?.logs) ? complaint.logs : []
    const logsHtml = logs
      .map((log) => {
        const isBuyer = String(log?.user_type || '') === 'buyer'
        return (
          '<div style="display:flex;margin-top:10px;' +
          (isBuyer ? 'justify-content:flex-end;' : 'justify-content:flex-start;') +
          '">' +
          '<div style="max-width:78%;border-radius:10px;padding:10px 12px;' +
          (isBuyer
            ? 'background:#eff6ff;border:1px solid #bfdbfe;'
            : 'background:#f9fafb;border:1px solid #e5e7eb;') +
          '">' +
          '<div style="font-size:11px;color:#6b7280;">' +
          escapeHtml(log?.create_time || '') +
          ' · ' +
          escapeHtml(log?.user_type || '') +
          '</div>' +
          '<div style="margin-top:6px;color:#111827;font-size:13px;line-height:1.8;word-break:break-all;">' +
          escapeHtml(log?.content || '') +
          '</div>' +
          '</div>' +
          '</div>'
        )
      })
      .join('')
    const canReply = Number(complaint?.status) < 2
    const waitingHint =
      Number(complaint?.status) < 2 && !complaint?.seller_reply
        ? '等待商家回复，剩余时间 ' + getComplaintCountdown(complaint?.create_time)
        : '商家已回复'
    if (content) {
      content.innerHTML =
        '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">' +
        '<div style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;"><div style="font-size:12px;color:#6b7280;">投诉类型</div><div style="margin-top:4px;color:#111827;">' +
        escapeHtml(complaint?.type || '-') +
        '</div></div>' +
        '<div style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;"><div style="font-size:12px;color:#6b7280;">当前状态</div><div style="margin-top:4px;color:#111827;">' +
        escapeHtml(getComplaintStatusText(complaint?.status)) +
        '</div></div>' +
        '<div style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;grid-column:1 / -1;"><div style="font-size:12px;color:#6b7280;">投诉原因</div><div style="margin-top:4px;color:#111827;word-break:break-all;">' +
        escapeHtml(complaint?.reason || '-') +
        '</div></div>' +
        '<div style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;"><div style="font-size:12px;color:#6b7280;">管理员状态</div><div style="margin-top:4px;color:#111827;">' +
        (Number(complaint?.is_admin_read) === 1 ? '已读' : '未读') +
        '</div></div>' +
        '<div style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;"><div style="font-size:12px;color:#6b7280;">商家状态</div><div style="margin-top:4px;color:#111827;">' +
        (Number(complaint?.is_seller_read) === 1 ? '已读' : '未读') +
        '</div></div>' +
        '</div>' +
        (Number(complaint?.status) < 2
          ? '<div style="margin-top:10px;padding:8px 10px;border-radius:8px;background:#fff7ed;border:1px solid #fed7aa;color:#c2410c;font-size:12px;">' +
            escapeHtml(waitingHint) +
            '</div>'
          : '') +
        '<div style="margin-top:12px;font-size:13px;color:#374151;font-weight:600;">沟通记录</div>' +
        '<div style="margin-top:8px;max-height:40vh;overflow:auto;border:1px solid #e5e7eb;border-radius:10px;padding:10px;background:#fff;">' +
        (logsHtml || '<div style="color:#9ca3af;font-size:13px;">暂无记录</div>') +
        '</div>' +
        (canReply
          ? '<div style="margin-top:12px;">' +
            '<textarea data-field="reply" placeholder="请输入补充说明..." style="width:100%;min-height:88px;border:1px solid #d1d5db;border-radius:8px;padding:8px 10px;resize:vertical;"></textarea>' +
            '<div data-field="reply-error" style="min-height:18px;margin-top:6px;color:#dc2626;font-size:12px;"></div>' +
            '<div style="margin-top:8px;display:flex;justify-content:flex-end;gap:8px;">' +
            '<button data-action="cancel-complaint" type="button" style="height:34px;padding:0 14px;border:1px solid #fecaca;border-radius:8px;background:#fff;color:#b91c1c;">撤销投诉</button>' +
            '<button data-action="reply" type="button" style="height:34px;padding:0 14px;border:none;border-radius:8px;background:#2563eb;color:#fff;">发送回复</button>' +
            '</div>' +
            '</div>'
          : '<div style="margin-top:12px;color:#9ca3af;font-size:12px;">该投诉已结案，无法继续回复</div>')
    }
    if (canReply) {
      const replyInput = modal.querySelector('[data-field="reply"]')
      const replyError = modal.querySelector('[data-field="reply-error"]')
      const replyButton = modal.querySelector('[data-action="reply"]')
      const cancelButton = modal.querySelector('[data-action="cancel-complaint"]')
      cancelButton.onclick = async () => {
        cancelButton.disabled = true
        cancelButton.textContent = '撤销中...'
        if (replyError) replyError.textContent = ''
        try {
          const cancelJson = await postForm('/api/complaint/buyer/cancel', {
            id: complaint?.id,
            password
          })
          if (Number(cancelJson?.code) !== 200) {
            if (replyError) replyError.textContent = cancelJson?.msg || '撤销失败'
            return
          }
          delete complaintStatusMap[tradeNo]
          modal.style.display = 'none'
          queryOrders({ keyword: input?.value || '', silent: true })
        } catch (e) {
          if (replyError) replyError.textContent = '撤销失败，请稍后重试'
        } finally {
          cancelButton.disabled = false
          cancelButton.textContent = '撤销投诉'
        }
      }
      replyButton.onclick = async () => {
        const reply = (replyInput?.value || '').trim()
        if (!reply) {
          if (replyError) replyError.textContent = '请输入回复内容'
          return
        }
        if (replyError) replyError.textContent = ''
        replyButton.disabled = true
        replyButton.textContent = '发送中...'
        try {
          const replyJson = await postForm('/api/complaint/buyer/reply', {
            id: complaint?.id,
            reply,
            password
          })
          if (Number(replyJson?.code) !== 200) {
            if (replyError) replyError.textContent = replyJson?.msg || '回复失败'
            return
          }
          const params = new URLSearchParams({ trade_no: tradeNo, password: password || '' })
          const res = await fetch('/api/complaint/query?' + params.toString(), {
            credentials: 'include'
          })
          const json = await res.json()
          if (Number(json?.code) === 200) {
            const item = json?.data || {}
            complaintStatusMap[tradeNo] = Number(item?.status ?? 0)
            openDetailModal(item, tradeNo, password)
            queryOrders({ keyword: input?.value || '', silent: true })
          } else if (replyError) {
            replyError.textContent = json?.msg || '刷新投诉详情失败'
          }
        } catch (e) {
          if (replyError) replyError.textContent = '回复失败，请稍后重试'
        } finally {
          replyButton.disabled = false
          replyButton.textContent = '发送回复'
        }
      }
    }
    modal.style.display = 'flex'
  }

  const openComplaintModal = (tradeNo, contact) => {
    const modal = ensureComplaintModal()
    const tradeNoInput = modal.querySelector('[data-field="tradeNo"]')
    const typeInput = modal.querySelector('[data-field="type"]')
    const reasonInput = modal.querySelector('[data-field="reason"]')
    const contactInput = modal.querySelector('[data-field="contact"]')
    const passwordInput = modal.querySelector('[data-field="password"]')
    const errorBoxInModal = modal.querySelector('[data-field="error"]')
    const submitButton = modal.querySelector('[data-action="submit"]')
    if (tradeNoInput) tradeNoInput.value = tradeNo || ''
    if (typeInput) typeInput.value = ''
    if (reasonInput) reasonInput.value = ''
    if (contactInput) contactInput.value = contact || ''
    if (passwordInput) passwordInput.value = ''
    if (errorBoxInModal) errorBoxInModal.textContent = ''
    modal.style.display = 'flex'
    submitButton.onclick = async () => {
      const type = (typeInput?.value || '').trim()
      const reason = (reasonInput?.value || '').trim()
      const contactVal = (contactInput?.value || '').trim()
      const password = (passwordInput?.value || '').trim()
      if (!type || !reason || !contactVal || !password) {
        if (errorBoxInModal) errorBoxInModal.textContent = '请完整填写投诉信息'
        return
      }
      submitButton.disabled = true
      submitButton.textContent = '提交中...'
      if (errorBoxInModal) errorBoxInModal.textContent = ''
      try {
        const json = await postForm('/api/complaint/create', {
          trade_no: tradeNo,
          type,
          reason,
          contact: contactVal,
          password
        })
        if (Number(json?.code) === 200) {
          complaintStatusMap[tradeNo] = 0
          modal.style.display = 'none'
          queryOrders({ keyword: input?.value || '' })
        } else if (errorBoxInModal) {
          errorBoxInModal.textContent = json?.msg || '投诉提交失败'
        }
      } catch (e) {
        if (errorBoxInModal) errorBoxInModal.textContent = '投诉提交失败，请稍后重试'
      } finally {
        submitButton.disabled = false
        submitButton.textContent = '提交投诉'
      }
    }
  }

  submitBtn?.addEventListener('click', () => {
    queryOrders({ keyword: input?.value || '' })
  })
  input?.addEventListener('keydown', (evt) => {
    if (evt.key === 'Enter') {
      evt.preventDefault()
      queryOrders({ keyword: input?.value || '' })
    }
  })

  resultBox?.addEventListener('click', async (evt) => {
    const target = evt.target
    if (!target || !target.closest) return
    const createBtn = target.closest('[data-action="create-complaint"]')
    if (createBtn) {
      const tradeNo = createBtn.getAttribute('data-trade-no') || ''
      const defaultContact = createBtn.getAttribute('data-contact') || ''
      openComplaintModal(tradeNo, defaultContact)
      return
    }
    const viewBtn = target.closest('[data-action="view-complaint"]')
    if (viewBtn) {
      const tradeNo = viewBtn.getAttribute('data-trade-no') || ''
      const checkModal = ensureCheckModal()
      const tradeNoInput = checkModal.querySelector('[data-field="tradeNo"]')
      const passwordInput = checkModal.querySelector('[data-field="password"]')
      const errorBoxInModal = checkModal.querySelector('[data-field="error"]')
      const submitButton = checkModal.querySelector('[data-action="submit"]')
      if (tradeNoInput) tradeNoInput.value = tradeNo
      if (passwordInput) passwordInput.value = ''
      if (errorBoxInModal) errorBoxInModal.textContent = ''
      checkModal.style.display = 'flex'
      submitButton.onclick = async () => {
        const password = (passwordInput?.value || '').trim()
        if (!password) {
          if (errorBoxInModal) errorBoxInModal.textContent = '请输入投诉密码'
          return
        }
        submitButton.disabled = true
        submitButton.textContent = '查询中...'
        if (errorBoxInModal) errorBoxInModal.textContent = ''
        try {
          const params = new URLSearchParams({ trade_no: tradeNo, password })
          const res = await fetch('/api/complaint/query?' + params.toString(), {
            credentials: 'include'
          })
          const json = await res.json()
          if (Number(json?.code) === 200) {
            const item = json?.data || {}
            if (Number(item?.is_buyer_read) === 0) {
              await postForm('/api/complaint/read', { id: item?.id, password })
              item.is_buyer_read = 1
            }
            complaintStatusMap[tradeNo] = Number(item?.status ?? 0)
            checkModal.style.display = 'none'
            openDetailModal(item, tradeNo, password)
            queryOrders({ keyword: input?.value || '', silent: true })
          } else if (errorBoxInModal) {
            errorBoxInModal.textContent = json?.msg || '查询失败'
          }
        } catch (e) {
          if (errorBoxInModal) errorBoxInModal.textContent = '查询失败，请稍后重试'
        } finally {
          submitButton.disabled = false
          submitButton.textContent = '查看详情'
        }
      }
    }
  })

  queryOrders({ useFingerprint: true, silent: true })
})
