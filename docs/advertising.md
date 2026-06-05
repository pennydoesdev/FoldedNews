# Advertising Platform

> Status: **Stage 13 implemented (foundation).** Advertiser role, Creative CPT
> with admin approval, weighted ad server, impression/click tracking + metrics,
> first-party interest signals, and pause-on-billing-failure. Front-end
> self-serve portal, full invoice UI, and segment/trending engine are follow-ups.

## Implementation

- **Roles:** `advertiser` (manages own `fn_creative`; no article access). Admins/
  sales approve creatives (`edit_others_fn_creatives`) and manage campaigns.
- **Model:** `fn_creative` (campaign, placement/format, click URL, image, weight,
  `_fn_approved`) under `fn_campaign` (status active/paused, pricing model + price).
- **Serving:** `AdServer::select(placement)` → weighted `Selector` over approved
  creatives in active campaigns → `<x-ad-slot placement="…"/>` (leaderboard +
  in-article wired; all 17 placements selectable). "Advertisement" labelled,
  `rel="sponsored nofollow"`.
- **Tracking:** clicks via `GET /ads/click?c=` (record + 302 to advertiser URL);
  viewable impressions beaconed (`POST /ads/impression`, ≥50% for 1s) to a
  dedicated `fn_ad_events` table (atomic upsert). Metrics dashboard aggregates
  impressions/clicks/CTR per campaign.
- **Billing:** reuses the Stage 10 Stripe client/webhook — an ad invoice with
  `metadata.fn_campaign` that fails (`invoice.payment_failed`) pauses the campaign.

## Audience intelligence — privacy rules (enforced)

Publisher-owned advertising: advertisers, sales team, admins. Advertiser portal
(accounts, campaigns, creatives, placements, billing, metrics). Ad formats
(billboard, leaderboard, MPU, sticky footer, in-article, takeover, newsletter,
podcast, video pre/mid-roll, sponsorships). Billing via Stripe (CPM, CPC, flat,
time-based, newsletter/podcast sponsorship).

## Billing adapter

- **Official docs:** Stripe Invoicing https://docs.stripe.com/invoicing
- **SDK / package:** `stripe/stripe-php`
- **Auth:** secret API key + webhook signature
- **Required env vars:** `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET`
- **Webhook docs:** https://docs.stripe.com/webhooks (shared with `docs/webhooks.md`)
- **Rate limits:** https://docs.stripe.com/rate-limits
- **Testing:** Stripe CLI + test invoices.

## Metrics

Impressions, clicks, CTR, viewability, reach, frequency, revenue (by placement /
campaign / advertiser).

## Audience intelligence — privacy rules

First-party only: topics read, sections visited, newsletters, podcasts, videos,
interest graph, behavioral segments, trending engine. **Prohibited:**
fingerprinting, cross-site identity graphs, undisclosed sensitive profiling,
browser privacy circumvention.

Implemented: `Ads\Interest` records topic-view counts for **logged-in readers
only** (user meta) — no anonymous profiling, no cookies-for-tracking, no
fingerprinting. This feeds contextual targeting + a trending foundation; the
full segment/trending engine is a follow-up.
