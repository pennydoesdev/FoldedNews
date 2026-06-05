/**
 * FoldedNews newsroom editor — a clean, Google-Docs-style Markdown surface
 * built on Milkdown (Crepe). The editor's document *is* Markdown: it writes the
 * canonical source back to a hidden textarea, which the Stage 4 save_post
 * pipeline converts to Gutenberg blocks. Real-time collaboration (Yjs) activates
 * when a WebSocket URL is configured; otherwise editing is solo.
 */

import { Crepe } from '@milkdown/crepe'
import '@milkdown/crepe/theme/common/style.css'
import '@milkdown/crepe/theme/frame.css'

async function mount(el) {
  const textarea = document.getElementById(el.dataset.textarea || '')
  if (!textarea) return

  const crepe = new Crepe({ root: el, defaultValue: textarea.value || '' })

  const wsUrl = el.dataset.collabWs || ''
  const room = el.dataset.room || 'fn-doc'

  if (wsUrl) {
    try {
      const [{ collab, collabServiceCtx }, Y, { WebsocketProvider }] = await Promise.all([
        import('@milkdown/plugin-collab'),
        import('yjs'),
        import('y-websocket'),
      ])

      crepe.editor.use(collab)
      await crepe.create()

      const doc = new Y.Doc()
      const provider = new WebsocketProvider(wsUrl, room, doc)
      crepe.editor.action((ctx) => {
        ctx.get(collabServiceCtx).bindDoc(doc).setAwareness(provider.awareness).connect()
      })
    } catch (err) {
      console.error('[foldednews] collaboration unavailable, editing solo:', err)
      await crepe.create()
    }
  } else {
    await crepe.create()
  }

  // Persist Markdown to the hidden textarea so the existing form save converts it.
  const sync = () => {
    try {
      textarea.value = crepe.getMarkdown()
    } catch (e) {
      /* editor not ready */
    }
  }

  try {
    crepe.on((listener) => listener.markdownUpdated(() => sync()))
  } catch (e) {
    /* listener API guard — submit handler below still persists */
  }
  textarea.closest('form')?.addEventListener('submit', sync)

  textarea.style.display = 'none'
  el.classList.add('is-ready')
}

document.querySelectorAll('[data-fn-milkdown]').forEach((el) => mount(el))
