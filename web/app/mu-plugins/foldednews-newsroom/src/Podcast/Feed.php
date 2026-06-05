<?php

namespace FoldedNews\Newsroom\Podcast;

use WP_Error;
use WP_Post;
use WP_Query;
use WP_Term;

/**
 * Generates an iTunes-compatible podcast RSS feed for a show (internal mode).
 * Registered via add_feed('podcast'); URL: /feed/podcast?podcast_show={slug}.
 * Enclosures use the episode audio URL (CDN-rewritten by the Stage 5 offload).
 */
final class Feed
{
    public static function render(): void
    {
        $slug = get_query_var('podcast_show');
        $term = (is_string($slug) && $slug !== '') ? get_term_by('slug', $slug, 'podcast_show') : null;

        if (! $term instanceof WP_Term) {
            status_header(404);
            return;
        }

        $episodes = (new WP_Query([
            'post_type' => 'fn_podcast',
            'post_status' => 'publish',
            'posts_per_page' => 300,
            'no_found_rows' => true,
            'tax_query' => [['taxonomy' => 'podcast_show', 'field' => 'term_id', 'terms' => $term->term_id]],
        ]))->posts;

        $artwork = (string) get_term_meta($term->term_id, '_fn_artwork', true);

        header('Content-Type: application/rss+xml; charset=UTF-8');

        echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        echo '<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:content="http://purl.org/rss/1.0/modules/content/">'."\n";
        echo '<channel>'."\n";
        echo '<title>'.self::esc($term->name).'</title>'."\n";
        echo '<link>'.self::esc(get_term_link($term) instanceof WP_Error ? home_url('/') : (string) get_term_link($term)).'</link>'."\n";
        echo '<description>'.self::esc($term->description).'</description>'."\n";
        echo '<language>'.self::esc(get_bloginfo('language')).'</language>'."\n";
        if ($artwork !== '') {
            echo '<itunes:image href="'.self::esc($artwork).'"/>'."\n";
        }

        foreach ($episodes as $episode) {
            self::item($episode);
        }

        echo '</channel>'."\n".'</rss>'."\n";
    }

    private static function item(WP_Post $episode): void
    {
        $audio = (string) get_post_meta($episode->ID, '_fn_audio', true);
        $guid = (string) (get_post_meta($episode->ID, '_fn_guid', true) ?: get_permalink($episode));
        $duration = (string) get_post_meta($episode->ID, '_fn_duration', true);

        echo '<item>'."\n";
        echo '<title>'.self::esc(get_the_title($episode)).'</title>'."\n";
        echo '<guid isPermaLink="false">'.self::esc($guid).'</guid>'."\n";
        echo '<link>'.self::esc((string) get_permalink($episode)).'</link>'."\n";
        echo '<pubDate>'.self::esc((string) get_post_time(DATE_RSS, true, $episode)).'</pubDate>'."\n";
        echo '<description><![CDATA['.$episode->post_content.']]></description>'."\n";
        if ($audio !== '') {
            echo '<enclosure url="'.self::esc($audio).'" length="0" type="audio/mpeg"/>'."\n";
        }
        if ($duration !== '') {
            echo '<itunes:duration>'.self::esc($duration).'</itunes:duration>'."\n";
        }
        echo '</item>'."\n";
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }
}
