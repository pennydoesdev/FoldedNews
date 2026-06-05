<?php

namespace App\Support;

use WP_Post;
use WP_Query;

/**
 * Pulls posts for the homepage while guaranteeing no post ID is rendered twice.
 * Each take() excludes everything already returned this request.
 */
final class Feed
{
    /** @var array<int, true> */
    private array $seen = [];

    /**
     * @param  array<string, mixed>  $args
     * @return list<WP_Post>
     */
    public function take(int $count, array $args = []): array
    {
        if ($count < 1) {
            return [];
        }

        $query = new WP_Query(array_merge([
            'post_type' => 'fn_article',
            'post_status' => 'publish',
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
        ], $args, [
            'posts_per_page' => $count,
            'post__not_in' => array_keys($this->seen),
            'fields' => 'all',
        ]));

        /** @var list<WP_Post> $posts */
        $posts = $query->posts;

        foreach ($posts as $post) {
            $this->seen[$post->ID] = true;
        }

        return $posts;
    }

    /**
     * @return list<int>
     */
    public function used(): array
    {
        return array_keys($this->seen);
    }
}
