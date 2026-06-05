<?php

use FoldedNews\Newsroom\Meter\Decision;

require_once dirname(__DIR__, 2).'/web/app/mu-plugins/foldednews-newsroom/src/Meter/Decision.php';

const FN_MONTH = '202606';
const FN_LIMIT = 5;
const FN_NOW = 1_000_000;

it('allows the first 5 articles and blocks the 6th', function () {
    $state = [];
    for ($i = 1; $i <= 5; $i++) {
        $result = Decision::evaluate($state, $i, FN_MONTH, FN_LIMIT, FN_NOW);
        $state = $result['state'];
        expect($result['allowed'])->toBeTrue();
    }

    expect(Decision::evaluate($state, 6, FN_MONTH, FN_LIMIT, FN_NOW)['allowed'])->toBeFalse();
});

it('does not re-count a re-read article', function () {
    $state = ['month' => FN_MONTH, 'ids' => [1, 2, 3, 4, 5], 'unlocks' => []];

    $result = Decision::evaluate($state, 3, FN_MONTH, FN_LIMIT, FN_NOW);
    expect($result['allowed'])->toBeTrue();
    expect($result['count'])->toBe(5);
});

it('grants timed unlocks that expire', function () {
    $state = ['month' => FN_MONTH, 'ids' => [1, 2, 3, 4, 5], 'unlocks' => []];
    $state = Decision::unlock($state, 6, FN_MONTH, FN_NOW + 100);

    expect(Decision::evaluate($state, 6, FN_MONTH, FN_LIMIT, FN_NOW)['allowed'])->toBeTrue();
    expect(Decision::evaluate($state, 6, FN_MONTH, FN_LIMIT, FN_NOW + 200)['allowed'])->toBeFalse();
});

it('resets on a new calendar month', function () {
    $state = ['month' => FN_MONTH, 'ids' => [1, 2, 3, 4, 5], 'unlocks' => []];

    expect(Decision::evaluate($state, 6, '202607', FN_LIMIT, FN_NOW)['allowed'])->toBeTrue();
});
