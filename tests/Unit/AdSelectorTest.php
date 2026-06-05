<?php

use FoldedNews\Newsroom\Ads\Selector;

require_once dirname(__DIR__, 2).'/web/app/mu-plugins/foldednews-newsroom/src/Ads/Selector.php';

it('picks proportionally to weight (deterministic roll)', function () {
    $candidates = [['id' => 10, 'weight' => 1], ['id' => 20, 'weight' => 3]]; // total 4

    expect(Selector::pick($candidates, 0))->toBe(10);
    expect(Selector::pick($candidates, 1))->toBe(20);
    expect(Selector::pick($candidates, 3))->toBe(20);
});

it('returns null when there are no eligible creatives', function () {
    expect(Selector::pick([]))->toBeNull();
    expect(Selector::pick([['id' => 1, 'weight' => 0]]))->toBeNull();
});
