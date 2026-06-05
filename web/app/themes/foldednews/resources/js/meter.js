/**
 * Meter wall: watch-ad-to-unlock. Plays the ad (Stage 13 plugs in a real ad
 * unit here), then calls the unlock REST endpoint and reloads to reveal the
 * article. Inert unless the meter wall is present.
 */

const wall = document.querySelector('[data-fn-meter]')

if (wall) {
  const button = wall.querySelector('button[data-action="ad"]')

  button?.addEventListener('click', async () => {
    const original = button.textContent
    button.disabled = true
    button.textContent = 'Loading ad…'

    // Placeholder ad playback (Stage 13 replaces with a verified ad unit).
    await new Promise((resolve) => setTimeout(resolve, 1500))

    try {
      const res = await fetch(wall.dataset.rest, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ post: wall.dataset.post, nonce: wall.dataset.nonce }),
      })
      const data = await res.json()
      if (data && data.unlocked) {
        window.location.reload()
        return
      }
    } catch (e) {
      /* fall through */
    }

    button.disabled = false
    button.textContent = original
  })
}
