<?php

use FoldedNews\Newsroom\Bookmarks\Bookmarks;

require_once dirname(__DIR__, 2).'/web/app/mu-plugins/foldednews-newsroom/src/Bookmarks/Bookmarks.php';

it('toggles a bookmark on and off', function () {
    [$data, $saved] = Bookmarks::applyToggle([], 5, 100);
    expect($saved)->toBeTrue();
    expect($data)->toHaveKey(5);

    [$data, $saved] = Bookmarks::applyToggle($data, 5, 200);
    expect($saved)->toBeFalse();
    expect($data)->not->toHaveKey(5);
});

it('merges guest bookmarks without overwriting existing timestamps', function () {
    $merged = Bookmarks::applyMerge([5 => 100], [5, 6, 7], 300);

    expect(array_keys($merged))->toBe([5, 6, 7]);
    expect($merged[5])->toBe(100);   // existing kept
    expect($merged[6])->toBe(300);   // new
});
