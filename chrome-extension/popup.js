/* FoldedNews QA popup. Runs page checks on demand via activeTab, on configured
   domains only. No reader data is collected or transmitted. */

const statusEl = document.getElementById('status')
const runBtn = document.getElementById('run')
const exportBtn = document.getElementById('export')
const resultsEl = document.getElementById('results')

let lastReport = null

document.getElementById('options').addEventListener('click', (e) => {
  e.preventDefault()
  chrome.runtime.openOptionsPage()
})

function getConfig() {
  return new Promise((resolve) => {
    chrome.storage.sync.get({ domains: ['foldednews.test', 'localhost'], health: '' }, resolve)
  })
}

async function activeTab() {
  const [tab] = await chrome.tabs.query({ active: true, currentWindow: true })
  return tab
}

// This function is injected into the page; it must be self-contained.
function runChecks() {
  const q = (s) => document.querySelector(s)
  const all = (s) => Array.from(document.querySelectorAll(s))
  const ld = all('script[type="application/ld+json"]').map((s) => s.textContent || '').join(' ')
  const html = document.documentElement.innerHTML

  const imgs = all('img')
  const missingAlt = imgs.filter((i) => !i.hasAttribute('alt')).length
  const broken = imgs.filter((i) => i.complete && i.naturalWidth === 0).length

  const headings = all('h1,h2,h3,h4,h5,h6').map((h) => Number(h.tagName[1]))
  let orderOk = headings.length === 0 || headings[0] === 1
  for (let i = 1; i < headings.length; i++) if (headings[i] - headings[i - 1] > 1) orderOk = false

  const unlabeledButtons = all('button').filter(
    (b) => !(b.textContent || '').trim() && !b.getAttribute('aria-label') && !b.querySelector('img[alt]')
  ).length

  return {
    url: location.href,
    checkedAt: new Date().toISOString(),
    checks: {
      title: !!document.title,
      metaDescription: !!q('meta[name="description"]'),
      canonical: !!q('link[rel="canonical"]'),
      openGraph: !!q('meta[property^="og:"]'),
      newsArticleSchema: /NewsArticle|LiveBlogPosting/.test(ld),
      authorName: /"author"/.test(ld) || !!q('[rel="author"], .author'),
      authorImage: !!q('.author img, .fn-author img, [itemprop="author"] img'),
      publishedDate: /datePublished/.test(ld) || !!q('time[datetime]'),
      updatedDate: /dateModified/.test(ld),
      featuredImage: !!q('article img, figure img, [property="og:image"]'),
      imageAltText: missingAlt === 0,
      noLocalUploads: !/wp-content\/uploads\//.test(html) && !/app\/uploads\//.test(html),
      cdnUsed: /cdn|cloudfront|r2\.|digitaloceanspaces|backblaze|amazonaws/i.test(html),
      paywallComponent: !!q('.fn-meter-wall'),
      newsletterModule: !!q('[data-fn-newsletter]'),
      adSlots: all('[data-ad]').length > 0,
      mapChartBlocks: all('[data-fn-viz]').length >= 0,
      liveBlog: !!q('#fn-update-stream'),
      brokenImages: broken === 0,
      headingOrder: orderOk,
      buttonLabels: unlabeledButtons === 0,
    },
    counts: { images: imgs.length, missingAlt, broken, unlabeledButtons },
  }
}

function render(report) {
  resultsEl.innerHTML = ''
  Object.entries(report.checks).forEach(([key, value]) => {
    const li = document.createElement('li')
    const name = document.createElement('span')
    name.textContent = key
    const val = document.createElement('span')
    val.textContent = value === true ? 'pass' : value === false ? 'fail' : String(value)
    val.className = value === true ? 'pass' : 'fail'
    li.append(name, val)
    resultsEl.append(li)
  })
}

async function init() {
  const cfg = await getConfig()
  const tab = await activeTab()
  const host = tab && tab.url ? new URL(tab.url).hostname : ''
  const allowed = cfg.domains.some((d) => host === d || host.endsWith('.' + d))

  if (!allowed) {
    statusEl.textContent = `${host || 'this page'} is not a configured newsroom domain.`
    return
  }
  statusEl.textContent = `Ready on ${host}.`
  runBtn.disabled = false

  runBtn.addEventListener('click', async () => {
    statusEl.textContent = 'Running…'
    const [{ result }] = await chrome.scripting.executeScript({ target: { tabId: tab.id }, func: runChecks })
    lastReport = result
    render(result)
    const failed = Object.values(result.checks).filter((v) => v === false).length
    statusEl.textContent = `${failed} issue(s).`
    exportBtn.disabled = false
  })

  exportBtn.addEventListener('click', () => {
    if (!lastReport) return
    const blob = new Blob([JSON.stringify(lastReport, null, 2)], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    chrome.downloads ? chrome.downloads.download({ url, filename: 'foldednews-qa.json' }) : window.open(url)
  })
}

init()
