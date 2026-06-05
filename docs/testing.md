# Testing & QA

## Automated

| Layer | Tool | Command |
|---|---|---|
| PHP lint | `php -l` | `find ... -name '*.php' | xargs -n1 php -l` |
| PHP static analysis | PHPStan | `composer analyse` |
| PHP unit | Pest | `composer test` |
| Frontend build | Vite | `npm run build` (in theme) |
| E2E / smoke | Playwright | `npx playwright test` (Stage 3+) |
| Accessibility | axe + Playwright | Stage 3+ |
| Secret scan | Gitleaks | CI |
| Dependency audit | `composer audit`, `npm audit` | CI |

All wired in `.github/workflows/ci.yml`. The `e2e` job self-activates when
`tests/e2e/**` exists.

## Manual QA — Stage 1 (Foundation)

Run after `composer install` (root + theme), `npm run build`, and a configured
`.env` pointing at a MySQL database with `web/wp` installed.

- [ ] Homepage loads (`WP_HOME`) without PHP errors.
- [ ] `wp-admin` loads and you can log in.
- [ ] Sage assets compile: `npm run build` produces `web/app/themes/foldednews/public/build/manifest.json`.
- [ ] Acorn boots: `wp acorn about` runs without error.
- [ ] No hardcoded vanilla WordPress paths — content dir is `/app`, not `/wp-content`.
- [ ] Zilla Slab renders for headings/brand.
- [ ] Skip-to-content link is reachable by keyboard; primary nav is keyboard navigable.

> Note: homepage/admin/Acorn checks require a live WordPress + MySQL environment,
> which is provisioned during install (see `README.md`) — they cannot run in a
> static CI-only context.

## Manual QA — Stage 2 (Content model)

Requires a live WordPress + MySQL install (mu-plugin active automatically).

- [ ] All 15 content types appear in wp-admin: Articles, Live Blogs, Live
  Updates, Timeline Events, People, Organizations, Places, Videos, Podcasts,
  Newsletters, Campaigns, Contacts, Corrections, Source Notes, Editorial Reviews.
- [ ] Topics + Article Formats taxonomies exist; the 10 formats are seeded
  (Standard News … Podcast Article).
- [ ] Create and publish a test Article with a dek, featured image, topic, and a
  People byline.
- [ ] Author-card data is available: People entry has role + social; article
  carries `_fn_byline`. (Card rendering lands in Stage 3.)
- [ ] Published + Updated dates: edit the article and confirm `post_modified`
  advances.
- [ ] View source on the single article: a `NewsArticle` JSON-LD block is
  present with `datePublished`, `dateModified`, `headline`, `author`,
  `publisher` (validate at https://validator.schema.org/).
- [ ] Permalinks resolve (e.g. `/articles/`, `/videos/`, `/people/`) — flush
  Permalinks once if needed.

## Manual QA — Stage 3 (Sage news theme)

Requires a live install with a few published Articles (some tagged with topic
slugs `politics`/`business`/etc., one tagged Article Format "Breaking News",
and a People entry set as an article's `_fn_byline`).

- [ ] Homepage renders: breaking banner, hero, lead package, latest rail, live
  updates rail, topic/section rails, newsletter signup.
- [ ] **No duplicate posts** across the homepage (dedup is enforced server-side
  by `App\Support\Feed` via `post__not_in`).
- [ ] Empty sections are omitted (untagged topics don't render empty rails).
- [ ] Single article shows the author card under the headline, the date line
  (Published / Updated), source notes and corrections when present.
- [ ] Mobile layout works (single column → grid at `sm`/`lg`).
- [ ] Keyboard navigation: skip link, nav, and one link per card (image links
  are `aria-hidden`/`tabindex=-1` to avoid duplicates).
- [ ] Zilla Slab renders on headings/brand.
- [ ] Lighthouse/CWV baseline acceptable (full optimisation is Stage 19).

## Manual QA — Stage 4 (Markdown over Gutenberg)

- [ ] Edit an Article: the "Newsroom Markdown" meta box is present.
- [ ] Write Markdown (headings, list, `**bold**`, `:::note … :::`), save.
- [ ] `post_content` now holds Gutenberg blocks; reopen in the block editor and
  confirm the blocks render and are valid (no "invalid block" warnings).
- [ ] Reload the editor — the Markdown source persists (`_newsroom_markdown_source`).
- [ ] Front end renders the converted blocks correctly.
- [ ] Edit a block directly without changing Markdown, save — block edits are
  kept (hash guard skips reconversion).
- [ ] Raw `<script>` in Markdown is escaped, not executed.

Automated: `tests/Unit/MarkdownTest.php` (Pest) covers the pure-PHP converter;
PHPStan level 5 covers the module.

## Manual QA — Stage 5 (S3 media offload)

Set the `S3_*` + `CDN_URL` env vars (offload is inert until all are set).

- [ ] Upload an image — original + generated sizes appear in the bucket
  (`S3_PATH_PREFIX/…`).
- [ ] Upload a video and a podcast audio file — both offload.
- [ ] Front end / REST / RSS show **CDN URLs**, no public
  `web/app/uploads/` (or `wp-content/uploads/`) URLs (`scripts/health-check.sh`).
- [ ] `srcset` URLs are CDN-rewritten.
- [ ] Image still looks lossless (no recompression by the offloader).
- [ ] With `S3_DELETE_LOCAL=true`, local files are removed only after the remote
  HEAD verify succeeds.
- [ ] Rollback: point S3 at bad credentials → upload still succeeds locally, the
  URL stays local, the failure is logged (`fn_s3_failures`) and a retry is
  scheduled (`fn_s3_retry`).

Automated: `tests/Unit/SignerTest.php` verifies SigV4 against AWS's published
vector; PHPStan level 5 covers the media layer.

## Per-stage checklists

Each stage appends its manual QA checklist here as it lands (content model,
theme layouts, editor, media offload, live blog, maps, video, podcast,
subscriptions, meter, newsletters, ads, AI, bookmarks, desktop mode, extension,
health, performance, hardening).
