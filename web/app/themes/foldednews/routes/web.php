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

/*
| Reader account (Stage 10). Logged-in readers manage their membership via
| Stripe Checkout / Customer Portal and their email preferences.
*/
Route::get('/account/', function () {
    if (! is_user_logged_in()) {
        wp_safe_redirect(wp_login_url(home_url('/account/')));
        exit;
    }

    $userId = get_current_user_id();

    return view('account', [
        'user' => wp_get_current_user(),
        'isMember' => \FoldedNews\Newsroom\Billing\Account::isMember($userId),
        'status' => (string) get_user_meta($userId, \FoldedNews\Newsroom\Billing\Account::STATUS_META, true),
        'hasCustomer' => (string) get_user_meta($userId, \FoldedNews\Newsroom\Billing\Account::CUSTOMER_META, true) !== '',
    ]);
})->name('account');
