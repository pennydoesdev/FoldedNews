<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Add a "page-{slug}" body class for layout hooks.
 */
add_filter('body_class', function (array $classes) {
    if (is_single() || is_page() && ! is_front_page()) {
        if (is_string($slug = get_post_field('post_name'))) {
            $classes[] = 'page-' . $slug;
        }
    }

    return $classes;
});
