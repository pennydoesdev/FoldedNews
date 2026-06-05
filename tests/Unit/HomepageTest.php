<?php

$theme = dirname(__DIR__, 2).'/web/app/themes/foldednews';

it('ships every required Blade component', function () use ($theme) {
    $components = [
        'article-card', 'article-hero', 'section-rail', 'breaking-banner',
        'author-card', 'article-date-line', 'correction-notice', 'source-note',
        'newsletter-signup',
    ];

    foreach ($components as $component) {
        expect("{$theme}/resources/views/components/{$component}.blade.php")->toBeFile();
    }
});

it('assembles the homepage from those components', function () use ($theme) {
    $front = file_get_contents("{$theme}/resources/views/front-page.blade.php");

    expect($front)
        ->toContain('x-breaking-banner')
        ->toContain('x-article-hero')
        ->toContain('x-section-rail')
        ->toContain('x-newsletter-signup');
});

it('deduplicates homepage posts by id', function () use ($theme) {
    expect(file_get_contents("{$theme}/app/Support/Feed.php"))
        ->toContain('post__not_in')
        ->toContain('$this->seen');
});
