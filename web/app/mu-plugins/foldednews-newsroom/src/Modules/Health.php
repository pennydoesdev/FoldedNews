<?php

namespace FoldedNews\Newsroom\Modules;

use FoldedNews\Newsroom\Ai\AiRouter;
use FoldedNews\Newsroom\Ai\AiUsageLogger;
use FoldedNews\Newsroom\Ads\Events;
use FoldedNews\Newsroom\Module;

/**
 * System health & QA dashboard plus a /health REST endpoint. Aggregates the
 * platform's logs (S3 offload failures, AI usage, ad events, scheduled jobs).
 */
final class Health implements Module
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('rest_api_init', [$this, 'routes']);
    }

    public function menu(): void
    {
        add_menu_page(__('System Health', 'foldednews'), __('System Health', 'foldednews'), 'manage_options', 'fn-health', [$this, 'render'], 'dashicons-heart', 59);
    }

    /**
     * @return array<string, bool>
     */
    private function tables(): array
    {
        global $wpdb;
        $out = [];
        foreach ([Events::table(), AiUsageLogger::table()] as $table) {
            $out[$table] = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
        }

        return $out;
    }

    /**
     * @return array<string, int>
     */
    private function cron(): array
    {
        $out = [];
        foreach (['fn_podcast_sync'] as $hook) {
            $out[$hook] = (int) wp_next_scheduled($hook);
        }

        return $out;
    }

    public function render(): void
    {
        $failures = get_option('fn_s3_failures', []);
        $failures = is_array($failures) ? $failures : [];
        $providers = array_values(array_filter(array_map(
            static fn ($p): string => $p->available() ? $p->id() : '',
            AiRouter::providers()
        )));

        echo '<div class="wrap"><h1>'.esc_html__('System Health', 'foldednews').'</h1>';

        echo '<h2>'.esc_html__('Environment', 'foldednews').'</h2><table class="widefat striped"><tbody>';
        $this->row('PHP', PHP_VERSION);
        $this->row('WP_ENV', defined('WP_ENV') ? (string) constant('WP_ENV') : 'unknown');
        $this->row('S3 offload', getenv('S3_BUCKET') ? 'configured' : 'not set');
        $this->row('Stripe', getenv('STRIPE_SECRET_KEY') ? 'configured' : 'not set');
        $this->row('AI providers configured', $providers === [] ? 'none' : implode(', ', $providers));
        echo '</tbody></table>';

        echo '<h2>'.esc_html__('Tables', 'foldednews').'</h2><table class="widefat striped"><tbody>';
        foreach ($this->tables() as $table => $exists) {
            $this->row($table, $exists ? 'ok' : 'missing');
        }
        echo '</tbody></table>';

        echo '<h2>'.esc_html__('Scheduled jobs', 'foldednews').'</h2><table class="widefat striped"><tbody>';
        foreach ($this->cron() as $hook => $next) {
            $this->row($hook, $next > 0 ? gmdate('Y-m-d H:i', $next).' UTC' : 'not scheduled');
        }
        echo '</tbody></table>';

        printf('<h2>%s (%d)</h2>', esc_html__('S3 offload failures', 'foldednews'), count($failures));
        if ($failures !== []) {
            echo '<table class="widefat striped"><tbody>';
            foreach (array_slice($failures, -10) as $failure) {
                if (is_array($failure)) {
                    $this->row('attachment '.(int) ($failure['id'] ?? 0), implode(', ', array_map('strval', (array) ($failure['files'] ?? []))));
                }
            }
            echo '</tbody></table>';
        }

        echo '<h2>'.esc_html__('Recent AI calls', 'foldednews').'</h2><table class="widefat striped"><thead><tr><th>When</th><th>Feature</th><th>Provider</th><th>Status</th></tr></thead><tbody>';
        foreach (AiUsageLogger::recent(10) as $log) {
            printf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', esc_html((string) ($log['created_at'] ?? '')), esc_html((string) ($log['feature'] ?? '')), esc_html((string) ($log['provider'] ?? '')), esc_html((string) ($log['status'] ?? '')));
        }
        echo '</tbody></table></div>';
    }

    private function row(string $label, string $value): void
    {
        printf('<tr><th style="width:240px">%s</th><td>%s</td></tr>', esc_html($label), esc_html($value));
    }

    public function routes(): void
    {
        register_rest_route('foldednews/v1', '/health', [
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => [$this, 'health'],
        ]);
    }

    public function health(): \WP_REST_Response
    {
        return new \WP_REST_Response([
            'ok' => true,
            'env' => defined('WP_ENV') ? (string) constant('WP_ENV') : 'unknown',
            'time' => gmdate('c'),
            'tables' => array_map(static fn (bool $b): bool => $b, $this->tables()),
            'ai_providers' => count(array_filter(AiRouter::providers(), static fn ($p): bool => $p->available())),
        ], 200);
    }
}
