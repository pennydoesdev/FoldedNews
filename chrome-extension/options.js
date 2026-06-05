const domainsEl = document.getElementById('domains')
const healthEl = document.getElementById('health')
const statusEl = document.getElementById('status')

chrome.storage.sync.get({ domains: ['foldednews.test', 'localhost'], health: '' }, (cfg) => {
  domainsEl.value = cfg.domains.join('\n')
  healthEl.value = cfg.health
})

document.getElementById('save').addEventListener('click', () => {
  const domains = domainsEl.value
    .split(/\r?\n/)
    .map((d) => d.trim().toLowerCase())
    .filter(Boolean)

  chrome.storage.sync.set({ domains, health: healthEl.value.trim() }, () => {
    statusEl.textContent = 'Saved.'
    setTimeout(() => (statusEl.textContent = ''), 1500)
  })
})
