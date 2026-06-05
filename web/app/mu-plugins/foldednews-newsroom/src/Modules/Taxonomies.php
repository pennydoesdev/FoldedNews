<?php

namespace FoldedNews\Newsroom\Modules;

use FoldedNews\Newsroom\Module;
use FoldedNews\Newsroom\Support\Labels;

/**
 * Topics (cross-content classification) and Article Formats (the article
 * templates: Standard News, Breaking News, Investigation, ...).
 */
final class Taxonomies implements Module
{
    /** @var list<string> */
    private const TOPIC_TYPES = ['fn_article', 'fn_video', 'fn_podcast', 'fn_live_blog', 'fn_timeline_event'];

    /** @var list<string> */
    private const FORMATS = [
        'Standard News', 'Breaking News', 'Investigation', 'Analysis', 'Explainer',
        'Feature', 'Live Article', 'Timeline Article', 'Video Article', 'Podcast Article',
    ];

    public function register(): void
    {
        add_action('init', [$this, 'boot']);
        add_action('init', [$this, 'seedFormats'], 50);
    }

    public function boot(): void
    {
        register_taxonomy('topic', self::TOPIC_TYPES, [
            'labels' => Labels::taxonomy('Topic', 'Topics'),
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'topic'],
        ]);

        register_taxonomy('article_format', ['fn_article'], [
            'labels' => Labels::taxonomy('Article Format', 'Article Formats'),
            'hierarchical' => false,
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => false,
        ]);
    }

    /**
     * Seed the fixed set of article formats once.
     */
    public function seedFormats(): void
    {
        if (get_option('fn_formats_seeded')) {
            return;
        }

        foreach (self::FORMATS as $name) {
            if (!term_exists($name, 'article_format')) {
                wp_insert_term($name, 'article_format');
            }
        }

        update_option('fn_formats_seeded', '0.2.0');
    }
}
