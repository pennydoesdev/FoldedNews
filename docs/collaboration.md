# Collaborative Authoring (planned)

Real-time collaborative editing (Google-Docs-style) for the newsroom editor and
live blog, captured from official sources. This is a **forward enhancement** over
the Stage 4 Markdown/Gutenberg editor and the Stage 6 live blog — not yet built.

## Stack

| Layer | Tech | Docs |
|---|---|---|
| CRDT sync engine | **Yjs** (shared types: `Y.Text`/`Y.Array`/`Y.Map`; conflict-free, network-agnostic) | https://docs.yjs.dev/ |
| Editor (markdown-native) | **Milkdown** — markdown WYSIWYG on ProseMirror; its document *is* markdown | https://milkdown.dev/docs |
| Editor (alt, HTML-native) | **Tiptap** (headless, on ProseMirror) | https://tiptap.dev/docs |
| Editor ↔ CRDT binding | Milkdown `@milkdown/plugin-collab` (or `y-prosemirror` / Tiptap `Collaboration`); use **relative positions** for comments/versions, not indexes | https://milkdown.dev/docs/plugin/plugin-collab · https://github.com/yjs/y-prosemirror |
| Network provider | `y-websocket` + **Hocuspocus** server (meshable; swappable) | https://tiptap.dev/docs/hocuspocus |
| Offline | `y-indexeddb` (local persistence + service worker) | https://github.com/yjs/y-indexeddb |
| Presence | Yjs **Awareness** (cursors, names, colors) | https://docs.yjs.dev/getting-started/adding-awareness |

## Milkdown setup (markdown editor for `_newsroom_markdown_source`)

Milkdown 7.21.x (official packages, verified from the repo):

- **`@milkdown/crepe`** — batteries-included markdown editor (UI + Nord/Material
  themes). Fastest path for the authoring surface.
- **`@milkdown/kit`** — modular all-in-one (core + commonmark + gfm + plugins)
  for a custom build.
- **`@milkdown/plugin-collab`** + `yjs` `y-protocols` `y-prosemirror` — real-time
  collab; bind a `Y.Doc` + a provider's awareness, then `connect()`.

The editor's markdown is written to `_newsroom_markdown_source`; the Stage 4
`MarkdownToBlocks` pipeline produces the canonical Gutenberg `post_content`.
Built as a Sage/Vite entry mounted in a Gutenberg sidebar/panel or a desk app.

## Newsroom fit

- **Article/live-blog editing** — multiple reporters/editors on one document; the
  canonical store stays WordPress (`post_content` + `_newsroom_markdown_source`).
  Yjs syncs the in-progress doc; on save we serialize to blocks (Stage 4 pipeline).
- **Mentions** (`@person`) → resolve against the People CPT (Stage 2).
- **Comments / threads** → relative positions; editorial review (Stage 2 CPT).
- **Versions / diffs** → Yjs snapshots for "what changed while you were away".

## Notes

- Tiptap is headless/MIT; collaboration via self-hosted Hocuspocus keeps it
  first-party (no third-party doc server required).
- AI assistance over documents (Tiptap Server AI Toolkit / our Stage 14 AI
  router) edits via precise operations rather than replacing the whole document.
- Custom blocks/nodes need schema awareness if an AI agent generates them.
