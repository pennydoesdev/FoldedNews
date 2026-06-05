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
