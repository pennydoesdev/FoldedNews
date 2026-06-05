<?php

namespace App;

/**
 * Contextual page/archive title for templates.
 *
 * Presentation helper only — no business logic here.
 */
function title(): string
{
    if (is_home()) {
        if ($home = get_option('page_for_posts', true)) {
            return (string) get_the_title($home);
        }

        return (string) __('Latest', 'foldednews');
    }

    if (is_archive()) {
        return wp_strip_all_tags(get_the_archive_title());
    }

    if (is_search()) {
        /* translators: %s is the search query. */
        return sprintf(__('Search results for &ldquo;%s&rdquo;', 'foldednews'), get_search_query());
    }

    if (is_404()) {
        return (string) __('Not found', 'foldednews');
    }

    return (string) get_the_title();
}
