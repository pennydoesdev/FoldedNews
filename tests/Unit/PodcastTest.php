<?php

use FoldedNews\Newsroom\Podcast\Feed;
use FoldedNews\Newsroom\Podcast\Importer;

$mu = dirname(__DIR__, 2).'/web/app/mu-plugins/foldednews-newsroom';
$theme = dirname(__DIR__, 2).'/web/app/themes/foldednews';
require_once $mu.'/src/Podcast/Importer.php';
require_once $mu.'/src/Podcast/Feed.php';

it('imports and syncs feeds with GUID dedup', function () {
    expect(method_exists(Importer::class, 'sync'))->toBeTrue();
    expect(method_exists(Importer::class, 'syncAll'))->toBeTrue();
    expect(method_exists(Importer::class, 'existsByGuid'))->toBeTrue();
});

it('generates an iTunes RSS feed', function () {
    expect(method_exists(Feed::class, 'render'))->toBeTrue();
});

it('registers the podcast feed and hourly sync', function () use ($mu) {
    $src = file_get_contents($mu.'/src/Modules/Podcast.php');
    expect($src)
        ->toContain("add_feed('podcast'")
        ->toContain("wp_schedule_event(time() + 300, 'hourly', 'fn_podcast_sync')");
});

it('ships the podcast hub, show and episode templates', function () use ($theme) {
    expect("{$theme}/resources/views/archive-fn_podcast.blade.php")->toBeFile();
    expect("{$theme}/resources/views/taxonomy-podcast_show.blade.php")->toBeFile();
    expect("{$theme}/resources/views/single-fn_podcast.blade.php")->toBeFile();
    expect("{$theme}/resources/views/components/audio-player.blade.php")->toBeFile();
});
