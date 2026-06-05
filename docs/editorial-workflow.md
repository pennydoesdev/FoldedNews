# Editorial Workflow

> Status: skeleton. Populated across **Stage 2 (content model)**,
> **Stage 4 (Markdown editor)**, and **Stage 6 (live blog/timeline)**.

## Content model (Stage 2 — implemented)

Registered by the `foldednews-newsroom` mu-plugin (modules: `ContentTypes`,
`Taxonomies`, `Meta`, `Schema`).

Post types (`fn_` prefixed): Articles, Live Blogs, Live Updates, Timeline
Events, People, Organizations, Places, Videos, Podcasts, Newsletters,
Campaigns, Contacts, Corrections, Source Notes, Editorial Reviews. **Topics**
are modelled as a taxonomy (`topic`) because articles *relate to* topics;
article templates are the `article_format` taxonomy (seeded with the 10 below).

Article meta: `_fn_dek` (standfirst), `_fn_byline` (People IDs), `_fn_source_notes`,
`_fn_corrections`. People meta: `_fn_role`, `_fn_social`. All REST-exposed.
Published/updated dates use native `post_date`/`post_modified`. A `NewsArticle`
JSON-LD block (`datePublished`/`dateModified`) is emitted on single articles.

Article templates: Standard News, Breaking News, Investigation, Analysis,
Explainer, Feature, Live Article, Timeline Article, Video Article, Podcast
Article.

Every article supports: headline, dek/standfirst, author card (photo, name,
role, social), published + updated dates, `NewsArticle` schema
(`datePublished`/`dateModified`), featured image, topic relationships, source
notes, correction notices.

## Authoring (Stage 4 — implemented, foundation)

Gutenberg stays the block engine; Markdown is the authoring interface.
`_newsroom_markdown_source` (REST-exposed) is canonical; on save the `Markdown`
module converts it to Gutenberg block HTML in `post_content` via the pure-PHP
`MarkdownToBlocks` service. A hash guard (`_newsroom_markdown_hash`) means
conversion only runs when the Markdown changes, so direct block edits survive
and Gutenberg compatibility is intact. A classic meta box provides the authoring
textarea today.

Supported: headings, paragraphs, lists, blockquotes, fenced code, rules, inline
`**bold**`/`*italic*`/`` `code` ``/`[links]()` (HTML escaped, safe URL schemes),
and `:::` directives (`note`, `context`, `timeline`, `quote`, `source`,
`correction`, `live-update`, `map`, `chart`, `diagram`) → group blocks with
`fn-*` classNames.

Next increments: a Gutenberg sidebar editor (React) + real custom blocks
(Stage 7 replaces map/chart/diagram group placeholders with interactive blocks).

## Review & corrections

Editorial review states, corrections ledger, source notes. AI editorial output
is never auto-published; legal/breaking claims require human approval
(`AiSafetyService`, Stage 14).

## Live coverage (Stage 6 — implemented)

Live blogs (`fn_live_blog`) aggregate approved updates (`fn_live_update`) linked
by `_fn_live_blog`. Each update carries reporter, editor `_fn_approved`,
`_fn_pinned`, `_fn_correction` label, and `_fn_sources`. The single live-blog
template (`LiveBlog` composer) renders "what we know / do not know", the pinned
key update, and the newest/oldest-toggled stream; a public REST endpoint
(`/wp-json/foldednews/v1/live/{id}/updates?since=`) + `resources/js/live-blog.js`
poll for new updates (auto-refresh, stops when archived). `LiveBlogPosting`
JSON-LD (with `liveBlogUpdate` entries) is emitted by the Schema module.

Timelines (`fn_timeline_event`, `_fn_event_date`/`_fn_source`) render via the
`timeline` component grouped by year, vertical (mobile-first) or horizontal,
on the timeline archive (`TimelineArchive` composer).

## Podcasts (Stage 9 — implemented)

Shows are a `podcast_show` taxonomy (term meta: mode, import feed URL, artwork,
Apple/Spotify/YouTube links); episodes are `fn_podcast` (`_fn_audio`, `_fn_guid`,
`_fn_duration`, `_fn_premium`, transcript, chapters). **Internal** mode generates
an iTunes RSS feed at `/feed/podcast?podcast_show={slug}` (CDN enclosures).
**External RSS** mode (RSS.com and any podcast feed) imports via core SimpleXML,
dedupes by GUID, and syncs hourly (`fn_podcast_sync`); `rss_com_api` mode falls
back to the public feed when no API credentials are set.
