# Webhooks

> Status: skeleton. Populated in **Stage 10 (Stripe subscriptions)** and
> **Stage 13 (advertising invoices)**.

## Stripe (Stage 10)

- **Official docs:** https://docs.stripe.com/webhooks
- **SDK / package:** `stripe/stripe-php` (https://github.com/stripe/stripe-php)
- **Auth:** secret API key (server-side); webhook signature secret
- **Required env vars:** `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET`
- **Webhook docs:** https://docs.stripe.com/webhooks/signature ·
  events: https://docs.stripe.com/api/events/types
- **Rate limits:** https://docs.stripe.com/rate-limits
- **Testing:** Stripe CLI `stripe listen --forward-to <url>` and
  `stripe trigger <event>`; test cards https://docs.stripe.com/testing

### Endpoint design (Stage 10)

- Single REST route with a `permission_callback` that verifies the Stripe
  signature using `STRIPE_WEBHOOK_SECRET` before any processing.
- **Idempotent**: persist processed Stripe event IDs; ignore duplicates.
- Stripe = billing source of truth; WordPress = identity/access/newsletters.

### Handled events (Stage 10)

`checkout.session.completed`, `customer.*`, `customer.subscription.*`,
`invoice.*`, `payment_intent.*`, `payment_method.*`,
`billing_portal.session.created` (full list in the stage implementation).
