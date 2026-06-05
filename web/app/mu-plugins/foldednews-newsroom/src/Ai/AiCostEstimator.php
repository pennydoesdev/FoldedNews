<?php

namespace FoldedNews\Newsroom\Ai;

/**
 * Estimates request cost from token usage and a per-model price table
 * (USD per 1M tokens). The arithmetic core is pure and unit-tested.
 */
final class AiCostEstimator
{
    /**
     * Pure cost calculation.
     */
    public static function cost(float $inRatePerM, float $outRatePerM, int $promptTokens, int $completionTokens): float
    {
        $total = ($promptTokens / 1_000_000) * $inRatePerM + ($completionTokens / 1_000_000) * $outRatePerM;

        return round($total, 6);
    }

    public static function estimate(string $model, int $promptTokens, int $completionTokens): float
    {
        $rate = self::pricing()[$model] ?? ['in' => 0.0, 'out' => 0.0];

        return self::cost((float) $rate['in'], (float) $rate['out'], $promptTokens, $completionTokens);
    }

    /**
     * USD per 1M tokens. Filterable; defaults are indicative — confirm current
     * pricing with each provider.
     *
     * @return array<string, array{in: float, out: float}>
     */
    public static function pricing(): array
    {
        return apply_filters('foldednews/ai/pricing', [
            'gpt-4o-mini' => ['in' => 0.15, 'out' => 0.60],
            'gpt-4o' => ['in' => 2.50, 'out' => 10.00],
            'claude-3-5-haiku-latest' => ['in' => 0.80, 'out' => 4.00],
            'gemini-1.5-flash' => ['in' => 0.075, 'out' => 0.30],
        ]);
    }
}
