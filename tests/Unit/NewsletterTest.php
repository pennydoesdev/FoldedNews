<?php

use FoldedNews\Newsroom\Newsletter\Token;

$mu = dirname(__DIR__, 2).'/web/app/mu-plugins/foldednews-newsroom';
require_once $mu.'/src/Newsletter/Token.php';

it('signs and verifies unsubscribe tokens (case-insensitive email)', function () {
    $secret = 'salt_x';
    $token = Token::sign('Reader@Example.com', $secret);

    expect(Token::verify('reader@example.com', $token, $secret))->toBeTrue();
    expect(Token::verify('reader@example.com', $token, 'other'))->toBeFalse();
    expect(Token::verify('someone@else.com', $token, $secret))->toBeFalse();
    expect(Token::verify('reader@example.com', '', $secret))->toBeFalse();
});

it('formats rows to CSV', function () use ($mu) {
    require_once $mu.'/src/Newsletter/Contacts.php';

    $csv = \FoldedNews\Newsroom\Newsletter\Contacts::toCsv([
        ['email', 'name', 'status'],
        ['ann@example.com', 'Ann Reporter', 'subscribed'],
    ]);

    expect($csv)
        ->toContain('email,name,status')
        ->toContain('ann@example.com,Ann Reporter,subscribed');
});
