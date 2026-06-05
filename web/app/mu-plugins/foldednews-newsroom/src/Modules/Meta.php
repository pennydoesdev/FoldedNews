<?php

namespace FoldedNews\Newsroom\Modules;

use FoldedNews\Newsroom\Module;

/**
 * Newsroom meta fields. All REST-exposed so the Stage 4 editor and Sage
 * templates can read them. Author-card data lives on People; articles carry a
 * byline relationship to People plus dek, source notes and corrections.
 */
final class Meta implements Module
{
    public function register(): void
    {
        add_action('init', [$this, 'boot']);
    }

    public function boot(): void
    {
        // Standfirst / dek.
        $this->text('fn_article', '_fn_dek');

        // Relationships (arrays of post IDs).
        $this->ids('fn_article', '_fn_byline');
        $this->ids('fn_article', '_fn_source_notes');
        $this->ids('fn_article', '_fn_corrections');

        // Author card (People).
        $this->text('fn_person', '_fn_role');
        $this->strings('fn_person', '_fn_social');
    }

    private function text(string $type, string $key): void
    {
        register_post_meta($type, $key, [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
        ]);
    }

    /**
     * Array-of-integers meta (post relationships).
     */
    private function ids(string $type, string $key): void
    {
        register_post_meta($type, $key, [
            'type' => 'array',
            'single' => true,
            'show_in_rest' => [
                'schema' => ['type' => 'array', 'items' => ['type' => 'integer']],
            ],
            'sanitize_callback' => static fn ($value): array => array_values(array_filter(array_map('absint', (array) $value))),
            'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
        ]);
    }

    /**
     * Array-of-strings meta (e.g. social URLs).
     */
    private function strings(string $type, string $key): void
    {
        register_post_meta($type, $key, [
            'type' => 'array',
            'single' => true,
            'show_in_rest' => [
                'schema' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
            'sanitize_callback' => static fn ($value): array => array_values(array_filter(array_map('esc_url_raw', array_map('strval', (array) $value)))),
            'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
        ]);
    }
}
