/**
 * Newsletter signup forms + the token-based preferences/unsubscribe page.
 * POST JSON to the REST endpoints; inert unless those elements are present.
 */

async function postJson(url, body) {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  })
  return res.json()
}

// Signup forms (may be several per page).
document.querySelectorAll('form[data-fn-newsletter]').forEach((form) => {
  form.addEventListener('submit', async (event) => {
    event.preventDefault()
    const status = form.querySelector('[data-status]')
    const data = new FormData(form)
    try {
      const json = await postJson(form.dataset.rest, {
        email: data.get('email'),
        name: data.get('name') || '',
        list: data.get('list') || '',
        nonce: form.dataset.nonce,
      })
      if (status) status.textContent = json.subscribed ? 'Thanks — you’re subscribed.' : 'Sorry, that didn’t work.'
      if (json.subscribed) form.reset()
    } catch (e) {
      if (status) status.textContent = 'Network error. Please try again.'
    }
  })
})

// Preferences / unsubscribe page.
const prefs = document.querySelector('form[data-fn-prefs]')
if (prefs) {
  const status = prefs.querySelector('[data-status]')
  const base = { email: prefs.dataset.email, token: prefs.dataset.token }

  prefs.addEventListener('submit', async (event) => {
    event.preventDefault()
    const lists = [...prefs.querySelectorAll('input[name="lists"]:checked')].map((i) => i.value)
    const json = await postJson(prefs.dataset.rest, { ...base, lists })
    if (status) status.textContent = json.updated ? 'Preferences updated.' : 'Could not update.'
  })

  prefs.querySelector('[data-action="unsubscribe"]')?.addEventListener('click', async () => {
    const json = await postJson(prefs.dataset.rest, { ...base, unsubscribe: 1 })
    if (status) status.textContent = json.unsubscribed ? 'You have been unsubscribed.' : 'Could not unsubscribe.'
  })
}
