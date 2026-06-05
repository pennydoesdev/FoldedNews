# Editorial Workflow

> Status: skeleton. Populated across **Stage 2 (content model)**,
> **Stage 4 (Markdown editor)**, and **Stage 6 (live blog/timeline)**.

## Content model (Stage 2)

Content types: Articles, Live Blogs, Live Updates, Timeline Events, People,
Organizations, Places, Topics, Videos, Podcasts, Newsletters, Campaigns,
Contacts, Corrections, Source Notes, Editorial Reviews.

Article templates: Standard News, Breaking News, Investigation, Analysis,
Explainer, Feature, Live Article, Timeline Article, Video Article, Podcast
Article.

Every article supports: headline, dek/standfirst, author card (photo, name,
role, social), published + updated dates, `NewsArticle` schema
(`datePublished`/`dateModified`), featured image, topic relationships, source
notes, correction notices.

## Authoring (Stage 4)

Gutenberg stays the block engine; Markdown is the authoring interface.
`post_content` stores block HTML; `_newsroom_markdown_source` stores canonical
Markdown. Directives: `:::note`, `:::context`, `:::timeline`, `:::quote`,
`:::source`, `:::correction`, `:::live-update`, `:::map`, `:::chart`,
`:::diagram`.

## Review & corrections

Editorial review states, corrections ledger, source notes. AI editorial output
is never auto-published; legal/breaking claims require human approval
(`AiSafetyService`, Stage 14).

## Live coverage (Stage 6)

Live blog update stream (newest/oldest, pinned key update, "what we know / do
not know", reporter + editor approval, correction labels, `LiveBlogPosting`
schema) and TimelineJS-style timelines.
