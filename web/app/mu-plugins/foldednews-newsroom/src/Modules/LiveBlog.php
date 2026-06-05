<?php

namespace FoldedNews\Newsroom\Modules;

use FoldedNews\Newsroom\Live\Updates;
use FoldedNews\Newsroom\Module;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Live blog + timeline domain: meta, authoring meta boxes, the auto-refresh
 * REST endpoint, and (with the Schema module) LiveBlogPosting output.
 */
final class LiveBlog implements Module
{
    public function register(): void
    {
        add_action('init', [$this, 'registerMeta']);
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('save_post_fn_live_update', [$this, 'saveUpdate'], 10, 1);
        add_action('save_post_fn_live_blog', [$this, 'saveBlog'], 10, 1);
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerMeta(): void
    {
        $auth = static fn (): bool => current_user_can('edit_posts');
        $bool = ['type' => 'boolean', 'single' => true, 'show_in_rest' => true, 'auth_callback' => $auth];
        $int = ['type' => 'integer', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'absint', 'auth_callback' => $auth];
        $text = ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field', 'auth_callback' => $auth];
        $area = ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_textarea_field', 'auth_callback' => $auth];
        $urls = [
            'type' => 'array',
            'single' => true,
            'show_in_rest' => ['schema' => ['type' => 'array', 'items' => ['type' => 'string']]],
            'sanitize_callback' => static fn ($v): array => array_values(array_filter(array_map('esc_url_raw', array_map('strval', (array) $v)))),
            'auth_callback' => $auth,
        ];

        register_post_meta('fn_live_update', Updates::PARENT_META, $int);
        register_post_meta('fn_live_update', Updates::APPROVED_META, $bool);
        register_post_meta('fn_live_update', Updates::PINNED_META, $bool);
        register_post_meta('fn_live_update', '_fn_reporter', $int);
        register_post_meta('fn_live_update', '_fn_correction', $text);
        register_post_meta('fn_live_update', '_fn_sources', $urls);

        register_post_meta('fn_live_blog', '_fn_what_we_know', $area);
        register_post_meta('fn_live_blog', '_fn_what_we_dont_know', $area);
        register_post_meta('fn_live_blog', '_fn_archived', $bool);

        register_post_meta('fn_timeline_event', '_fn_event_date', $text);
        register_post_meta('fn_timeline_event', '_fn_source', $text);
    }

    public function addMetaBoxes(): void
    {
        add_meta_box('fn-live-update', __('Live update', 'foldednews'), [$this, 'renderUpdateBox'], 'fn_live_update', 'side', 'high');
        add_meta_box('fn-live-blog', __('Live blog', 'foldednews'), [$this, 'renderBlogBox'], 'fn_live_blog', 'side', 'high');
    }

    public function renderUpdateBox(WP_Post $post): void
    {
        wp_nonce_field('fn_live_update_save', 'fn_live_update_nonce');
        $blog = (int) get_post_meta($post->ID, Updates::PARENT_META, true);
        $reporter = (int) get_post_meta($post->ID, '_fn_reporter', true);
        $correction = (string) get_post_meta($post->ID, '_fn_correction', true);
        $sources = Updates::sources($post);

        printf('<p><label>%s<br><input type="number" name="fn_live_blog" value="%d" style="width:100%%"></label></p>', esc_html__('Live blog ID', 'foldednews'), $blog);
        printf('<p><label><input type="checkbox" name="fn_approved" %s> %s</label></p>', checked((bool) get_post_meta($post->ID, Updates::APPROVED_META, true), true, false), esc_html__('Approved (editor)', 'foldednews'));
        printf('<p><label><input type="checkbox" name="fn_pinned" %s> %s</label></p>', checked((bool) get_post_meta($post->ID, Updates::PINNED_META, true), true, false), esc_html__('Pinned key update', 'foldednews'));
        printf('<p><label>%s<br><input type="number" name="fn_reporter" value="%d" style="width:100%%"></label></p>', esc_html__('Reporter (People ID)', 'foldednews'), $reporter);
        printf('<p><label>%s<br><input type="text" name="fn_correction" value="%s" style="width:100%%"></label></p>', esc_html__('Correction label', 'foldednews'), esc_attr($correction));
        printf('<p><label>%s<br><textarea name="fn_sources" rows="3" style="width:100%%">%s</textarea></label></p>', esc_html__('Source links (one per line)', 'foldednews'), esc_textarea(implode("\n", $sources)));
    }

    public function renderBlogBox(WP_Post $post): void
    {
        wp_nonce_field('fn_live_blog_save', 'fn_live_blog_nonce');
        printf('<p><label>%s<br><textarea name="fn_what_we_know" rows="4" style="width:100%%">%s</textarea></label></p>', esc_html__('What we know', 'foldednews'), esc_textarea((string) get_post_meta($post->ID, '_fn_what_we_know', true)));
        printf('<p><label>%s<br><textarea name="fn_what_we_dont_know" rows="4" style="width:100%%">%s</textarea></label></p>', esc_html__('What we do not know', 'foldednews'), esc_textarea((string) get_post_meta($post->ID, '_fn_what_we_dont_know', true)));
        printf('<p><label><input type="checkbox" name="fn_archived" %s> %s</label></p>', checked((bool) get_post_meta($post->ID, '_fn_archived', true), true, false), esc_html__('Archived', 'foldednews'));
    }

    public function saveUpdate(int $postId): void
    {
        if (! $this->canSave($postId, 'fn_live_update_nonce', 'fn_live_update_save')) {
            return;
        }

        update_post_meta($postId, Updates::PARENT_META, isset($_POST['fn_live_blog']) ? absint($_POST['fn_live_blog']) : 0);
        update_post_meta($postId, Updates::APPROVED_META, isset($_POST['fn_approved']));
        update_post_meta($postId, Updates::PINNED_META, isset($_POST['fn_pinned']));
        update_post_meta($postId, '_fn_reporter', isset($_POST['fn_reporter']) ? absint($_POST['fn_reporter']) : 0);
        update_post_meta($postId, '_fn_correction', isset($_POST['fn_correction']) ? sanitize_text_field((string) wp_unslash($_POST['fn_correction'])) : '');

        $sources = isset($_POST['fn_sources']) ? (string) wp_unslash($_POST['fn_sources']) : '';
        $urls = array_values(array_filter(array_map('esc_url_raw', array_map('trim', preg_split('/\r\n|\r|\n/', $sources) ?: []))));
        update_post_meta($postId, '_fn_sources', $urls);
    }

    public function saveBlog(int $postId): void
    {
        if (! $this->canSave($postId, 'fn_live_blog_nonce', 'fn_live_blog_save')) {
            return;
        }

        update_post_meta($postId, '_fn_what_we_know', isset($_POST['fn_what_we_know']) ? sanitize_textarea_field((string) wp_unslash($_POST['fn_what_we_know'])) : '');
        update_post_meta($postId, '_fn_what_we_dont_know', isset($_POST['fn_what_we_dont_know']) ? sanitize_textarea_field((string) wp_unslash($_POST['fn_what_we_dont_know'])) : '');
        update_post_meta($postId, '_fn_archived', isset($_POST['fn_archived']));
    }

    public function registerRoutes(): void
    {
        register_rest_route('foldednews/v1', '/live/(?P<id>\d+)/updates', [
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => [$this, 'restUpdates'],
        ]);
    }

    public function restUpdates(WP_REST_Request $request): WP_REST_Response
    {
        $blogId = (int) $request['id'];
        $since = (int) $request->get_param('since');
        $order = is_string($request->get_param('order')) ? (string) $request->get_param('order') : 'DESC';

        $blog = get_post($blogId);
        if (! $blog instanceof WP_Post || $blog->post_type !== 'fn_live_blog' || $blog->post_status !== 'publish') {
            return new WP_REST_Response(['updates' => [], 'latest' => $since, 'archived' => true], 200);
        }

        $payload = array_map([Updates::class, 'payload'], Updates::forBlog($blogId, $order, $since));
        $latest = $since;
        foreach ($payload as $entry) {
            $latest = max($latest, (int) $entry['timestamp']);
        }

        return new WP_REST_Response([
            'updates' => $payload,
            'latest' => $latest,
            'archived' => (bool) get_post_meta($blogId, '_fn_archived', true),
        ], 200);
    }

    private function canSave(int $postId, string $nonceField, string $action): bool
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }

        $nonce = isset($_POST[$nonceField]) ? sanitize_key((string) $_POST[$nonceField]) : '';

        return $nonce !== '' && wp_verify_nonce($nonce, $action) !== false && current_user_can('edit_post', $postId);
    }
}
