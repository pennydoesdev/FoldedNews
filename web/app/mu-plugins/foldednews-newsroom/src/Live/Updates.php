<?php

namespace FoldedNews\Newsroom\Live;

use WP_Post;
use WP_Query;

/**
 * Queries and serializes approved live-blog updates. Pinned + approval state and
 * the per-update reporter/correction/sources live in post meta.
 */
final class Updates
{
    public const PARENT_META = '_fn_live_blog';
    public const APPROVED_META = '_fn_approved';
    public const PINNED_META = '_fn_pinned';

    /**
     * Approved updates for a live blog. `$since` (unix) limits to newer updates
     * for auto-refresh polling.
     *
     * @return list<WP_Post>
     */
    public static function forBlog(int $blogId, string $order = 'DESC', int $since = 0): array
    {
        if ($blogId <= 0) {
            return [];
        }

        $args = [
            'post_type' => 'fn_live_update',
            'post_status' => 'publish',
            'posts_per_page' => 200,
            'orderby' => 'date',
            'order' => strtoupper($order) === 'ASC' ? 'ASC' : 'DESC',
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
            'meta_query' => [
                'relation' => 'AND',
                ['key' => self::PARENT_META, 'value' => $blogId],
                ['key' => self::APPROVED_META, 'value' => '1'],
            ],
        ];

        if ($since > 0) {
            $args['date_query'] = [[
                'after' => gmdate('Y-m-d H:i:s', $since),
                'inclusive' => false,
                'column' => 'post_date_gmt',
            ]];
        }

        /** @var list<WP_Post> $posts */
        $posts = (new WP_Query($args))->posts;

        return $posts;
    }

    public static function pinned(int $blogId): ?WP_Post
    {
        if ($blogId <= 0) {
            return null;
        }

        $posts = (new WP_Query([
            'post_type' => 'fn_live_update',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'meta_query' => [
                'relation' => 'AND',
                ['key' => self::PARENT_META, 'value' => $blogId],
                ['key' => self::APPROVED_META, 'value' => '1'],
                ['key' => self::PINNED_META, 'value' => '1'],
            ],
        ]))->posts;

        $post = $posts[0] ?? null;

        return $post instanceof WP_Post ? $post : null;
    }

    public static function reporter(WP_Post $update): string
    {
        $personId = (int) get_post_meta($update->ID, '_fn_reporter', true);

        if ($personId > 0 && ($name = get_the_title($personId)) !== '') {
            return $name;
        }

        return (string) get_the_author_meta('display_name', (int) $update->post_author);
    }

    public static function correction(WP_Post $update): string
    {
        $value = get_post_meta($update->ID, '_fn_correction', true);

        return is_string($value) ? $value : '';
    }

    /**
     * @return list<string>
     */
    public static function sources(WP_Post $update): array
    {
        $value = get_post_meta($update->ID, '_fn_sources', true);

        return is_array($value) ? array_values(array_filter(array_map('strval', $value))) : [];
    }

    /**
     * JSON payload for the auto-refresh endpoint.
     *
     * @return array<string, mixed>
     */
    public static function payload(WP_Post $update): array
    {
        return [
            'id' => $update->ID,
            'timestamp' => (int) get_post_time('U', true, $update),
            'time_iso' => (string) get_post_time('c', true, $update),
            'time_human' => (string) get_the_time(get_option('date_format').' · '.get_option('time_format'), $update),
            'title' => get_the_title($update),
            'reporter' => self::reporter($update),
            'correction' => self::correction($update),
            'pinned' => (bool) get_post_meta($update->ID, self::PINNED_META, true),
            'sources' => self::sources($update),
            'content' => (string) apply_filters('the_content', $update->post_content),
        ];
    }
}
