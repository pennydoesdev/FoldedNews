<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Acorn)
|--------------------------------------------------------------------------
|
| Laravel-style routes for virtual pages and JSON endpoints. Enabled via
| `->withRouting(web: base_path('routes/web.php'))` in functions.php.
|
| Later stages register routes here (reader account, AJAX bookmarks, ad
| serving, Stripe/AI/webhook endpoints). Run `wp acorn route:cache` on deploy.
|
*/

Route::view('/health/theme/', 'partials.health')->name('foldednews.health');
