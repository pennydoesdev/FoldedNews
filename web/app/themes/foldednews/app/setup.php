<?php

/**
 * Theme setup.
 */

namespace App;

use Illuminate\Support\Facades\Vite;

/**
 * Use the Tailwind-merged theme.json built by @roots/vite-plugin.
 */
add_filter('theme_file_path', function ($path, $file) {
    return $file === 'theme.json'
        ? public_path('build/assets/theme.json')
        : $path;
}, 10, 2);

/**
 * Disable on-demand block asset loading.
 *
 * @link https://core.trac.wordpress.org/ticket/61965
 */
add_filter('should_load_separate_core_block_assets', '__return_false');

/**
 * Inject editor styles into the block editor.
 */
add_filter('block_editor_settings_all', function ($settings) {
    $settings['styles'][] = [
        'css' => "@import url('" . Vite::asset('resources/css/editor.css') . "')",
    ];

    return $settings;
});

/**
 * Load the Milkdown newsroom editor on the post edit screens that author Markdown.
 */
add_action('admin_footer', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;

    if (! $screen || $screen->base !== 'post') {
        return;
    }

    $types = ['fn_article', 'fn_live_blog', 'fn_live_update', 'fn_timeline_event', 'fn_video', 'fn_podcast', 'fn_newsletter'];

    if (in_array($screen->post_type, $types, true)) {
        echo Vite::withEntryPoints(['resources/js/milkdown.js'])->toHtml();
    }
});

/**
 * Register theme support.
 *
 * @link https://developer.wordpress.org/themes/functionality/
 */
add_action('after_setup_theme', function () {
    register_nav_menus([
        'primary_navigation' => __('Primary Navigation', 'foldednews'),
        'footer_navigation' => __('Footer Navigation', 'foldednews'),
    ]);

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('customize-selective-refresh-widgets');
    add_theme_support('html5', [
        'caption',
        'comment-form',
        'comment-list',
        'gallery',
        'search-form',
        'script',
        'style',
    ]);
}, 20);

/**
 * Register sidebars.
 */
add_action('widgets_init', function () {
    $config = [
        'before_widget' => '<section class="widget %1$s %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h3>',
        'after_title' => '</h3>',
    ];

    register_sidebar([
        'name' => __('Primary', 'foldednews'),
        'id' => 'sidebar-primary',
    ] + $config);

    register_sidebar([
        'name' => __('Footer', 'foldednews'),
        'id' => 'sidebar-footer',
    ] + $config);
});
