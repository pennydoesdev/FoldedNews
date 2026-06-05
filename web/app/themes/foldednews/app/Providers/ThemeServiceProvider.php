<?php

namespace App\Providers;

use Roots\Acorn\Sage\SageServiceProvider;

class ThemeServiceProvider extends SageServiceProvider
{
    /**
     * Register theme services.
     */
    public function register(): void
    {
        parent::register();
    }

    /**
     * Bootstrap theme services.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
