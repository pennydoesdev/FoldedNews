<?php

/**
 * Newsroom presentation accessors. Thin wrappers over post meta/terms so Blade
 * components stay free of data access. No business logic here.
 */

namespace App\Newsroom;

use WP_Post;

function id(WP_Post|int $post): int
{
    return $post instanceof WP_Post ? $post->ID : (int) $post;
}

function dek(WP_Post|int $post): string
{
    $value = get_post_meta(id($post), '_fn_dek', true);

    return is_string($value) ? $value : '';
}

/**
 * @return list<int>
 */
function byline(WP_Post|int $post): array
{
    return ids_meta($post, '_fn_byline');
}

/**
 * @return list<int>
 */
function corrections(WP_Post|int $post): array
{
    return ids_meta($post, '_fn_corrections');
}

/**
 * @return list<int>
 */
function source_notes(WP_Post|int $post): array
{
    return ids_meta($post, '_fn_source_notes');
}

/**
 * @return list<int>
 */
function ids_meta(WP_Post|int $post, string $key): array
{
    $value = get_post_meta(id($post), $key, true);

    return is_array($value)
        ? array_values(array_filter(array_map('intval', $value)))
        : [];
}

function person_role(WP_Post|int $post): string
{
    $value = get_post_meta(id($post), '_fn_role', true);

    return is_string($value) ? $value : '';
}

/**
 * @return list<string>
 */
function person_social(WP_Post|int $post): array
{
    $value = get_post_meta(id($post), '_fn_social', true);

    return is_array($value)
        ? array_values(array_filter(array_map('strval', $value)))
        : [];
}

function primary_topic(WP_Post|int $post): string
{
    $terms = get_the_terms(id($post), 'topic');

    if (! is_array($terms) || $terms === []) {
        return '';
    }

    return (string) $terms[0]->name;
}

function published_iso(WP_Post|int $post): string
{
    $value = get_post_time('c', true, id($post));

    return is_string($value) ? $value : '';
}

function modified_iso(WP_Post|int $post): string
{
    $value = get_post_modified_time('c', true, id($post));

    return is_string($value) ? $value : '';
}

function is_updated(WP_Post|int $post): bool
{
    $published = (int) get_post_time('U', true, id($post));
    $modified = (int) get_post_modified_time('U', true, id($post));

    return ($modified - $published) > 60;
}

/* ---- Video (Stage 8) ------------------------------------------------------ */

function video_aspect(WP_Post|int $post): string
{
    $value = (string) get_post_meta(id($post), '_fn_aspect', true);

    return in_array($value, ['16:9', '9:16', '1:1', '4:5', '21:9'], true) ? $value : '16:9';
}

function aspect_class(string $aspect): string
{
    return 'fn-aspect-'.str_replace(':', 'x', $aspect);
}

function video_src(WP_Post|int $post): string
{
    $value = get_post_meta(id($post), '_fn_video_src', true);

    return is_string($value) ? $value : '';
}

function video_mime(string $src): string
{
    return match (strtolower(pathinfo($src, PATHINFO_EXTENSION))) {
        'webm' => 'video/webm',
        'ogv', 'ogg' => 'video/ogg',
        'mov' => 'video/quicktime',
        'm3u8' => 'application/x-mpegURL',
        default => 'video/mp4',
    };
}

function video_poster(WP_Post|int $post): string
{
    $attachment = (int) get_post_meta(id($post), '_fn_poster', true);

    if ($attachment > 0 && is_string($url = wp_get_attachment_url($attachment))) {
        return $url;
    }

    $thumb = get_the_post_thumbnail_url(id($post), 'large');

    return is_string($thumb) ? $thumb : '';
}

/**
 * @return list<array{lang: string, label: string, src: string}>
 */
function video_captions(WP_Post|int $post): array
{
    $value = get_post_meta(id($post), '_fn_captions', true);

    if (! is_array($value)) {
        return [];
    }

    $tracks = [];
    foreach ($value as $track) {
        if (is_array($track) && ! empty($track['src'])) {
            $tracks[] = [
                'lang' => (string) ($track['lang'] ?? ''),
                'label' => (string) ($track['label'] ?? ''),
                'src' => (string) $track['src'],
            ];
        }
    }

    return $tracks;
}

function video_chapters(WP_Post|int $post): string
{
    $value = get_post_meta(id($post), '_fn_chapters', true);

    return is_string($value) ? $value : '';
}

function video_transcript(WP_Post|int $post): string
{
    $value = get_post_meta(id($post), '_fn_transcript', true);

    return is_string($value) ? $value : '';
}

function video_is_live(WP_Post|int $post): bool
{
    return (bool) get_post_meta(id($post), '_fn_live', true);
}

/* ---- Podcast (Stage 9) ---------------------------------------------------- */

function podcast_audio(WP_Post|int $post): string
{
    $value = get_post_meta(id($post), '_fn_audio', true);

    return is_string($value) ? $value : '';
}

function podcast_duration(WP_Post|int $post): string
{
    $value = get_post_meta(id($post), '_fn_duration', true);

    return is_string($value) ? $value : '';
}

function podcast_is_premium(WP_Post|int $post): bool
{
    return (bool) get_post_meta(id($post), '_fn_premium', true);
}

function show_meta(int $termId, string $key): string
{
    $value = get_term_meta($termId, '_fn_'.$key, true);

    return is_string($value) ? $value : '';
}

/* ---- Access (Stage 10) ---------------------------------------------------- */

function is_member(?int $userId = null): bool
{
    $userId ??= get_current_user_id();

    return class_exists(\FoldedNews\Newsroom\Billing\Account::class)
        && \FoldedNews\Newsroom\Billing\Account::isMember((int) $userId);
}

/**
 * @return list<array{label: string, url: string}>
 */
function show_subscribe_links(int $termId): array
{
    $links = [];
    foreach (['apple' => 'Apple Podcasts', 'spotify' => 'Spotify', 'youtube' => 'YouTube'] as $service => $label) {
        $url = show_meta($termId, $service);
        if ($url !== '') {
            $links[] = ['label' => $label, 'url' => $url];
        }
    }

    return $links;
}
