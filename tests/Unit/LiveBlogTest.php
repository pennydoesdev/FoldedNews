<?php

use FoldedNews\Newsroom\Live\Updates;

$mu = dirname(__DIR__, 2).'/web/app/mu-plugins/foldednews-newsroom';
$theme = dirname(__DIR__, 2).'/web/app/themes/foldednews';
require_once $mu.'/src/Live/Updates.php';

it('exposes the live-update query API', function () {
    expect(method_exists(Updates::class, 'forBlog'))->toBeTrue();
    expect(method_exists(Updates::class, 'pinned'))->toBeTrue();
    expect(method_exists(Updates::class, 'payload'))->toBeTrue();
    expect(Updates::PARENT_META)->toBe('_fn_live_blog');
});

it('registers a public auto-refresh REST route', function () use ($mu) {
    expect(file_get_contents($mu.'/src/Modules/LiveBlog.php'))
        ->toContain("register_rest_route('foldednews/v1', '/live/(?P<id>\\d+)/updates'");
});

it('ships the live-blog and timeline templates', function () use ($theme) {
    expect("{$theme}/resources/views/single-fn_live_blog.blade.php")->toBeFile();
    expect("{$theme}/resources/views/components/live-update.blade.php")->toBeFile();
    expect("{$theme}/resources/views/components/timeline.blade.php")->toBeFile();
    expect("{$theme}/resources/views/archive-fn_timeline_event.blade.php")->toBeFile();
});
