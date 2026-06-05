<?php

use FoldedNews\Newsroom\Modules\Video;

$mu = dirname(__DIR__, 2).'/web/app/mu-plugins/foldednews-newsroom';
$theme = dirname(__DIR__, 2).'/web/app/themes/foldednews';
require_once $mu.'/src/Module.php';
require_once $mu.'/src/Support/Labels.php';
require_once $mu.'/src/Modules/Video.php';

it('supports the five aspect ratios', function () {
    expect(Video::ASPECTS)->toBe(['16:9', '9:16', '1:1', '4:5', '21:9']);
});

it('ships the video hub, player and card templates', function () use ($theme) {
    expect("{$theme}/resources/views/archive-fn_video.blade.php")->toBeFile();
    expect("{$theme}/resources/views/single-fn_video.blade.php")->toBeFile();
    expect("{$theme}/resources/views/components/video-player.blade.php")->toBeFile();
    expect("{$theme}/resources/views/components/video-card.blade.php")->toBeFile();
    expect("{$theme}/resources/views/components/video-strip.blade.php")->toBeFile();
});

it('inserts the vertical strip on a default cadence', function () use ($theme) {
    expect(file_get_contents("{$theme}/app/View/Composers/VideoHub.php"))
        ->toContain("apply_filters('foldednews/video/strip_every', 3)")
        ->toContain("'type' => 'strip'");
});
