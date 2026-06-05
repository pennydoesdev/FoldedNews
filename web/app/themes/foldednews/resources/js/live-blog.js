/**
 * Live blog auto-refresh. Polls the REST endpoint for new *approved* updates and
 * inserts them without a reload. Inert unless an #fn-update-stream is present.
 */

function initStream(el) {
  const rest = el.dataset.rest
  if (!rest) return

  const order = el.dataset.order === 'ASC' ? 'ASC' : 'DESC'
  let since = parseInt(el.dataset.since || '0', 10) || 0
  let archived = el.dataset.archived === '1'

  const render = (u) => {
    const article = document.createElement('article')
    article.id = 'update-' + u.id
    article.className = 'fn-update border-l-2 border-brand-600 pl-4'
    const meta = [u.time_human, u.reporter].filter(Boolean).join(' · ')
    const correction = u.correction
      ? ` <span class="rounded bg-amber-200 px-1.5 py-0.5 text-xs font-semibold uppercase text-amber-900"></span>`
      : ''
    const header = document.createElement('header')
    header.className = 'text-xs text-ink-500'
    header.innerHTML = `<time datetime="${u.time_iso}"></time>${correction}`
    header.querySelector('time').textContent = meta
    if (u.correction) header.querySelector('span').textContent = u.correction

    if (u.title) {
      const h = document.createElement('h3')
      h.className = 'mt-1 font-display text-lg font-semibold'
      h.textContent = u.title
      article.appendChild(header)
      article.appendChild(h)
    } else {
      article.appendChild(header)
    }

    const body = document.createElement('div')
    body.className = 'prose prose-sm mt-2 max-w-none'
    body.innerHTML = u.content // server-rendered post content
    article.appendChild(body)
    return article
  }

  const poll = async () => {
    if (archived) return
    try {
      const res = await fetch(`${rest}?since=${since}&order=${order}`, {
        headers: { Accept: 'application/json' },
      })
      if (!res.ok) return
      const data = await res.json()
      archived = Boolean(data.archived)
      ;(Array.isArray(data.updates) ? data.updates : []).forEach((u) => {
        if (document.getElementById('update-' + u.id)) return
        const node = render(u)
        if (order === 'ASC') el.appendChild(node)
        else el.insertBefore(node, el.firstChild)
      })
      if (typeof data.latest === 'number' && data.latest > since) since = data.latest
    } catch (e) {
      /* transient network error; retry next tick */
    }
  }

  setInterval(poll, 20000)
}

document.querySelectorAll('#fn-update-stream').forEach(initStream)
