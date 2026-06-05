<?php

/**
 * Plugin Name:  FoldedNews Newsroom
 * Description:  Modular newsroom domain (content types, taxonomies, services). Bootstraps via Acorn.
 * Version:      0.1.0
 * Author:       FoldedNews
 * License:      Proprietary
 *
 * Stage 1: bootstrap only. Content types/taxonomies land in Stage 2.
 * Business logic lives in PHP service classes (src/), never in Blade.
 */

namespace FoldedNews\Newsroom;

if (!defined('ABSPATH')) {
    exit;
}

define('FOLDEDNEWS_NEWSROOM_VERSION', '0.1.0');
define('FOLDEDNEWS_NEWSROOM_DIR', __DIR__);

/**
 * Modules register themselves here as the platform grows (Stage 2+).
 * Each module is a small, self-contained class with a register() method.
 *
 * @return array<class-string>
 */
function modules(): array
{
    /**
     * Allow modules to be added/removed without editing core.
     *
     * @param array<class-string> $modules
     */
    return (array) apply_filters('foldednews/newsroom/modules', []);
}

add_action('plugins_loaded', function (): void {
    foreach (modules() as $module) {
        if (class_exists($module) && method_exists($module, 'register')) {
            (new $module())->register();
        }
    }
});
