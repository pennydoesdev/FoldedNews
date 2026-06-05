/**
 * Interactive visualizations for the :::map / :::chart / :::diagram blocks.
 * Each <figure data-fn-viz> carries a base64 config (JSON for chart/map, Mermaid
 * source for diagram). Libraries (Leaflet, Chart.js, Mermaid) are lazy-loaded
 * only when a figure nears the viewport, and only the type that's needed.
 * On any failure the figure shows a static text fallback.
 */

function decode(b64) {
  try {
    return decodeURIComponent(escape(atob(b64 || '')))
  } catch (e) {
    return ''
  }
}

function isDark() {
  return (
    document.documentElement.classList.contains('dark') ||
    window.matchMedia('(prefers-color-scheme: dark)').matches
  )
}

function addCaption(figure, config) {
  const parts = [config.caption, config.source && 'Source: ' + config.source].filter(Boolean)
  const mount = figure.querySelector('.fn-viz-mount')
  if (config.caption && mount) mount.setAttribute('aria-label', config.caption)
  if (!parts.length) return
  const fc = document.createElement('figcaption')
  fc.className = 'fn-viz-caption'
  fc.textContent = parts.join(' — ')
  figure.appendChild(fc)
}

async function renderChart(mount, config) {
  const { Chart, registerables } = await import('chart.js')
  Chart.register(...registerables)
  const canvas = document.createElement('canvas')
  mount.appendChild(canvas)
  const ink = isDark() ? '#e5e5e5' : '#262626'
  config.options = config.options || {}
  config.options.responsive = true
  config.options.maintainAspectRatio = false
  config.options.color = ink
  new Chart(canvas, config)
}

async function renderMap(mount, config) {
  const leaflet = await import('leaflet')
  await import('leaflet/dist/leaflet.css')
  const L = leaflet.default || leaflet
  mount.style.height = (config.height || 400) + 'px'
  const map = L.map(mount).setView(config.center || [0, 0], config.zoom || 2)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(map)
  ;(config.markers || []).forEach((m) => {
    if (m.latlng) L.marker(m.latlng).addTo(map).bindPopup(m.label || '')
  })
  if (config.geojson) L.geoJSON(config.geojson).addTo(map)
}

async function renderDiagram(mount, code, dark) {
  const mermaid = (await import('mermaid')).default
  mermaid.initialize({
    startOnLoad: false,
    theme: dark ? 'dark' : 'default',
    fontFamily: 'Zilla Slab, Georgia, serif',
  })
  const id = 'fn-mmd-' + Math.random().toString(36).slice(2)
  const { svg } = await mermaid.render(id, code)
  mount.innerHTML = svg
}

async function hydrate(figure) {
  const type = figure.dataset.fnViz
  const mount = figure.querySelector('.fn-viz-mount')
  if (!mount) return
  const raw = decode(figure.dataset.fnConfig)

  try {
    if (type === 'diagram') {
      await renderDiagram(mount, raw, isDark())
    } else {
      const config = raw ? JSON.parse(raw) : {}
      if (type === 'chart') await renderChart(mount, config)
      else if (type === 'map') await renderMap(mount, config)
      addCaption(figure, config)
    }
    figure.classList.add('is-ready')
  } catch (e) {
    console.error('[foldednews] visualization failed:', e)
    mount.textContent = mount.getAttribute('aria-label') || 'Visualization unavailable.'
  }
}

const figures = document.querySelectorAll('[data-fn-viz]')
if (figures.length) {
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(
      (entries, obs) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            obs.unobserve(entry.target)
            hydrate(entry.target)
          }
        })
      },
      { rootMargin: '200px' }
    )
    figures.forEach((el) => io.observe(el))
  } else {
    figures.forEach(hydrate)
  }
}
