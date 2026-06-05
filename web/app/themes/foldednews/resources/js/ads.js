/**
 * Viewable ad impressions: count an impression once a slot has been ≥50% visible
 * for 1s (a simple viewability gate), beaconed to the tracking endpoint. Clicks
 * are tracked server-side via the redirect link. Inert without ad slots.
 */

const slots = document.querySelectorAll('[data-ad]')

if (slots.length && 'IntersectionObserver' in window) {
  const counted = new Set()
  const timers = new Map()

  const beacon = (el) => {
    const body = JSON.stringify({ c: el.dataset.ad })
    const url = el.dataset.beacon
    if (navigator.sendBeacon) {
      navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }))
    } else {
      fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body, keepalive: true })
    }
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        const el = entry.target
        const id = el.dataset.ad
        if (entry.isIntersecting && entry.intersectionRatio >= 0.5) {
          if (counted.has(id)) return
          timers.set(
            id,
            setTimeout(() => {
              counted.add(id)
              observer.unobserve(el)
              beacon(el)
            }, 1000)
          )
        } else {
          clearTimeout(timers.get(id))
        }
      })
    },
    { threshold: [0, 0.5, 1] }
  )

  slots.forEach((el) => observer.observe(el))
}
