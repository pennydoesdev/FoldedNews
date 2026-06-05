<?php

namespace FoldedNews\Newsroom\Modules;

use FoldedNews\Newsroom\Module;

/**
 * Performance: invalidate cached homepage queries on content changes, trim
 * unnecessary head output, and add a preconnect to the media CDN.
 */
final class Performance implements Module
{
    public function register(): void
    {
        add_action('save_post', [$this, 'flush']);
        add_action('deleted_post', [$this, 'flush']);
        add_action('init', [$this, 'headCleanup']);
        add_action('wp_head', [$this, 'resourceHints'], 1);
    }

    public function flush(): void
    {
        delete_transient('fn_home_DESC');
        delete_transient('fn_home_ASC');
    }

    public function headCleanup(): void
    {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('wp_head', 'wp_generator');
    }

    public function resourceHints(): void
    {
        $cdn = getenv('CDN_URL');
        if (! is_string($cdn) || $cdn === '') {
            return;
        }

        $host = wp_parse_url($cdn, PHP_URL_HOST);
        if (is_string($host) && $host !== '') {
            printf('<link rel="preconnect" href="%s" crossorigin>'."\n", esc_url('https://'.$host));
        }
    }
}
