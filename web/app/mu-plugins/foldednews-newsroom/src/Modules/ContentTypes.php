<?php

namespace FoldedNews\Newsroom\Modules;

use FoldedNews\Newsroom\Module;
use FoldedNews\Newsroom\Support\Labels;

/**
 * Registers the newsroom custom post types.
 *
 * Topics are modelled as a taxonomy (see Taxonomies) because articles relate
 * to topics; every other content type in the model is a post type here.
 */
final class ContentTypes implements Module
{
    private const VERSION = '0.2.0';

    public function register(): void
    {
        add_action('init', [$this, 'boot']);
        add_action('init', [$this, 'maybeFlush'], 99);
    }

    public function boot(): void
    {
        foreach ($this->definitions() as $slug => $def) {
            register_post_type($slug, $this->args($def));
        }
    }

    /**
     * One-time permalink flush when the CPT set changes.
     */
    public function maybeFlush(): void
    {
        if (get_option('fn_cpt_version') !== self::VERSION) {
            flush_rewrite_rules(false);
            update_option('fn_cpt_version', self::VERSION);
        }
    }

    /**
     * @param array{singular:string,plural:string,icon:string,public:bool,supports:list<string>,archive:string} $def
     * @return array<string, mixed>
     */
    private function args(array $def): array
    {
        return [
            'labels' => Labels::postType($def['singular'], $def['plural']),
            'public' => $def['public'],
            'show_ui' => true,
            'show_in_rest' => true,
            'show_in_menu' => true,
            'menu_icon' => $def['icon'],
            'supports' => $def['supports'],
            'has_archive' => $def['public'] ? $def['archive'] : false,
            'rewrite' => $def['public'] ? ['slug' => $def['archive']] : false,
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ];
    }

    /**
     * @return array<string, array{singular:string,plural:string,icon:string,public:bool,supports:list<string>,archive:string}>
     */
    private function definitions(): array
    {
        $story = ['title', 'editor', 'author', 'thumbnail', 'excerpt', 'revisions', 'custom-fields'];
        $entity = ['title', 'editor', 'thumbnail', 'custom-fields'];
        $note = ['title', 'editor', 'custom-fields'];

        return [
            'fn_article' => ['singular' => 'Article', 'plural' => 'Articles', 'icon' => 'dashicons-media-document', 'public' => true, 'supports' => $story, 'archive' => 'articles'],
            'fn_live_blog' => ['singular' => 'Live Blog', 'plural' => 'Live Blogs', 'icon' => 'dashicons-megaphone', 'public' => true, 'supports' => $story, 'archive' => 'live'],
            'fn_live_update' => ['singular' => 'Live Update', 'plural' => 'Live Updates', 'icon' => 'dashicons-clock', 'public' => false, 'supports' => ['title', 'editor', 'author', 'custom-fields'], 'archive' => 'live-updates'],
            'fn_timeline_event' => ['singular' => 'Timeline Event', 'plural' => 'Timeline Events', 'icon' => 'dashicons-calendar-alt', 'public' => true, 'supports' => $entity, 'archive' => 'timeline'],
            'fn_person' => ['singular' => 'Person', 'plural' => 'People', 'icon' => 'dashicons-admin-users', 'public' => true, 'supports' => $entity, 'archive' => 'people'],
            'fn_organization' => ['singular' => 'Organization', 'plural' => 'Organizations', 'icon' => 'dashicons-building', 'public' => true, 'supports' => $entity, 'archive' => 'organizations'],
            'fn_place' => ['singular' => 'Place', 'plural' => 'Places', 'icon' => 'dashicons-location', 'public' => true, 'supports' => $entity, 'archive' => 'places'],
            'fn_video' => ['singular' => 'Video', 'plural' => 'Videos', 'icon' => 'dashicons-video-alt3', 'public' => true, 'supports' => $story, 'archive' => 'videos'],
            'fn_podcast' => ['singular' => 'Podcast', 'plural' => 'Podcasts', 'icon' => 'dashicons-microphone', 'public' => true, 'supports' => $story, 'archive' => 'podcasts'],
            'fn_newsletter' => ['singular' => 'Newsletter', 'plural' => 'Newsletters', 'icon' => 'dashicons-email', 'public' => true, 'supports' => ['title', 'editor', 'custom-fields'], 'archive' => 'newsletters'],
            'fn_campaign' => ['singular' => 'Campaign', 'plural' => 'Campaigns', 'icon' => 'dashicons-chart-line', 'public' => false, 'supports' => ['title', 'custom-fields'], 'archive' => 'campaigns'],
            'fn_contact' => ['singular' => 'Contact', 'plural' => 'Contacts', 'icon' => 'dashicons-id', 'public' => false, 'supports' => ['title', 'custom-fields'], 'archive' => 'contacts'],
            'fn_correction' => ['singular' => 'Correction', 'plural' => 'Corrections', 'icon' => 'dashicons-edit', 'public' => false, 'supports' => $note, 'archive' => 'corrections'],
            'fn_source_note' => ['singular' => 'Source Note', 'plural' => 'Source Notes', 'icon' => 'dashicons-clipboard', 'public' => false, 'supports' => $note, 'archive' => 'source-notes'],
            'fn_editorial_review' => ['singular' => 'Editorial Review', 'plural' => 'Editorial Reviews', 'icon' => 'dashicons-yes-alt', 'public' => false, 'supports' => $note, 'archive' => 'editorial-reviews'],
        ];
    }
}
