/**
 * Bookmarks. Logged-in readers toggle via REST; guests use localStorage and
 * merge into their account on login. Also drives the account Saved Articles
 * search/sort/remove. Inert unless bookmark elements are present.
 */

const KEY = 'fn_bookmarks'
const readGuest = () => {
  try {
    return JSON.parse(localStorage.getItem(KEY) || '[]').map(String)
  } catch (e) {
    return []
  }
}
const writeGuest = (ids) => {
  try {
    localStorage.setItem(KEY, JSON.stringify([...new Set(ids)]))
  } catch (e) {
    /* storage unavailable */
  }
}

function reflect(btn, saved) {
  btn.dataset.saved = saved ? '1' : '0'
  btn.setAttribute('aria-pressed', saved ? 'true' : 'false')
  const icon = btn.querySelector('.fn-bookmark-icon')
  if (icon) icon.textContent = saved ? '★' : '☆'
  const label = btn.querySelector('.fn-bookmark-label')
  if (label) label.textContent = saved ? 'Saved' : 'Save'
}

const buttons = document.querySelectorAll('[data-fn-bookmark]')

if (buttons.length) {
  const guest = new Set(readGuest())
  const loggedInBtn = [...buttons].find((b) => b.dataset.loggedIn === '1')

  // Merge guest bookmarks into the account on login, then clear local storage.
  if (loggedInBtn && guest.size) {
    fetch(`${loggedInBtn.dataset.rest}/merge`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': loggedInBtn.dataset.nonce },
      body: JSON.stringify({ ids: [...guest] }),
    })
      .then(() => localStorage.removeItem(KEY))
      .catch(() => {})
  }

  buttons.forEach((btn) => {
    if (btn.dataset.loggedIn !== '1') reflect(btn, guest.has(btn.dataset.post))

    btn.addEventListener('click', async () => {
      const post = btn.dataset.post

      if (btn.dataset.loggedIn === '1') {
        try {
          const res = await fetch(`${btn.dataset.rest}/toggle`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': btn.dataset.nonce },
            body: JSON.stringify({ post_id: post }),
          })
          const data = await res.json()
          reflect(btn, !!data.saved)
          if (!data.saved) btn.closest('[data-saved-item]')?.remove()
        } catch (e) {
          /* ignore */
        }
      } else {
        const g = new Set(readGuest())
        g.has(post) ? g.delete(post) : g.add(post)
        writeGuest([...g])
        reflect(btn, g.has(post))
      }
    })
  })
}

// Account → Saved Articles: search + sort.
const list = document.querySelector('[data-fn-saved-list]')
if (list) {
  const items = [...list.querySelectorAll('[data-saved-item]')]
  const search = document.querySelector('[data-fn-saved-search]')
  const sort = document.querySelector('[data-fn-saved-sort]')

  const apply = () => {
    const q = (search?.value || '').toLowerCase()
    items.forEach((it) => {
      it.style.display = (it.dataset.title || '').toLowerCase().includes(q) ? '' : 'none'
    })
    if (sort) {
      const key = sort.value === 'published' ? 'published' : 'saved'
      ;[...items]
        .sort((a, b) => Number(b.dataset[key] || 0) - Number(a.dataset[key] || 0))
        .forEach((it) => list.appendChild(it))
    }
  }

  search?.addEventListener('input', apply)
  sort?.addEventListener('change', apply)
}
