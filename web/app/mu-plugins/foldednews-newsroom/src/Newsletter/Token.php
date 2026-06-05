<?php

namespace FoldedNews\Newsroom\Newsletter;

/**
 * Signs unsubscribe / preference links so they can't be forged. Pure (the key is
 * passed in — the module derives it from wp_salt) so it is unit-testable.
 */
final class Token
{
    public static function sign(string $email, string $secret): string
    {
        return hash_hmac('sha256', strtolower(trim($email)), $secret);
    }

    public static function verify(string $email, string $token, string $secret): bool
    {
        return $token !== '' && hash_equals(self::sign($email, $secret), $token);
    }
}
