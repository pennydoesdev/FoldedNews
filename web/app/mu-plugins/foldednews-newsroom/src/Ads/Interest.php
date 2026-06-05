<?php

namespace FoldedNews\Newsroom\Ads;

/**
 * First-party, privacy-respecting interest signal: counts topic views for
 * logged-in readers only (stored in user meta — no anonymous profiling, no
 * fingerprinting, no cross-site identity). Feeds contextual targeting + trending.
 */
final class Interest
{
    private const META = '_fn_interests';

    public static function recordTopics(int $postId): void
    {
        $userId = get_current_user_id();
        if ($userId <= 0) {
            return; // no profiling of anonymous visitors
        }

        $terms = get_the_terms($postId, 'topic');
        if (! is_array($terms)) {
            return;
        }

        $data = self::read($userId);
        foreach ($terms as $term) {
            $data[$term->slug] = (int) ($data[$term->slug] ?? 0) + 1;
        }

        arsort($data);
        update_user_meta($userId, self::META, array_slice($data, 0, 50, true));
    }

    /**
     * @return array<string, int>
     */
    public static function top(int $userId, int $limit = 5): array
    {
        return array_slice(self::read($userId), 0, $limit, true);
    }

    /**
     * @return array<string, int>
     */
    private static function read(int $userId): array
    {
        $data = get_user_meta($userId, self::META, true);

        return is_array($data) ? array_map('intval', $data) : [];
    }
}
