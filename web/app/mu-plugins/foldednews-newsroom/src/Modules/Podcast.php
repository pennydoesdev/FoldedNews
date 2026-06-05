<?php

namespace FoldedNews\Newsroom\Modules;

use FoldedNews\Newsroom\Module;
use FoldedNews\Newsroom\Podcast\Feed;
use FoldedNews\Newsroom\Podcast\Importer;
use FoldedNews\Newsroom\Support\Labels;
use WP_Post;
use WP_Term;

/**
 * Podcast domain: Shows + Categories taxonomies, episode meta, the per-show
 * import config (term meta), the iTunes RSS feed, and the scheduled sync.
 */
final class Podcast implements Module
{
    /** @var list<string> */
    private const MODES = ['internal', 'external_rss', 'rss_com_api'];

    public function register(): void
    {
        add_action('init', [$this, 'boot']);
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_action('save_post_fn_podcast', [$this, 'saveEpisode'], 10, 1);

        // Show import config (term meta) admin fields.
        add_action('podcast_show_add_form_fields', [$this, 'addShowFields']);
        add_action('podcast_show_edit_form_fields', [$this, 'editShowFields']);
        add_action('created_podcast_show', [$this, 'saveShow']);
        add_action('edited_podcast_show', [$this, 'saveShow']);

        // Scheduled external sync.
        add_action('fn_podcast_sync', [Importer::class, 'syncAll']);
        if (! wp_next_scheduled('fn_podcast_sync')) {
            wp_schedule_event(time() + 300, 'hourly', 'fn_podcast_sync');
        }
    }

    public function boot(): void
    {
        register_taxonomy('podcast_show', ['fn_podcast'], [
            'labels' => Labels::taxonomy('Show', 'Shows'),
            'hierarchical' => false,
            'public' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'podcast-shows'],
        ]);

        register_taxonomy('podcast_category', ['fn_podcast'], [
            'labels' => Labels::taxonomy('Podcast Category', 'Podcast Categories'),
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'podcast-category'],
        ]);

        $auth = static fn (): bool => current_user_can('edit_posts');
        register_post_meta('fn_podcast', '_fn_audio', ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'esc_url_raw', 'auth_callback' => $auth]);
        register_post_meta('fn_podcast', '_fn_duration', ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field', 'auth_callback' => $auth]);
        register_post_meta('fn_podcast', '_fn_guid', ['type' => 'string', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => 'sanitize_text_field', 'auth_callback' => $auth]);
        register_post_meta('fn_podcast', '_fn_premium', ['type' => 'boolean', 'single' => true, 'show_in_rest' => true, 'auth_callback' => $auth]);
        register_post_meta('fn_podcast', '_fn_transcript', ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_textarea_field', 'auth_callback' => $auth]);
        register_post_meta('fn_podcast', '_fn_chapters', ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'esc_url_raw', 'auth_callback' => $auth]);

        register_term_meta('podcast_show', '_fn_mode', ['type' => 'string', 'single' => true, 'show_in_rest' => true]);
        register_term_meta('podcast_show', '_fn_feed_url', ['type' => 'string', 'single' => true, 'show_in_rest' => true]);
        register_term_meta('podcast_show', '_fn_artwork', ['type' => 'string', 'single' => true, 'show_in_rest' => true]);
        foreach (['apple', 'spotify', 'youtube'] as $svc) {
            register_term_meta('podcast_show', "_fn_{$svc}", ['type' => 'string', 'single' => true, 'show_in_rest' => true]);
        }

        add_feed('podcast', [Feed::class, 'render']);
    }

    public function addMetaBox(): void
    {
        add_meta_box('fn-podcast', __('Episode', 'foldednews'), [$this, 'renderEpisode'], 'fn_podcast', 'normal', 'high');
    }

    public function renderEpisode(WP_Post $post): void
    {
        wp_nonce_field('fn_podcast_save', 'fn_podcast_nonce');
        printf('<p><label>%s<br><input type="url" name="fn_audio" value="%s" style="width:100%%"></label></p>', esc_html__('Audio URL (S3/CDN or external enclosure)', 'foldednews'), esc_attr((string) get_post_meta($post->ID, '_fn_audio', true)));
        printf('<p><label>%s<br><input type="text" name="fn_duration" value="%s" style="width:160px" placeholder="00:32:10"></label></p>', esc_html__('Duration', 'foldednews'), esc_attr((string) get_post_meta($post->ID, '_fn_duration', true)));
        printf('<p><label>%s<br><input type="url" name="fn_chapters" value="%s" style="width:100%%"></label></p>', esc_html__('Chapters URL', 'foldednews'), esc_attr((string) get_post_meta($post->ID, '_fn_chapters', true)));
        printf('<p><label>%s<br><textarea name="fn_transcript" rows="5" style="width:100%%">%s</textarea></label></p>', esc_html__('Transcript', 'foldednews'), esc_textarea((string) get_post_meta($post->ID, '_fn_transcript', true)));
        printf('<p><label><input type="checkbox" name="fn_premium" %s> %s</label></p>', checked((bool) get_post_meta($post->ID, '_fn_premium', true), true, false), esc_html__('Premium episode', 'foldednews'));
    }

    public function saveEpisode(int $postId): void
    {
        $nonce = isset($_POST['fn_podcast_nonce']) ? sanitize_key((string) $_POST['fn_podcast_nonce']) : '';
        if ($nonce === '' || wp_verify_nonce($nonce, 'fn_podcast_save') === false || ! current_user_can('edit_post', $postId)) {
            return;
        }

        update_post_meta($postId, '_fn_audio', isset($_POST['fn_audio']) ? esc_url_raw((string) wp_unslash($_POST['fn_audio'])) : '');
        update_post_meta($postId, '_fn_duration', isset($_POST['fn_duration']) ? sanitize_text_field((string) wp_unslash($_POST['fn_duration'])) : '');
        update_post_meta($postId, '_fn_chapters', isset($_POST['fn_chapters']) ? esc_url_raw((string) wp_unslash($_POST['fn_chapters'])) : '');
        update_post_meta($postId, '_fn_transcript', isset($_POST['fn_transcript']) ? sanitize_textarea_field((string) wp_unslash($_POST['fn_transcript'])) : '');
        update_post_meta($postId, '_fn_premium', isset($_POST['fn_premium']));
    }

    public function addShowFields(): void
    {
        echo '<div class="form-field">'.$this->modeSelect('').'</div>';
        echo '<div class="form-field"><label for="fn_feed_url">'.esc_html__('Import feed URL (RSS.com or any podcast RSS)', 'foldednews').'</label><input type="url" name="fn_feed_url" id="fn_feed_url" value=""></div>';
    }

    public function editShowFields(WP_Term $term): void
    {
        $mode = (string) get_term_meta($term->term_id, '_fn_mode', true);
        printf('<tr class="form-field"><th>%s</th><td>%s</td></tr>', esc_html__('Mode', 'foldednews'), $this->modeSelect($mode));
        $this->row('fn_feed_url', __('Import feed URL', 'foldednews'), (string) get_term_meta($term->term_id, '_fn_feed_url', true));
        $this->row('fn_artwork', __('Artwork URL', 'foldednews'), (string) get_term_meta($term->term_id, '_fn_artwork', true));
        foreach (['apple', 'spotify', 'youtube'] as $svc) {
            $this->row("fn_{$svc}", ucfirst($svc).' URL', (string) get_term_meta($term->term_id, "_fn_{$svc}", true));
        }
    }

    public function saveShow(int $termId): void
    {
        if (! current_user_can('manage_categories')) {
            return;
        }

        $mode = isset($_POST['fn_mode']) ? sanitize_text_field((string) wp_unslash($_POST['fn_mode'])) : 'internal';
        update_term_meta($termId, '_fn_mode', in_array($mode, self::MODES, true) ? $mode : 'internal');
        foreach (['fn_feed_url' => '_fn_feed_url', 'fn_artwork' => '_fn_artwork', 'fn_apple' => '_fn_apple', 'fn_spotify' => '_fn_spotify', 'fn_youtube' => '_fn_youtube'] as $field => $key) {
            if (isset($_POST[$field])) {
                update_term_meta($termId, $key, esc_url_raw((string) wp_unslash($_POST[$field])));
            }
        }
    }

    private function modeSelect(string $current): string
    {
        $html = '<label for="fn_mode">'.esc_html__('Mode', 'foldednews').'</label><select name="fn_mode" id="fn_mode">';
        foreach (self::MODES as $mode) {
            $html .= sprintf('<option value="%1$s" %2$s>%1$s</option>', esc_attr($mode), selected($current, $mode, false));
        }

        return $html.'</select>';
    }

    private function row(string $name, string $label, string $value): void
    {
        printf(
            '<tr class="form-field"><th><label for="%1$s">%2$s</label></th><td><input type="url" name="%1$s" id="%1$s" value="%3$s"></td></tr>',
            esc_attr($name),
            esc_html($label),
            esc_attr($value)
        );
    }
}
