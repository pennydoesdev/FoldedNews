<?php

namespace FoldedNews\Newsroom\Modules;

use FoldedNews\Newsroom\Module;
use FoldedNews\Newsroom\Support\MarkdownToBlocks;
use WP_Post;

/**
 * Markdown-over-Gutenberg authoring layer.
 *
 * Gutenberg stays the block engine. `_newsroom_markdown_source` is the canonical
 * Markdown; on save it is converted to block HTML stored in `post_content`.
 * Conversion only runs when the Markdown changed (hash guard), so direct block
 * edits are preserved and Gutenberg compatibility stays intact.
 */
final class Markdown implements Module
{
    /** @var list<string> */
    private const TYPES = [
        'fn_article', 'fn_live_blog', 'fn_live_update',
        'fn_timeline_event', 'fn_video', 'fn_podcast', 'fn_newsletter',
    ];

    private const SOURCE_META = '_newsroom_markdown_source';
    private const HASH_META = '_newsroom_markdown_hash';

    private static bool $syncing = false;

    public function register(): void
    {
        add_action('init', [$this, 'registerMeta']);
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_action('save_post', [$this, 'save'], 10, 2);
    }

    public function registerMeta(): void
    {
        foreach (self::TYPES as $type) {
            register_post_meta($type, self::SOURCE_META, [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => 'sanitize_textarea_field',
                'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
            ]);
        }
    }

    public function addMetaBox(): void
    {
        add_meta_box('fn-markdown', __('Newsroom Markdown', 'foldednews'), [$this, 'render'], self::TYPES, 'normal', 'high');
    }

    public function render(WP_Post $post): void
    {
        wp_nonce_field('fn_markdown_save', 'fn_markdown_nonce');
        $value = get_post_meta($post->ID, self::SOURCE_META, true);
        $wsUrl = getenv('FN_COLLAB_WS_URL');

        echo '<p class="description">'
            . esc_html__('Author in Markdown (Milkdown). Saved as the canonical source and converted to Gutenberg blocks.', 'foldednews')
            . '</p>';

        // Canonical source: the editor mounts over this; without JS it stays a textarea.
        printf(
            '<textarea id="fn-markdown-source" name="fn_markdown_source" rows="18" style="width:100%%;font-family:monospace;" spellcheck="false">%s</textarea>',
            esc_textarea(is_string($value) ? $value : '')
        );
        printf(
            '<div data-fn-milkdown data-textarea="fn-markdown-source" data-room="fn-%d" data-collab-ws="%s" class="fn-milkdown" style="min-height:60vh;border:1px solid #dcdcde;border-radius:6px;margin-top:8px;"></div>',
            $post->ID,
            esc_attr(is_string($wsUrl) ? $wsUrl : '')
        );
    }

    public function save(int $post_id, WP_Post $post): void
    {
        if (self::$syncing) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (wp_is_post_revision($post_id) || ! in_array($post->post_type, self::TYPES, true)) {
            return;
        }
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        // Persist the Markdown source from the classic meta box (Gutenberg uses REST).
        $nonce = isset($_POST['fn_markdown_nonce']) ? sanitize_key((string) $_POST['fn_markdown_nonce']) : '';
        if ($nonce !== '' && wp_verify_nonce($nonce, 'fn_markdown_save') && isset($_POST['fn_markdown_source'])) {
            update_post_meta(
                $post_id,
                self::SOURCE_META,
                sanitize_textarea_field((string) wp_unslash($_POST['fn_markdown_source']))
            );
        }

        $markdown = get_post_meta($post_id, self::SOURCE_META, true);
        if (! is_string($markdown) || trim($markdown) === '') {
            return;
        }

        $hash = md5($markdown);
        if (get_post_meta($post_id, self::HASH_META, true) === $hash) {
            return; // unchanged — keep any direct block edits
        }

        self::$syncing = true;
        wp_update_post([
            'ID' => $post_id,
            'post_content' => MarkdownToBlocks::convert($markdown),
        ]);
        update_post_meta($post_id, self::HASH_META, $hash);
        self::$syncing = false;
    }
}
