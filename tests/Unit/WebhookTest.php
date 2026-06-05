<?php

use FoldedNews\Newsroom\Billing\Webhook;

require_once dirname(__DIR__, 2).'/web/app/mu-plugins/foldednews-newsroom/src/Billing/Webhook.php';

function fn_stripe_header(string $payload, string $secret, ?int $time = null): string
{
    $time ??= time();

    return "t={$time},v1=".hash_hmac('sha256', $time.'.'.$payload, $secret);
}

it('accepts a valid Stripe webhook signature', function () {
    $secret = 'whsec_test';
    $payload = '{"id":"evt_1","type":"invoice.paid"}';

    expect(Webhook::verify($payload, fn_stripe_header($payload, $secret), $secret))->toBeTrue();
});

it('rejects tampered payloads and wrong secrets', function () {
    $secret = 'whsec_test';
    $payload = '{"id":"evt_1"}';
    $header = fn_stripe_header($payload, $secret);

    expect(Webhook::verify($payload.'x', $header, $secret))->toBeFalse();
    expect(Webhook::verify($payload, $header, 'whsec_other'))->toBeFalse();
});

it('rejects stale timestamps (replay protection)', function () {
    $secret = 'whsec_test';
    $payload = '{"id":"evt_1"}';

    expect(Webhook::verify($payload, fn_stripe_header($payload, $secret, time() - 1000), $secret))->toBeFalse();
});

it('rejects empty signature or secret', function () {
    expect(Webhook::verify('{}', '', 'whsec_test'))->toBeFalse();
    expect(Webhook::verify('{}', 't=1,v1=abc', ''))->toBeFalse();
});
