<?php

namespace FoldedNews\Newsroom\Modules;

use FoldedNews\Newsroom\Live\Updates;
use FoldedNews\Newsroom\Module;
use WP_Post;

/**
 * Emits schema.org NewsArticle JSON-LD on single article pages, including
 * datePublished / dateModified, headline, author(s), image and publisher.
 */
final class Schema implements Module
{
    public function register(): void
    {
        add_action('wp_head', [$this, 'output']);
    }

    public function output(): void
    {
        if (is_singular('fn_live_blog')) {
            $this->liveBlog();

            return;
        }

        if (!is_singular('fn_article')) {
            return;
        }

        $post = get_post();

        if (!$post instanceof WP_Post) {
            return;
        }

        $published = get_post_time('c', true, $post);
        $modified = get_post_modified_time('c', true, $post);
        $permalink = get_permalink($post);
        $image = get_the_post_thumbnail_url($post, 'full');

        $data = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => wp_strip_all_tags(get_the_title($post)),
            'datePublished' => is_string($published) ? $published : null,
            'dateModified' => is_string($modified) ? $modified : null,
            'mainEntityOfPage' => is_string($permalink) ? $permalink : null,
            'image' => is_string($image) ? [$image] : null,
            'author' => $this->authors($post),
            'description' => $this->description($post),
            'publisher' => [
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
            ],
        ], static fn ($value): bool => $value !== null && $value !== [] && $value !== '');

        $json = wp_json_encode($data, JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return;
        }

        echo "\n" . '<script type="application/ld+json">' . $json . '</script>' . "\n";
    }

    private function liveBlog(): void
    {
        $post = get_post();

        if (! $post instanceof WP_Post) {
            return;
        }

        $published = get_post_time('c', true, $post);
        $modified = get_post_modified_time('c', true, $post);
        $permalink = get_permalink($post);
        $archived = (bool) get_post_meta($post->ID, '_fn_archived', true);

        $updates = [];
        foreach (Updates::forBlog($post->ID, 'ASC') as $update) {
            $time = get_post_time('c', true, $update);
            $updates[] = array_filter([
                '@type' => 'BlogPosting',
                'headline' => wp_strip_all_tags(get_the_title($update)) ?: 'Update',
                'datePublished' => is_string($time) ? $time : null,
                'articleBody' => wp_strip_all_tags((string) $update->post_content),
            ], static fn ($v): bool => $v !== null && $v !== '');
        }

        $data = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'LiveBlogPosting',
            'headline' => wp_strip_all_tags(get_the_title($post)),
            'coverageStartTime' => is_string($published) ? $published : null,
            'coverageEndTime' => ($archived && is_string($modified)) ? $modified : null,
            'datePublished' => is_string($published) ? $published : null,
            'dateModified' => is_string($modified) ? $modified : null,
            'mainEntityOfPage' => is_string($permalink) ? $permalink : null,
            'liveBlogUpdate' => $updates,
        ], static fn ($value): bool => $value !== null && $value !== [] && $value !== '');

        $json = wp_json_encode($data, JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return;
        }

        echo "\n".'<script type="application/ld+json">'.$json.'</script>'."\n";
    }

    /**
     * @return list<array<string, string>>|null
     */
    private function authors(WP_Post $post): ?array
    {
        $authors = [];
        $byline = get_post_meta($post->ID, '_fn_byline', true);

        if (is_array($byline)) {
            foreach ($byline as $id) {
                $name = get_the_title((int) $id);

                if ($name !== '') {
                    $authors[] = ['@type' => 'Person', 'name' => wp_strip_all_tags($name)];
                }
            }
        }

        if ($authors === []) {
            $name = get_the_author_meta('display_name', (int) $post->post_author);

            if ($name !== '') {
                $authors[] = ['@type' => 'Person', 'name' => $name];
            }
        }

        return $authors === [] ? null : $authors;
    }

    private function description(WP_Post $post): string
    {
        $dek = get_post_meta($post->ID, '_fn_dek', true);

        if (is_string($dek) && $dek !== '') {
            return $dek;
        }

        return wp_strip_all_tags((string) get_the_excerpt($post));
    }
}
