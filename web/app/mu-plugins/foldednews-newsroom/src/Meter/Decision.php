<?php

namespace FoldedNews\Newsroom\Meter;

/**
 * Pure metering logic (no WordPress) so it is fully unit-testable. Tracks the
 * distinct articles a reader has opened this calendar month and per-article
 * unlocks. The WordPress wrapper (Meter) handles storage + member/free bypass.
 *
 * State shape: array{month: string, ids: list<int>, unlocks: array<int,int>}
 */
final class Decision
{
    /**
     * Evaluate (and record) a view. Returns the decision and the new state.
     *
     * @param  array<string, mixed>  $state
     * @return array{allowed: bool, reason: string, count: int, state: array<string, mixed>}
     */
    public static function evaluate(array $state, int $postId, string $month, int $limit, int $now): array
    {
        $state = self::forMonth($state, $month);
        $unlocks = self::unlocks($state);

        if (isset($unlocks[$postId]) && $unlocks[$postId] > $now) {
            return self::result(true, 'unlocked', $state);
        }

        $ids = self::ids($state);

        if (in_array($postId, $ids, true)) {
            return self::result(true, 'counted', $state);
        }

        if (count($ids) < $limit) {
            $ids[] = $postId;
            $state['ids'] = $ids;

            return self::result(true, 'within', $state);
        }

        return self::result(false, 'metered', $state);
    }

    /**
     * Grant a timed unlock for one article.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public static function unlock(array $state, int $postId, string $month, int $expiresAt): array
    {
        $state = self::forMonth($state, $month);
        $unlocks = self::unlocks($state);
        $unlocks[$postId] = $expiresAt;
        $state['unlocks'] = $unlocks;

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public static function count(array $state, string $month): int
    {
        return ($state['month'] ?? '') === $month ? count(self::ids($state)) : 0;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private static function forMonth(array $state, string $month): array
    {
        return ($state['month'] ?? '') === $month
            ? $state
            : ['month' => $month, 'ids' => [], 'unlocks' => []];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return list<int>
     */
    private static function ids(array $state): array
    {
        return is_array($state['ids'] ?? null)
            ? array_values(array_map('intval', $state['ids']))
            : [];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<int, int>
     */
    private static function unlocks(array $state): array
    {
        if (! is_array($state['unlocks'] ?? null)) {
            return [];
        }

        $out = [];
        foreach ($state['unlocks'] as $id => $expiry) {
            $out[(int) $id] = (int) $expiry;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{allowed: bool, reason: string, count: int, state: array<string, mixed>}
     */
    private static function result(bool $allowed, string $reason, array $state): array
    {
        return [
            'allowed' => $allowed,
            'reason' => $reason,
            'count' => count(self::ids($state)),
            'state' => $state,
        ];
    }
}
