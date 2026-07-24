if (context && context.templateId) {
  root.setAttribute('data-template-id', context.templateId)
}

const shop = (context && context.shop) || {}

const avatar = root.querySelector('.shop-example-avatar-img')
if (avatar) {
  avatar.addEventListener('error', () => {
    avatar.src =
      'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2280%22 height=%2280%22 viewBox=%220 0 80 80%22%3E%3Crect width=%2280%22 height=%2280%22 rx=%2214%22 fill=%22%23eef2ff%22/%3E%3Ctext x=%2240%22 y=%2247%22 text-anchor=%22middle%22 font-size=%2228%22 fill=%22%23409eff%22%3ES%3C/text%3E%3C/svg%3E'
  })
}

const ensureModal = () => {
  let overlay = document.getElementById('shop-example-cert-modal')
  if (overlay) return overlay
  overlay = document.createElement('div')
  overlay.id = 'shop-example-cert-modal'
  overlay.className = 'shop-example-modal-overlay'
  overlay.innerHTML = `
  <div class="shop-example-modal">
    <div class="shop-example-modal-header">店铺认证信息</div>
    <div class="shop-example-modal-body">
      <div class="shop-example-modal-row">
        <span class="shop-example-modal-label">认证状态</span>
        <span class="shop-example-modal-value">${shop.is_certified ? '已认证' : '未认证'}</span>
      </div>
      <div class="shop-example-modal-row">
        <span class="shop-example-modal-label">认证类型</span>
        <span class="shop-example-modal-value">${shop.certification_type === 'enterprise' ? '企业认证' : '个人认证'}</span>
      </div>
      <div class="shop-example-modal-row">
        <span class="shop-example-modal-label">认证时间</span>
        <span class="shop-example-modal-value">${shop.certification_time || '暂无'}</span>
      </div>
      <div class="shop-example-modal-row">
        <span class="shop-example-modal-label">保证金</span>
        <span class="shop-example-modal-value">¥${shop.deposit || 0}</span>
      </div>
    </div>
    <div class="shop-example-modal-footer">
      <button class="shop-btn shop-btn-primary" data-close-cert-modal="1">我知道了</button>
    </div>
  </div>`
  document.body.appendChild(overlay)
  overlay.addEventListener('click', (event) => {
    const target = event.target
    if (!(target instanceof HTMLElement)) return
    if (target === overlay || target.dataset.closeCertModal === '1') {
      overlay.classList.remove('is-open')
    }
  })
  return overlay
}

root.addEventListener('click', (event) => {
  const target = event.target
  if (!(target instanceof HTMLElement)) return
  const trigger = target.closest('[data-action="show-certification"]')
  if (!trigger) return
  const modal = ensureModal()
  modal.classList.add('is-open')
})
