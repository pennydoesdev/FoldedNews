/**
 * Account billing buttons. POST to the billing REST routes (cookie auth + REST
 * nonce) and redirect to the returned Stripe Checkout / Customer Portal URL.
 * Inert unless the account billing block is present.
 */

const root = document.querySelector('[data-fn-billing]')

if (root) {
  const base = (root.dataset.rest || '').replace(/\/$/, '')
  const nonce = root.dataset.nonce || ''

  root.querySelectorAll('button[data-action]').forEach((button) => {
    button.addEventListener('click', async () => {
      button.disabled = true
      try {
        const res = await fetch(`${base}/${button.dataset.action}`, {
          method: 'POST',
          headers: { 'X-WP-Nonce': nonce, 'Content-Type': 'application/json' },
        })
        const data = await res.json()
        if (data && data.url) {
          window.location.href = data.url
          return
        }
      } catch (e) {
        /* fall through to re-enable */
      }
      button.disabled = false
      button.textContent = button.textContent + ' — unavailable'
    })
  })
}
