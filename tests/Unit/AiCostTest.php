<?php

use FoldedNews\Newsroom\Ai\AiCostEstimator;

require_once dirname(__DIR__, 2).'/web/app/mu-plugins/foldednews-newsroom/src/Ai/AiCostEstimator.php';

it('computes cost from per-million token rates', function () {
    expect(AiCostEstimator::cost(0.15, 0.60, 1_000_000, 1_000_000))->toBe(0.75);
    expect(AiCostEstimator::cost(0.15, 0.60, 1000, 1000))->toBe(0.00075);
    expect(AiCostEstimator::cost(0.0, 0.0, 5000, 5000))->toBe(0.0);
});
