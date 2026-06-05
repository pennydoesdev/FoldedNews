<?php

/**
 * Plugin Name:  FoldedNews Newsroom
 * Description:  Modular newsroom domain (content types, taxonomies, services).
 * Version:      0.2.0
 * Author:       FoldedNews
 * License:      Proprietary
 *
 * Business logic lives in PHP modules under src/, never in Blade.
 */

namespace FoldedNews\Newsroom;

if (!defined('ABSPATH')) {
    exit;
}

define('FOLDEDNEWS_NEWSROOM_VERSION', '0.2.0');
define('FOLDEDNEWS_NEWSROOM_DIR', __DIR__);

/**
 * Minimal PSR-4 autoloader for FoldedNews\Newsroom\ => src/.
 */
spl_autoload_register(function (string $class): void {
    $prefix = __NAMESPACE__ . '\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

/**
 * Default modules. Add/remove via the filter without editing core.
 *
 * @return array<class-string<Module>>
 */
function modules(): array
{
    /** @param array<class-string<Module>> $modules */
    return (array) apply_filters('foldednews/newsroom/modules', [
        Modules\ContentTypes::class,
        Modules\Taxonomies::class,
        Modules\Meta::class,
        Modules\Markdown::class,
        Modules\Media::class,
        Modules\LiveBlog::class,
        Modules\Video::class,
        Modules\Podcast::class,
        Modules\Billing::class,
        Modules\Meter::class,
        Modules\Newsletter::class,
        Modules\Ads::class,
        Modules\Schema::class,
    ]);
}

add_action('plugins_loaded', function (): void {
    foreach (modules() as $module) {
        if (is_subclass_of($module, Module::class)) {
            (new $module())->register();
        }
    }
});
