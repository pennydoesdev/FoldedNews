/**
 * Video.js player init. Lazy-loaded: the player library + CSS only download when
 * a [data-fn-video] element is present on the page (article video embeds, the
 * video hub, single video pages). Supports all aspect ratios, caption and
 * chapter tracks (declared in the markup).
 */

async function init() {
  const players = document.querySelectorAll('video[data-fn-video]')
  if (!players.length) return

  const [{ default: videojs }] = await Promise.all([import('video.js')])
  await import('video.js/dist/video-js.css')

  players.forEach((el) => {
    if (el.dataset.vjsReady) return
    el.dataset.vjsReady = '1'
    videojs(el, { fluid: false, responsive: true, playsinline: true })
  })
}

init()
