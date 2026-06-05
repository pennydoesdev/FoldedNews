<?php

namespace FoldedNews\Newsroom\Billing;

/**
 * Minimal Stripe API client over the WordPress HTTP API (no SDK). Implements the
 * subset we use: Checkout Sessions and Customer Portal Sessions.
 * `stripe/stripe-php` is the documented drop-in for full coverage.
 *
 * @link https://docs.stripe.com/api
 */
final class StripeClient
{
    public function __construct(private readonly string $secretKey) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>|null
     */
    public function checkoutSession(array $params): ?array
    {
        return $this->post('/v1/checkout/sessions', $params);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>|null
     */
    public function portalSession(array $params): ?array
    {
        return $this->post('/v1/billing_portal/sessions', $params);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>|null
     */
    private function post(string $path, array $params): ?array
    {
        if ($this->secretKey === '') {
            return null;
        }

        $response = wp_remote_post('https://api.stripe.com'.$path, [
            'headers' => [
                'Authorization' => 'Bearer '.$this->secretKey,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => self::encode($params),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $data = json_decode((string) wp_remote_retrieve_body($response), true);

        return is_array($data) ? $data : null;
    }

    /**
     * Stripe form-encoding with bracketed nested keys (e.g. line_items[0][price]).
     *
     * @param  array<string, mixed>  $params
     */
    private static function encode(array $params, string $prefix = ''): string
    {
        $pairs = [];

        foreach ($params as $key => $value) {
            $name = $prefix === '' ? (string) $key : $prefix.'['.$key.']';

            if (is_array($value)) {
                $nested = self::encode($value, $name);
                if ($nested !== '') {
                    $pairs[] = $nested;
                }
            } else {
                $pairs[] = rawurlencode($name).'='.rawurlencode(is_bool($value) ? ($value ? 'true' : 'false') : (string) $value);
            }
        }

        return implode('&', $pairs);
    }
}
