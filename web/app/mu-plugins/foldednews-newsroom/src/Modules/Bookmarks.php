<?php

namespace FoldedNews\Newsroom\Modules;

use FoldedNews\Newsroom\Bookmarks\Bookmarks as Store;
use FoldedNews\Newsroom\Module;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Bookmark REST endpoints (cookie-authenticated). Toggle for logged-in readers;
 * merge guest (localStorage) bookmarks on login.
 */
final class Bookmarks implements Module
{
    public function register(): void
    {
        add_action('rest_api_init', [$this, 'routes']);
    }

    public function routes(): void
    {
        $authed = static fn (): bool => is_user_logged_in();

        register_rest_route('foldednews/v1', '/bookmarks/toggle', ['methods' => 'POST', 'permission_callback' => $authed, 'callback' => [$this, 'toggle']]);
        register_rest_route('foldednews/v1', '/bookmarks/merge', ['methods' => 'POST', 'permission_callback' => $authed, 'callback' => [$this, 'merge']]);
    }

    public function toggle(WP_REST_Request $request): WP_REST_Response
    {
        $postId = (int) $request->get_param('post_id');
        if ($postId <= 0) {
            return new WP_REST_Response(['error' => 'bad_request'], 400);
        }

        return new WP_REST_Response(['saved' => Store::toggle(get_current_user_id(), $postId)], 200);
    }

    public function merge(WP_REST_Request $request): WP_REST_Response
    {
        $ids = array_map('intval', (array) $request->get_param('ids'));

        return new WP_REST_Response(['ok' => true, 'count' => Store::merge(get_current_user_id(), $ids)], 200);
    }
}
