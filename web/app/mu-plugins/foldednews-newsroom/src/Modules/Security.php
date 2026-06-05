<?php

namespace FoldedNews\Newsroom\Modules;

use FoldedNews\Newsroom\Module;
use WP_Error;
use WP_REST_Request;

/**
 * Hardening: per-IP rate limits on public mutating endpoints, executable-upload
 * blocking, and baseline security headers. (Per-endpoint nonce/capability checks,
 * prepared queries, and webhook signature verification live in their modules.)
 */
final class Security implements Module
{
    /** @var list<string> public + mutating routes to rate-limit */
    private const GUARDED = [
        '/foldednews/v1/newsletter/subscribe',
        '/foldednews/v1/newsletter/preferences',
        '/foldednews/v1/meter/unlock',
        '/foldednews/v1/ads/impression',
        '/foldednews/v1/bookmarks/toggle',
    ];

    private const LIMIT = 60; // requests per minute per IP per route

    public function register(): void
    {
        add_filter('rest_pre_dispatch', [$this, 'rateLimit'], 10, 3);
        add_filter('upload_mimes', [$this, 'restrictMimes']);
        add_filter('wp_handle_upload_prefilter', [$this, 'blockExecutables']);
        add_action('send_headers', [$this, 'headers']);
    }

    /**
     * @param  mixed  $result
     * @return mixed
     */
    public function rateLimit($result, $server, $request)
    {
        if (! $request instanceof WP_REST_Request || $request->get_method() !== 'POST') {
            return $result;
        }
        if (! in_array($request->get_route(), self::GUARDED, true)) {
            return $result;
        }

        $key = 'fn_rl_'.md5($request->get_route().'|'.$this->ip());
        $count = (int) get_transient($key);
        if ($count >= self::LIMIT) {
            return new WP_Error('rate_limited', __('Too many requests. Please try again shortly.', 'foldednews'), ['status' => 429]);
        }
        set_transient($key, $count + 1, 60);

        return $result;
    }

    /**
     * @param  array<string, string>  $mimes
     * @return array<string, string>
     */
    public function restrictMimes(array $mimes): array
    {
        foreach (['exe', 'php', 'php3', 'php4', 'php5', 'phtml', 'pht', 'js', 'htaccess', 'sh', 'bat', 'cmd', 'com'] as $bad) {
            unset($mimes[$bad]);
        }

        return $mimes;
    }

    /**
     * @param  array<string, mixed>  $file
     * @return array<string, mixed>
     */
    public function blockExecutables(array $file): array
    {
        $name = strtolower((string) ($file['name'] ?? ''));
        if (preg_match('/\.(php\d?|phtml|pht|exe|sh|bat|cmd|com|js|htaccess)(\.|$)/', $name)) {
            $file['error'] = __('This file type is not allowed.', 'foldednews');
        }

        return $file;
    }

    public function headers(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-Frame-Options: SAMEORIGIN');
    }

    private function ip(): string
    {
        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) $_SERVER['REMOTE_ADDR']) : '0';
    }
}
