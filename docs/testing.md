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

## Manual QA — Stage 6 (Live blog + timeline)

- [ ] Create a Live Blog; note its ID.
- [ ] Add Live Updates with that Live Blog ID, mark **Approved**; they appear on
  the live blog (unapproved ones do not).
- [ ] Pin an update → it shows in the pinned slot with a "Pinned" badge.
- [ ] Add a correction label to an update → the label renders.
- [ ] Newest/Oldest toggle reorders the stream.
- [ ] Auto-refresh: publish a new approved update; within ~20s it appears without
  reload (`/wp-json/foldednews/v1/live/{id}/updates`).
- [ ] Archive the Live Blog → "Archived" badge shows and polling stops.
- [ ] `LiveBlogPosting` JSON-LD present on the live blog (validate at
  https://validator.schema.org/).
- [ ] Timeline archive groups events by year; Vertical/Horizontal toggle works;
  mobile layout is single-column.

Automated: `tests/Unit/LiveBlogTest.php`; PHPStan level 5 covers the domain.

## Manual QA — Stage 7 (Maps / charts / diagrams)

In an article's Markdown, add viz directives and publish:
- `:::chart` + a Chart.js config JSON (e.g. `{"type":"bar","data":{...},"caption":"…","source":"…"}`)
- `:::map` + `{"center":[lat,lng],"zoom":10,"markers":[{"latlng":[lat,lng],"label":"…"}]}`
- `:::diagram` + Mermaid source (e.g. `graph TD; A-->B`)

- [ ] Chart renders (Chart.js); bar/line/etc. per config.
- [ ] Checkpoint/multi-point map renders on OpenStreetMap tiles (Leaflet).
- [ ] Diagram renders (Mermaid).
- [ ] Caption + source note show (chart/map config `caption`/`source`).
- [ ] Mobile: visualizations are responsive.
- [ ] Keyboard: map pan/zoom reachable (Leaflet); page remains navigable.
- [ ] Fallback: with JS disabled / on error, the figure shows its
  `aria-label`/"Visualization unavailable" text rather than breaking.
- [ ] Libraries load only when a viz scrolls into view (Network panel).

Automated: `tests/Unit/MarkdownTest.php` covers viz block output; `npm run build`
verifies the bundles + per-type code splitting.

## Manual QA — Stage 8 (Video hub)

Create `fn_video` posts; set the Video meta (source URL, aspect, poster,
captions `lang|label|url`, live flag); assign Shows/Topics.

- [ ] Upload a 16:9 video → plays in the **Video.js** player at 16:9.
- [ ] Upload a 9:16 video → plays at 9:16; appears in the vertical **Shorts** strip.
- [ ] Captions track shows in the player; chapters track loads if set.
- [ ] Video hub (`/videos/`): hero, live slot, latest grid, shows row, topics row.
- [ ] A 9:16 strip is inserted **every 3 rows** (default; `foldednews/video/strip_every`).
- [ ] Video search filters the hub.
- [ ] Media URLs are CDN (Stage 5 offload), not local uploads.
- [ ] Dark viewing surface on hub + single video; mobile responsive.
- [ ] Video.js + its CSS load only on pages that contain a player (Network panel).

Automated: `tests/Unit/VideoTest.php`; PHPStan level 5 covers the module;
`npm run build` verifies the lazy-loaded player bundle.

## Manual QA — Stage 9 (Podcast hub)

- [ ] Create a Show (a `podcast_show` term). Internal mode: add an episode
  (`fn_podcast`) with an audio URL (S3/CDN) → it appears on the show page and hub.
- [ ] Generated feed: visit `/feed/podcast?podcast_show={slug}` → valid podcast
  RSS with `<enclosure>` (CDN audio) and `<itunes:duration>` (validate at
  https://podba.se/validate/ or castfeedvalidator.com).
- [ ] External mode: set a Show's mode to `external_rss` + an **RSS.com** feed URL;
  trigger sync (`wp acorn` cron or `do_action('fn_podcast_sync')`) → episodes
  import, preserving the external GUID.
- [ ] Re-run sync → **no duplicates** (GUID dedup).
- [ ] Hub: hero, show grid, category filters, latest episodes; episode page has
  audio player, show notes, transcript; show page has subscribe + RSS links.
- [ ] Enclosure/audio URLs are CDN, not local uploads.

Automated: `tests/Unit/PodcastTest.php`; PHPStan level 5 covers Importer/Feed/module.

## Manual QA — Stage 10 (Accounts + Stripe)

Set `STRIPE_*` env vars; configure the webhook endpoint in the Stripe dashboard
(`/wp-json/foldednews/v1/stripe/webhook`); use `stripe listen` for local dev.

- [ ] Create a free WordPress account; visit `/account/` (redirects to login if out).
- [ ] Subscribe with test card `4242 4242 4242 4242` (Checkout) → return to account,
  status becomes **Active member** (via `checkout.session.completed` +
  `customer.subscription.created`).
- [ ] Manage billing opens the **Customer Portal**; cancel → access reverts to Free
  (`customer.subscription.deleted`).
- [ ] Renew / resume → active again.
- [ ] Failed payment (`4000 0000 0000 0341`) → status `past_due`
  (`invoice.payment_failed`).
- [ ] Re-send a webhook from the dashboard → handled **idempotently** (no double
  processing).
- [ ] Invalid signature is rejected (400).

Automated: `tests/Unit/WebhookTest.php` verifies signature + replay protection;
PHPStan level 5 covers the billing module.

## Per-stage checklists

Each stage appends its manual QA checklist here as it lands (content model,
theme layouts, editor, media offload, live blog, maps, video, podcast,
subscriptions, meter, newsletters, ads, AI, bookmarks, desktop mode, extension,
health, performance, hardening).
