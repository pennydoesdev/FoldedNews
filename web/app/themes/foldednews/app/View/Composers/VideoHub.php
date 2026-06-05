<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use WP_Post;
use WP_Query;

/**
 * Cinematic video hub: hero, live slot, latest grid with a 9:16 vertical strip
 * inserted every N rows (default 3, filterable), plus shows and topics rows.
 */
class VideoHub extends Composer
{
    /**
     * @var array<int, string>
     */
    protected static $views = ['archive-fn_video'];

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $perRow = 4;
        $stripEvery = max(1, (int) apply_filters('foldednews/video/strip_every', 3));

        $latest = $this->videos(['posts_per_page' => 24]);
        $hero = array_shift($latest);

        $verticals = $this->videos([
            'posts_per_page' => 8,
            'meta_query' => [['key' => '_fn_aspect', 'value' => '9:16']],
        ]);

        $rows = [];
        $rowIndex = 0;
        foreach (array_chunk($latest, $perRow) as $chunk) {
            $rows[] = ['type' => 'grid', 'videos' => $chunk];

            if (++$rowIndex % $stripEvery === 0 && $verticals !== []) {
                $rows[] = ['type' => 'strip', 'videos' => $verticals];
            }
        }

        return [
            'hero' => $hero,
            'rows' => $rows,
            'live' => $this->videos(['posts_per_page' => 4, 'meta_query' => [['key' => '_fn_live', 'value' => '1']]]),
            'shows' => $this->terms('video_show'),
            'topics' => $this->terms('topic'),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return list<WP_Post>
     */
    private function videos(array $args): array
    {
        /** @var list<WP_Post> $posts */
        $posts = (new WP_Query(array_merge([
            'post_type' => 'fn_video',
            'post_status' => 'publish',
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
        ], $args)))->posts;

        return $posts;
    }

    /**
     * @return array<int, \WP_Term>
     */
    private function terms(string $taxonomy): array
    {
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true, 'number' => 12]);

        return is_array($terms) ? $terms : [];
    }
}
