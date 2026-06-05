# Advertising Platform

> Status: skeleton. Populated in **Stage 13 (Advertiser portal + ad platform)**.

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
