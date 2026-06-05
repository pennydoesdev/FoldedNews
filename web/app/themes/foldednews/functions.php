<?php

use App\Providers\ThemeServiceProvider;
use Roots\Acorn\Application;

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
*/

if (! file_exists($composer = __DIR__.'/vendor/autoload.php')) {
    wp_die(__('Error locating autoloader. Please run <code>composer install</code>.', 'foldednews'));
}

require $composer;

/*
|--------------------------------------------------------------------------
| Register The Bootloader
|--------------------------------------------------------------------------
| Boot the Acorn application container when WordPress finishes loading the
| theme. Acorn is the Laravel-style glue for providers, services, Blade, etc.
*/

Application::configure()
    ->withProviders([
        ThemeServiceProvider::class,
    ])
    ->withRouting(web: base_path('routes/web.php'))
    ->boot();

/*
|--------------------------------------------------------------------------
| Register Theme Files
|--------------------------------------------------------------------------
*/

collect(['setup', 'filters'])
    ->each(function ($file) {
        if (! locate_template($file = "app/{$file}.php", true, true)) {
            wp_die(
                /* translators: %s is replaced with the relative file path */
                sprintf(__('Error locating <code>%s</code> for inclusion.', 'foldednews'), $file)
            );
        }
    });
