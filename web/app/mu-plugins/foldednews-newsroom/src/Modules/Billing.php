<?php

namespace FoldedNews\Newsroom\Modules;

use FoldedNews\Newsroom\Billing\Account;
use FoldedNews\Newsroom\Billing\StripeClient;
use FoldedNews\Newsroom\Billing\Webhook;
use FoldedNews\Newsroom\Module;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Stripe billing: Checkout for signup, Customer Portal for self-service billing,
 * and a signature-verified, idempotent webhook that mirrors subscription state
 * into WordPress (the access source of truth).
 */
final class Billing implements Module
{
    public function register(): void
    {
        add_action('rest_api_init', [$this, 'routes']);
    }

    public function routes(): void
    {
        $authed = static fn (): bool => is_user_logged_in();

        register_rest_route('foldednews/v1', '/billing/checkout', ['methods' => 'POST', 'permission_callback' => $authed, 'callback' => [$this, 'checkout']]);
        register_rest_route('foldednews/v1', '/billing/portal', ['methods' => 'POST', 'permission_callback' => $authed, 'callback' => [$this, 'portal']]);
        register_rest_route('foldednews/v1', '/stripe/webhook', ['methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => [$this, 'webhook']]);
    }

    private function client(): ?StripeClient
    {
        $key = getenv('STRIPE_SECRET_KEY');

        return is_string($key) && $key !== '' ? new StripeClient($key) : null;
    }

    public function checkout(WP_REST_Request $request): WP_REST_Response
    {
        $client = $this->client();
        $price = (string) getenv('STRIPE_PRICE_ID');
        $userId = get_current_user_id();

        if (! $client instanceof StripeClient || $price === '' || $userId === 0) {
            return new WP_REST_Response(['error' => 'unconfigured'], 400);
        }

        $session = $client->checkoutSession([
            'mode' => 'subscription',
            'line_items' => [['price' => $price, 'quantity' => 1]],
            'client_reference_id' => (string) $userId,
            'customer_email' => wp_get_current_user()->user_email,
            'success_url' => home_url('/account/?checkout=success'),
            'cancel_url' => home_url('/account/?checkout=cancel'),
            'allow_promotion_codes' => true,
        ]);

        return $this->urlResponse($session);
    }

    public function portal(WP_REST_Request $request): WP_REST_Response
    {
        $client = $this->client();
        $customer = (string) get_user_meta(get_current_user_id(), Account::CUSTOMER_META, true);

        if (! $client instanceof StripeClient || $customer === '') {
            return new WP_REST_Response(['error' => 'no_customer'], 400);
        }

        return $this->urlResponse($client->portalSession([
            'customer' => $customer,
            'return_url' => home_url('/account/'),
        ]));
    }

    public function webhook(WP_REST_Request $request): WP_REST_Response
    {
        $secret = (string) getenv('STRIPE_WEBHOOK_SECRET');
        $payload = $request->get_body();

        if (! Webhook::verify($payload, (string) $request->get_header('Stripe-Signature'), $secret)) {
            return new WP_REST_Response(['error' => 'invalid_signature'], 400);
        }

        $event = json_decode($payload, true);
        if (! is_array($event) || ! isset($event['id'], $event['type'])) {
            return new WP_REST_Response(['error' => 'bad_event'], 400);
        }

        // Idempotency: never process the same event twice.
        $key = 'fn_stripe_'.(string) $event['id'];
        if (get_transient($key)) {
            return new WP_REST_Response(['received' => true, 'idempotent' => true], 200);
        }
        set_transient($key, 1, 30 * 24 * 60 * 60); // 30 days

        $object = $event['data']['object'] ?? null;
        $this->dispatch((string) $event['type'], is_array($object) ? $object : []);

        return new WP_REST_Response(['received' => true], 200);
    }

    /**
     * @param  array<string, mixed>  $object
     */
    private function dispatch(string $type, array $object): void
    {
        $customerOf = static fn (): string => (string) ($object['customer'] ?? '');

        switch ($type) {
            case 'checkout.session.completed':
                $userId = (int) ($object['client_reference_id'] ?? 0);
                if ($userId > 0 && $customerOf() !== '') {
                    Account::linkCustomer($userId, $customerOf());
                    Account::setStatus($userId, 'active');
                }
                break;

            case 'customer.subscription.created':
            case 'customer.subscription.updated':
            case 'customer.subscription.resumed':
                if (($userId = Account::findUserByCustomer($customerOf())) > 0) {
                    Account::setStatus($userId, (string) ($object['status'] ?? 'active'), (int) ($object['current_period_end'] ?? 0));
                }
                break;

            case 'customer.subscription.paused':
                if (($userId = Account::findUserByCustomer($customerOf())) > 0) {
                    Account::setStatus($userId, 'paused');
                }
                break;

            case 'customer.subscription.deleted':
                if (($userId = Account::findUserByCustomer($customerOf())) > 0) {
                    Account::setStatus($userId, 'canceled');
                }
                break;

            case 'invoice.payment_failed':
            case 'invoice.payment_action_required':
                if (($userId = Account::findUserByCustomer($customerOf())) > 0) {
                    Account::setStatus($userId, 'past_due');
                }
                break;

            case 'invoice.paid':
            case 'invoice.payment_succeeded':
                if (($userId = Account::findUserByCustomer($customerOf())) > 0) {
                    Account::setStatus($userId, 'active');
                }
                break;

            case 'customer.deleted':
                if (($userId = Account::findUserByCustomer((string) ($object['id'] ?? ''))) > 0) {
                    delete_user_meta($userId, Account::CUSTOMER_META);
                    Account::setStatus($userId, 'canceled');
                }
                break;

            default:
                // customer.created/updated, invoice.created/finalized/voided/marked_uncollectible,
                // payment_intent.*, payment_method.*, billing_portal.session.created — acknowledged
                // and exposed for extension without changing access state.
                do_action('foldednews/stripe/event', $type, $object);
        }
    }

    /**
     * @param  array<string, mixed>|null  $session
     */
    private function urlResponse(?array $session): WP_REST_Response
    {
        if (is_array($session) && isset($session['url']) && is_string($session['url'])) {
            return new WP_REST_Response(['url' => $session['url']], 200);
        }

        return new WP_REST_Response(['error' => 'stripe_error'], 502);
    }
}
