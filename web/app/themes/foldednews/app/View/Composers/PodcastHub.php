<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use WP_Post;
use WP_Query;

/**
 * Podcast hub: featured hero, show grid, category filters, latest episodes.
 */
class PodcastHub extends Composer
{
    /**
     * @var array<int, string>
     */
    protected static $views = ['archive-fn_podcast'];

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        /** @var list<WP_Post> $latest */
        $latest = (new WP_Query([
            'post_type' => 'fn_podcast',
            'post_status' => 'publish',
            'posts_per_page' => 13,
            'no_found_rows' => true,
        ]))->posts;

        return [
            'hero' => array_shift($latest),
            'latest' => $latest,
            'shows' => $this->terms('podcast_show', 24),
            'categories' => $this->terms('podcast_category', 20),
        ];
    }

    /**
     * @return array<int, \WP_Term>
     */
    private function terms(string $taxonomy, int $number): array
    {
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true, 'number' => $number]);

        return is_array($terms) ? $terms : [];
    }
}
