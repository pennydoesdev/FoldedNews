<?php

namespace FoldedNews\Newsroom\Modules;

use FoldedNews\Newsroom\Meter\Meter as MeterService;
use FoldedNews\Newsroom\Module;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Wires the article meter: evaluate (and record) the view early — before output,
 * so the anonymous cookie can be set — the ad-unlock REST endpoint, and the
 * "free" / "public safety" article flags that bypass the meter.
 */
final class Meter implements Module
{
    public function register(): void
    {
        add_action('init', [$this, 'registerMeta']);
        add_action('template_redirect', [$this, 'evaluate'], 5);
        add_action('rest_api_init', [$this, 'routes']);
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_action('save_post_fn_article', [$this, 'saveFlags'], 10, 1);
    }

    public function registerMeta(): void
    {
        $auth = static fn (): bool => current_user_can('edit_posts');
        foreach (['_fn_free', '_fn_public_safety'] as $key) {
            register_post_meta('fn_article', $key, ['type' => 'boolean', 'single' => true, 'show_in_rest' => true, 'auth_callback' => $auth]);
        }
    }

    public function evaluate(): void
    {
        if (is_singular('fn_article')) {
            MeterService::evaluate((int) get_queried_object_id());
        }
    }

    public function routes(): void
    {
        register_rest_route('foldednews/v1', '/meter/unlock', [
            'methods' => 'POST',
            'permission_callback' => '__return_true',
            'callback' => [$this, 'unlock'],
        ]);
    }

    public function unlock(WP_REST_Request $request): WP_REST_Response
    {
        $postId = (int) $request->get_param('post');
        $nonce = (string) $request->get_param('nonce');

        if (wp_verify_nonce($nonce, 'fn_meter') === false) {
            return new WP_REST_Response(['error' => 'bad_nonce'], 403);
        }

        if ($postId <= 0 || get_post_type($postId) !== 'fn_article') {
            return new WP_REST_Response(['error' => 'bad_request'], 400);
        }

        // Stage 13 plugs in ad-completion verification here. For now the ad gate
        // is client-side; the unlock grants timed access to this one article.
        MeterService::unlock($postId);

        return new WP_REST_Response(['unlocked' => true, 'ttl' => MeterService::unlockTtl()], 200);
    }

    public function addMetaBox(): void
    {
        add_meta_box('fn-meter', __('Access', 'foldednews'), [$this, 'renderBox'], 'fn_article', 'side', 'default');
    }

    public function renderBox(WP_Post $post): void
    {
        wp_nonce_field('fn_meter_flags', 'fn_meter_flags_nonce');
        printf('<p><label><input type="checkbox" name="fn_free" %s> %s</label></p>', checked((bool) get_post_meta($post->ID, '_fn_free', true), true, false), esc_html__('Free — never counts toward the meter', 'foldednews'));
        printf('<p><label><input type="checkbox" name="fn_public_safety" %s> %s</label></p>', checked((bool) get_post_meta($post->ID, '_fn_public_safety', true), true, false), esc_html__('Public safety — always bypasses the meter', 'foldednews'));
    }

    public function saveFlags(int $postId): void
    {
        $nonce = isset($_POST['fn_meter_flags_nonce']) ? sanitize_key((string) $_POST['fn_meter_flags_nonce']) : '';
        if ($nonce === '' || wp_verify_nonce($nonce, 'fn_meter_flags') === false || ! current_user_can('edit_post', $postId)) {
            return;
        }

        update_post_meta($postId, '_fn_free', isset($_POST['fn_free']));
        update_post_meta($postId, '_fn_public_safety', isset($_POST['fn_public_safety']));
    }
}
