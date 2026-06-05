<?php

namespace App\View\Composers;

use App\Support\Feed;
use Roots\Acorn\View\Composer;

/**
 * Assembles the homepage in one place: breaking banner, hero, lead package,
 * latest rail, live updates rail and topic/section rails — deduplicated by
 * post ID via Feed. Empty rails are omitted.
 */
class Homepage extends Composer
{
    /**
     * @var array<int, string>
     */
    protected static $views = ['front-page'];

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $order = (isset($_GET['order']) && strtoupper((string) $_GET['order']) === 'ASC') ? 'ASC' : 'DESC';
        $key = 'fn_home_'.$order;

        $cached = get_transient($key);
        if (is_array($cached)) {
            return $cached;
        }

        $data = $this->assemble();
        set_transient($key, $data, 300); // 5 min; flushed on save via Performance module

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function assemble(): array
    {
        $feed = new Feed();

        return [
            'breaking' => $feed->take(4, $this->byFormat('Breaking News')),
            'hero' => $feed->take(1)[0] ?? null,
            'lead' => $feed->take(4),
            'latest' => $feed->take(6),
            'live' => $feed->take(5, ['post_type' => ['fn_live_update', 'fn_live_blog']]),
            'sections' => $this->sections($feed),
        ];
    }

    /**
     * @return array{tax_query: array<int, array<string, mixed>>}
     */
    private function byFormat(string $name): array
    {
        return ['tax_query' => [[
            'taxonomy' => 'article_format',
            'field' => 'name',
            'terms' => $name,
        ]]];
    }

    /**
     * @return list<array{label: string, url: string, posts: list<\WP_Post>}>
     */
    private function sections(Feed $feed): array
    {
        $out = [];

        foreach ($this->config() as $section) {
            if (isset($section['topic'])) {
                $link = get_term_link($section['topic'], 'topic');
                $args = ['tax_query' => [['taxonomy' => 'topic', 'field' => 'slug', 'terms' => $section['topic']]]];
            } else {
                $link = get_post_type_archive_link($section['post_type']);
                $args = ['post_type' => $section['post_type']];
            }

            $posts = $feed->take(4, $args);

            if ($posts !== []) {
                $out[] = [
                    'label' => $section['label'],
                    'url' => is_string($link) ? $link : '',
                    'posts' => $posts,
                ];
            }
        }

        return $out;
    }

    /**
     * Section list is filterable so editors/config can reorder or extend it.
     *
     * @return list<array{label: string, topic?: string, post_type?: string}>
     */
    private function config(): array
    {
        return apply_filters('foldednews/homepage/sections', [
            ['label' => 'Politics', 'topic' => 'politics'],
            ['label' => 'Business', 'topic' => 'business'],
            ['label' => 'World', 'topic' => 'world'],
            ['label' => 'Climate', 'topic' => 'climate'],
            ['label' => 'Justice', 'topic' => 'justice'],
            ['label' => 'Culture', 'topic' => 'culture'],
            ['label' => 'Opinion', 'topic' => 'opinion'],
            ['label' => 'Video', 'post_type' => 'fn_video'],
            ['label' => 'Podcasts', 'post_type' => 'fn_podcast'],
        ]);
    }
}
