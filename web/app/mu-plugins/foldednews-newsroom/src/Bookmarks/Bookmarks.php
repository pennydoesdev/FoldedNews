<?php

namespace FoldedNews\Newsroom\Bookmarks;

/**
 * Reader bookmarks. Logged-in readers store them in user meta (postId =>
 * savedAt); guests use localStorage client-side and merge on login. The
 * array-mutation logic is pure and unit-tested.
 */
final class Bookmarks
{
    public const META = '_fn_bookmarks';

    /**
     * Pure toggle: add (with timestamp) or remove a bookmark.
     *
     * @param  array<int, int>  $data
     * @return array{0: array<int, int>, 1: bool}  [new data, saved?]
     */
    public static function applyToggle(array $data, int $postId, int $now): array
    {
        if (isset($data[$postId])) {
            unset($data[$postId]);

            return [$data, false];
        }

        $data[$postId] = $now;

        return [$data, true];
    }

    /**
     * Pure merge: add new ids without overwriting existing timestamps.
     *
     * @param  array<int, int>  $data
     * @param  list<int>  $ids
     * @return array<int, int>
     */
    public static function applyMerge(array $data, array $ids, int $now): array
    {
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0 && ! isset($data[$id])) {
                $data[$id] = $now;
            }
        }

        return $data;
    }

    public static function toggle(int $userId, int $postId): bool
    {
        if ($userId <= 0 || $postId <= 0) {
            return false;
        }

        [$data, $saved] = self::applyToggle(self::read($userId), $postId, time());
        self::write($userId, $data);

        return $saved;
    }

    public static function isSaved(int $userId, int $postId): bool
    {
        return isset(self::read($userId)[$postId]);
    }

    /**
     * @param  list<int>  $ids
     */
    public static function merge(int $userId, array $ids): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $valid = array_values(array_filter($ids, static fn ($id): bool => (int) $id > 0 && get_post_status((int) $id) !== false));
        $data = self::applyMerge(self::read($userId), $valid, time());
        self::write($userId, $data);

        return count($data);
    }

    /**
     * Saved post IDs, newest-saved first.
     *
     * @return list<int>
     */
    public static function ids(int $userId): array
    {
        $data = self::read($userId);
        arsort($data);

        return array_map('intval', array_keys($data));
    }

    public static function savedAt(int $userId, int $postId): int
    {
        return self::read($userId)[$postId] ?? 0;
    }

    /**
     * @return array<int, int>
     */
    private static function read(int $userId): array
    {
        $data = get_user_meta($userId, self::META, true);
        if (! is_array($data)) {
            return [];
        }

        $out = [];
        foreach ($data as $id => $timestamp) {
            $out[(int) $id] = (int) $timestamp;
        }

        return $out;
    }

    /**
     * @param  array<int, int>  $data
     */
    private static function write(int $userId, array $data): void
    {
        update_user_meta($userId, self::META, $data);
    }
}
