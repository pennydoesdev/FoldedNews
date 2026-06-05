<?php

namespace FoldedNews\Newsroom\Meter;

use FoldedNews\Newsroom\Billing\Account;

/**
 * Server-side article meter. Members, free articles, and public-safety articles
 * bypass it. Logged-in readers store state in user meta (syncs across devices);
 * anonymous readers use an HMAC-signed cookie. Decisions are cached per request.
 */
final class Meter
{
    private const USER_META = '_fn_meter';
    private const COOKIE = 'fn_meter';

    /** @var array<int, bool> */
    private static array $decisions = [];

    public static function limit(): int
    {
        return max(1, (int) apply_filters('foldednews/meter/limit', 5));
    }

    public static function unlockTtl(): int
    {
        return max(60, (int) apply_filters('foldednews/meter/unlock_ttl', 24 * 60 * 60));
    }

    /**
     * Evaluate + record a view (call early, before output, so the cookie can be set).
     */
    public static function evaluate(int $postId): bool
    {
        if (! self::isMetered($postId)) {
            return self::$decisions[$postId] = true;
        }

        $userId = get_current_user_id();
        if ($userId > 0 && Account::isMember($userId)) {
            return self::$decisions[$postId] = true;
        }

        $result = Decision::evaluate(self::read(), $postId, gmdate('Ym'), self::limit(), time());
        self::write($result['state']);

        return self::$decisions[$postId] = (bool) $result['allowed'];
    }

    public static function allowed(int $postId): bool
    {
        return self::$decisions[$postId] ?? true;
    }

    public static function unlock(int $postId): void
    {
        self::write(Decision::unlock(self::read(), $postId, gmdate('Ym'), time() + self::unlockTtl()));
        self::$decisions[$postId] = true;
    }

    public static function count(): int
    {
        return Decision::count(self::read(), gmdate('Ym'));
    }

    private static function isMetered(int $postId): bool
    {
        return $postId > 0
            && get_post_type($postId) === 'fn_article'
            && ! get_post_meta($postId, '_fn_free', true)
            && ! get_post_meta($postId, '_fn_public_safety', true);
    }

    /**
     * @return array<string, mixed>
     */
    private static function read(): array
    {
        $userId = get_current_user_id();

        if ($userId > 0) {
            $data = get_user_meta($userId, self::USER_META, true);

            return is_array($data) ? $data : [];
        }

        return self::readCookie();
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private static function write(array $state): void
    {
        $userId = get_current_user_id();

        if ($userId > 0) {
            update_user_meta($userId, self::USER_META, $state);

            return;
        }

        self::writeCookie($state);
    }

    /**
     * @return array<string, mixed>
     */
    private static function readCookie(): array
    {
        $raw = isset($_COOKIE[self::COOKIE]) ? (string) $_COOKIE[self::COOKIE] : '';
        if ($raw === '' || ! str_contains($raw, '.')) {
            return [];
        }

        [$payload, $signature] = explode('.', $raw, 2);
        if (! hash_equals(self::sign($payload), $signature)) {
            return [];
        }

        $json = base64_decode($payload, true);
        $data = $json !== false ? json_decode($json, true) : null;

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private static function writeCookie(array $state): void
    {
        if (headers_sent()) {
            return;
        }

        $payload = base64_encode((string) wp_json_encode($state));
        $value = $payload.'.'.self::sign($payload);

        setcookie(self::COOKIE, $value, [
            'expires' => time() + 40 * 24 * 60 * 60,
            'path' => '/',
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $_COOKIE[self::COOKIE] = $value; // reflect within this request
    }

    private static function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, wp_salt('nonce'));
    }
}
