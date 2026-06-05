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

    $bookmarks = [];
    foreach (\FoldedNews\Newsroom\Bookmarks\Bookmarks::ids($userId) as $postId) {
        $post = get_post($postId);
        if (! $post instanceof \WP_Post) {
            continue;
        }
        $bookmarks[] = [
            'id' => $postId,
            'title' => get_the_title($post),
            'url' => (string) get_permalink($post),
            'saved' => \FoldedNews\Newsroom\Bookmarks\Bookmarks::savedAt($userId, $postId),
            'published' => (int) get_post_time('U', false, $post),
        ];
    }

    return view('account', [
        'user' => wp_get_current_user(),
        'isMember' => \FoldedNews\Newsroom\Billing\Account::isMember($userId),
        'status' => (string) get_user_meta($userId, \FoldedNews\Newsroom\Billing\Account::STATUS_META, true),
        'hasCustomer' => (string) get_user_meta($userId, \FoldedNews\Newsroom\Billing\Account::CUSTOMER_META, true) !== '',
        'bookmarks' => $bookmarks,
    ]);
})->name('account');

/*
| Newsletter preferences / one-click unsubscribe (Stage 12). Reached via a
| signed link in every email; the token authorizes changes without login.
*/
Route::get('/newsletter/preferences/', function () {
    $email = isset($_GET['email']) ? strtolower(sanitize_email(wp_unslash($_GET['email']))) : '';
    $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';

    $valid = \FoldedNews\Newsroom\Newsletter\Token::verify($email, $token, wp_salt('nonce'));
    $contactId = $valid ? \FoldedNews\Newsroom\Newsletter\Contacts::findByEmail($email) : 0;

    $lists = get_terms(['taxonomy' => 'mailing_list', 'hide_empty' => false]);
    $subscribed = $contactId > 0 ? wp_get_object_terms($contactId, 'mailing_list', ['fields' => 'slugs']) : [];

    return view('newsletter-preferences', [
        'valid' => $valid && $contactId > 0,
        'email' => $email,
        'token' => $token,
        'lists' => is_array($lists) ? $lists : [],
        'subscribed' => is_array($subscribed) ? $subscribed : [],
    ]);
})->name('newsletter.preferences');
