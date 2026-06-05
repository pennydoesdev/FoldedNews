# Webhooks

> Status: **Stage 10 implemented** (subscriptions). Stage 13 reuses this for
> advertising invoices.

## Implementation

- Endpoint: `POST /wp-json/foldednews/v1/stripe/webhook` (public; authenticated by
  signature, not WP auth).
- **Signature verified** before any processing via `Billing\Webhook::verify`
  (HMAC-SHA256 over `{timestamp}.{payload}`, constant-time compare, 300s replay
  tolerance) — unit-tested in `tests/Unit/WebhookTest.php`.
- **Idempotent**: each Stripe event ID is recorded (transient); duplicates return
  200 without reprocessing.
- Stripe = billing truth; WordPress mirrors subscription status into user meta
  (`_fn_subscription_status`, `_fn_stripe_customer`, `_fn_period_end`) and that
  drives access (`Billing\Account::isMember`, used by the Stage 11 meter).
- Signup via Checkout (`/billing/checkout`), self-service via Customer Portal
  (`/billing/portal`). Client: lean `Billing\StripeClient` over the WP HTTP API
  (`stripe/stripe-php` is the documented drop-in).


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
