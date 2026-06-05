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

## Live coverage (Stage 6)

Live blog update stream (newest/oldest, pinned key update, "what we know / do
not know", reporter + editor approval, correction labels, `LiveBlogPosting`
schema) and TimelineJS-style timelines.
