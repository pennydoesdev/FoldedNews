<?php

namespace FoldedNews\Newsroom\Ads;

/**
 * Weighted creative selection. Pure (the random "roll" can be injected) so it is
 * unit-testable and deterministic in tests.
 */
final class Selector
{
    /**
     * @param  list<array{id: int, weight: int}>  $candidates
     */
    public static function pick(array $candidates, ?int $roll = null): ?int
    {
        $candidates = array_values(array_filter($candidates, static fn ($c): bool => (int) ($c['weight'] ?? 0) > 0));
        if ($candidates === []) {
            return null;
        }

        $total = array_sum(array_map(static fn ($c): int => (int) $c['weight'], $candidates));
        $roll = $roll ?? random_int(0, $total - 1);
        $roll = max(0, min($total - 1, $roll));

        $cursor = 0;
        foreach ($candidates as $candidate) {
            $cursor += (int) $candidate['weight'];
            if ($roll < $cursor) {
                return (int) $candidate['id'];
            }
        }

        return (int) $candidates[array_key_last($candidates)]['id'];
    }
}
