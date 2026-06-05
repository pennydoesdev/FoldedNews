<?php

namespace FoldedNews\Newsroom\Billing;

/**
 * Verifies Stripe webhook signatures (the `Stripe-Signature` header) without the
 * SDK. Constant-time compare + timestamp tolerance to prevent replay. Mirrors
 * Stripe's documented scheme: HMAC-SHA256 over "{timestamp}.{payload}".
 *
 * @link https://docs.stripe.com/webhooks/signature
 */
final class Webhook
{
    public static function verify(string $payload, string $signatureHeader, string $secret, int $tolerance = 300): bool
    {
        if ($secret === '' || $signatureHeader === '') {
            return false;
        }

        $timestamp = 0;
        $signatures = [];

        foreach (explode(',', $signatureHeader) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) !== 2) {
                continue;
            }
            if ($pair[0] === 't') {
                $timestamp = (int) $pair[1];
            } elseif ($pair[0] === 'v1') {
                $signatures[] = $pair[1];
            }
        }

        if ($timestamp <= 0 || $signatures === []) {
            return false;
        }

        if ($tolerance > 0 && abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        foreach ($signatures as $candidate) {
            if (hash_equals($expected, $candidate)) {
                return true;
            }
        }

        return false;
    }
}
