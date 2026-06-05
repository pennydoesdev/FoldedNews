<?php

namespace FoldedNews\Newsroom\Podcast;

use SimpleXMLElement;
use WP_Query;

/**
 * Imports/syncs a podcast show's external RSS feed (RSS.com and any standard
 * podcast feed). Deduplicates by GUID and preserves the external GUID, so
 * repeated syncs never create duplicates. Uses core SimpleXML (no dependency).
 */
final class Importer
{
    private const ITUNES_NS = 'http://www.itunes.com/dtds/podcast-1.0.dtd';
    private const CONTENT_NS = 'http://purl.org/rss/1.0/modules/content/';

    /**
     * @return int number of new episodes imported
     */
    public static function sync(int $termId): int
    {
        $feedUrl = (string) get_term_meta($termId, '_fn_feed_url', true);
        $mode = (string) get_term_meta($termId, '_fn_mode', true);

        if ($feedUrl === '' || ! in_array($mode, ['external_rss', 'rss_com_api'], true)) {
            return 0;
        }

        $response = wp_remote_get($feedUrl, ['timeout' => 30, 'redirection' => 3]);
        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            return 0;
        }

        $xml = @simplexml_load_string((string) wp_remote_retrieve_body($response));
        if (! $xml instanceof SimpleXMLElement || ! isset($xml->channel)) {
            return 0;
        }

        $imported = 0;
        foreach ($xml->channel->item as $item) {
            if (self::importItem($item, $termId)) {
                $imported++;
            }
        }

        return $imported;
    }

    private static function importItem(SimpleXMLElement $item, int $termId): bool
    {
        $guid = trim((string) ($item->guid ?: $item->link));
        if ($guid === '' || self::existsByGuid($guid)) {
            return false;
        }

        $itunes = $item->children(self::ITUNES_NS);
        $content = $item->children(self::CONTENT_NS);

        $encoded = ($content && isset($content->encoded)) ? (string) $content->encoded : '';
        $description = $encoded !== '' ? $encoded : (string) $item->description;
        $audio = isset($item->enclosure['url']) ? (string) $item->enclosure['url'] : '';
        $duration = ($itunes && isset($itunes->duration)) ? trim((string) $itunes->duration) : '';
        $pubDate = strtotime((string) $item->pubDate) ?: time();

        $postId = wp_insert_post([
            'post_type' => 'fn_podcast',
            'post_status' => 'publish',
            'post_title' => sanitize_text_field((string) $item->title),
            'post_content' => wp_kses_post($description),
            'post_date_gmt' => gmdate('Y-m-d H:i:s', $pubDate),
        ], true);

        if (! is_int($postId) || $postId <= 0) {
            return false;
        }

        update_post_meta($postId, '_fn_guid', $guid);
        update_post_meta($postId, '_fn_audio', esc_url_raw($audio));
        if ($duration !== '') {
            update_post_meta($postId, '_fn_duration', sanitize_text_field($duration));
        }
        wp_set_object_terms($postId, [$termId], 'podcast_show');

        return true;
    }

    private static function existsByGuid(string $guid): bool
    {
        return (new WP_Query([
            'post_type' => 'fn_podcast',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => [['key' => '_fn_guid', 'value' => $guid]],
        ]))->have_posts();
    }

    /**
     * Sync every show configured for external import. Cron handler.
     */
    public static function syncAll(): void
    {
        $terms = get_terms(['taxonomy' => 'podcast_show', 'hide_empty' => false]);
        if (! is_array($terms)) {
            return;
        }

        foreach ($terms as $term) {
            self::sync($term->term_id);
        }
    }
}
