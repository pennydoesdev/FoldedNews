<?php

namespace FoldedNews\Newsroom\Modules;

use FoldedNews\Newsroom\Module;
use FoldedNews\Newsroom\Support\Labels;
use WP_Post;

/**
 * Video domain: the Shows taxonomy plus per-video meta (source, aspect ratio,
 * poster, caption tracks, transcript, chapters, live flag). Media files are
 * offloaded to S3/CDN by the Stage 5 media layer.
 */
final class Video implements Module
{
    /** @var list<string> */
    public const ASPECTS = ['16:9', '9:16', '1:1', '4:5', '21:9'];

    public function register(): void
    {
        add_action('init', [$this, 'boot']);
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_action('save_post_fn_video', [$this, 'save'], 10, 1);
    }

    public function boot(): void
    {
        register_taxonomy('video_show', ['fn_video'], [
            'labels' => Labels::taxonomy('Show', 'Shows'),
            'hierarchical' => false,
            'public' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'shows'],
        ]);

        $auth = static fn (): bool => current_user_can('edit_posts');
        $text = ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field', 'auth_callback' => $auth];

        register_post_meta('fn_video', '_fn_video_src', ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'esc_url_raw', 'auth_callback' => $auth]);
        register_post_meta('fn_video', '_fn_aspect', $text);
        register_post_meta('fn_video', '_fn_poster', ['type' => 'integer', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'absint', 'auth_callback' => $auth]);
        register_post_meta('fn_video', '_fn_chapters', ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'esc_url_raw', 'auth_callback' => $auth]);
        register_post_meta('fn_video', '_fn_transcript', ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_textarea_field', 'auth_callback' => $auth]);
        register_post_meta('fn_video', '_fn_live', ['type' => 'boolean', 'single' => true, 'show_in_rest' => true, 'auth_callback' => $auth]);
        register_post_meta('fn_video', '_fn_captions', [
            'type' => 'array',
            'single' => true,
            'show_in_rest' => ['schema' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                'lang' => ['type' => 'string'], 'label' => ['type' => 'string'], 'src' => ['type' => 'string'],
            ]]]],
            'auth_callback' => $auth,
        ]);
    }

    public function addMetaBox(): void
    {
        add_meta_box('fn-video', __('Video', 'foldednews'), [$this, 'render'], 'fn_video', 'normal', 'high');
    }

    public function render(WP_Post $post): void
    {
        wp_nonce_field('fn_video_save', 'fn_video_nonce');
        $aspect = (string) get_post_meta($post->ID, '_fn_aspect', true) ?: '16:9';
        $captions = get_post_meta($post->ID, '_fn_captions', true);
        $lines = '';
        if (is_array($captions)) {
            foreach ($captions as $c) {
                if (is_array($c)) {
                    $lines .= ($c['lang'] ?? '').'|'.($c['label'] ?? '').'|'.($c['src'] ?? '')."\n";
                }
            }
        }

        printf('<p><label>%s<br><input type="url" name="fn_video_src" value="%s" style="width:100%%"></label></p>', esc_html__('Video file URL (S3/CDN or external)', 'foldednews'), esc_attr((string) get_post_meta($post->ID, '_fn_video_src', true)));
        echo '<p><label>'.esc_html__('Aspect ratio', 'foldednews').'<br><select name="fn_aspect">';
        foreach (self::ASPECTS as $option) {
            printf('<option value="%1$s" %2$s>%1$s</option>', esc_attr($option), selected($aspect, $option, false));
        }
        echo '</select></label></p>';
        printf('<p><label>%s<br><input type="number" name="fn_poster" value="%d" style="width:120px"></label></p>', esc_html__('Poster image (attachment ID)', 'foldednews'), (int) get_post_meta($post->ID, '_fn_poster', true));
        printf('<p><label>%s<br><input type="url" name="fn_chapters" value="%s" style="width:100%%"></label></p>', esc_html__('Chapters VTT URL', 'foldednews'), esc_attr((string) get_post_meta($post->ID, '_fn_chapters', true)));
        printf('<p><label>%s<br><textarea name="fn_captions" rows="3" style="width:100%%" placeholder="en|English|https://cdn/track.vtt">%s</textarea></label></p>', esc_html__('Caption tracks (lang|label|url per line)', 'foldednews'), esc_textarea(trim($lines)));
        printf('<p><label>%s<br><textarea name="fn_transcript" rows="5" style="width:100%%">%s</textarea></label></p>', esc_html__('Transcript', 'foldednews'), esc_textarea((string) get_post_meta($post->ID, '_fn_transcript', true)));
        printf('<p><label><input type="checkbox" name="fn_live" %s> %s</label></p>', checked((bool) get_post_meta($post->ID, '_fn_live', true), true, false), esc_html__('Live slot', 'foldednews'));
    }

    public function save(int $postId): void
    {
        $nonce = isset($_POST['fn_video_nonce']) ? sanitize_key((string) $_POST['fn_video_nonce']) : '';
        if ($nonce === '' || wp_verify_nonce($nonce, 'fn_video_save') === false || ! current_user_can('edit_post', $postId)) {
            return;
        }

        $aspect = isset($_POST['fn_aspect']) ? sanitize_text_field((string) wp_unslash($_POST['fn_aspect'])) : '16:9';
        update_post_meta($postId, '_fn_aspect', in_array($aspect, self::ASPECTS, true) ? $aspect : '16:9');
        update_post_meta($postId, '_fn_video_src', isset($_POST['fn_video_src']) ? esc_url_raw((string) wp_unslash($_POST['fn_video_src'])) : '');
        update_post_meta($postId, '_fn_poster', isset($_POST['fn_poster']) ? absint($_POST['fn_poster']) : 0);
        update_post_meta($postId, '_fn_chapters', isset($_POST['fn_chapters']) ? esc_url_raw((string) wp_unslash($_POST['fn_chapters'])) : '');
        update_post_meta($postId, '_fn_transcript', isset($_POST['fn_transcript']) ? sanitize_textarea_field((string) wp_unslash($_POST['fn_transcript'])) : '');
        update_post_meta($postId, '_fn_live', isset($_POST['fn_live']));

        $captions = [];
        $raw = isset($_POST['fn_captions']) ? (string) wp_unslash($_POST['fn_captions']) : '';
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) === 3 && $parts[2] !== '') {
                $captions[] = [
                    'lang' => sanitize_text_field($parts[0]),
                    'label' => sanitize_text_field($parts[1]),
                    'src' => esc_url_raw($parts[2]),
                ];
            }
        }
        update_post_meta($postId, '_fn_captions', $captions);
    }
}
