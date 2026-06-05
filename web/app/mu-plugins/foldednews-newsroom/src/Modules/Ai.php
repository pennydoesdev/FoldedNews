<?php

namespace FoldedNews\Newsroom\Modules;

use FoldedNews\Newsroom\Ai\AiPromptRegistry;
use FoldedNews\Newsroom\Ai\AiRouter;
use FoldedNews\Newsroom\Ai\AiUsageLogger;
use FoldedNews\Newsroom\Module;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

/**
 * AI Copilot: per-feature provider/model routing with fallback, usage logging,
 * an editor meta box (suggestions only — never auto-published), a settings page,
 * and a usage log. API keys live in env and are never shown or stored in options.
 */
final class Ai implements Module
{
    private const VERSION = '1';

    public function register(): void
    {
        add_action('init', [$this, 'install'], 1);
        add_action('rest_api_init', [$this, 'routes']);
        add_action('admin_menu', [$this, 'menus']);
        add_action('admin_post_fn_ai_settings', [$this, 'saveSettings']);
        add_action('add_meta_boxes', [$this, 'metaBox']);
    }

    public function install(): void
    {
        if (get_option('fn_ai_v') === self::VERSION) {
            return;
        }
        AiUsageLogger::install();
        update_option('fn_ai_v', self::VERSION);
    }

    public function routes(): void
    {
        register_rest_route('foldednews/v1', '/ai/run', [
            'methods' => 'POST',
            'permission_callback' => static fn (): bool => current_user_can('edit_posts'),
            'callback' => [$this, 'run'],
        ]);
    }

    public function run(WP_REST_Request $request): WP_REST_Response
    {
        $feature = sanitize_key((string) $request->get_param('feature'));
        if (! in_array($feature, AiPromptRegistry::features(), true)) {
            return new WP_REST_Response(['error' => 'unknown_feature'], 400);
        }

        $input = (string) $request->get_param('input');
        $postId = (int) $request->get_param('post_id');
        if ($input === '' && $postId > 0 && ($post = get_post($postId)) instanceof WP_Post) {
            $input = wp_strip_all_tags(get_the_title($post)."\n\n".$post->post_content);
        }

        if (trim($input) === '') {
            return new WP_REST_Response(['error' => 'no_input'], 400);
        }

        return new WP_REST_Response(AiRouter::run($feature, mb_substr($input, 0, 12000)), 200);
    }

    public function menus(): void
    {
        add_menu_page(__('AI Copilot', 'foldednews'), __('AI Copilot', 'foldednews'), 'manage_options', 'fn-ai', [$this, 'renderSettings'], 'dashicons-superhero', 58);
        add_submenu_page('fn-ai', __('Usage log', 'foldednews'), __('Usage log', 'foldednews'), 'manage_options', 'fn-ai-log', [$this, 'renderLog']);
    }

    public function renderSettings(): void
    {
        $providers = AiRouter::providers();
        $config = get_option('fn_ai_features', []);
        $config = is_array($config) ? $config : [];
        $default = (string) get_option('fn_ai_default_provider', 'openai');
        $fallback = (string) get_option('fn_ai_fallback_provider', '');

        echo '<div class="wrap"><h1>'.esc_html__('AI Copilot', 'foldednews').'</h1>';
        echo '<p>'.esc_html__('Provider API keys are read from environment variables and never stored here. Status:', 'foldednews').' ';
        foreach ($providers as $provider) {
            printf('<span style="margin-right:10px">%s: <strong style="color:%s">%s</strong></span>', esc_html($provider->label()), $provider->available() ? 'green' : '#999', $provider->available() ? esc_html__('configured', 'foldednews') : esc_html__('not set', 'foldednews'));
        }
        echo '</p>';

        printf('<form method="post" action="%s">', esc_url(admin_url('admin-post.php')));
        wp_nonce_field('fn_ai_settings');
        echo '<input type="hidden" name="action" value="fn_ai_settings">';

        echo '<table class="form-table"><tr><th>'.esc_html__('Default provider', 'foldednews').'</th><td>'.$this->providerSelect('default_provider', $providers, $default).'</td></tr>';
        echo '<tr><th>'.esc_html__('Fallback provider', 'foldednews').'</th><td>'.$this->providerSelect('fallback_provider', $providers, $fallback, true).'</td></tr></table>';

        echo '<h2>'.esc_html__('Per-feature routing', 'foldednews').'</h2>';
        echo '<table class="widefat striped"><thead><tr><th>'.esc_html__('Feature', 'foldednews').'</th><th>'.esc_html__('Provider', 'foldednews').'</th><th>'.esc_html__('Model (blank = provider default)', 'foldednews').'</th></tr></thead><tbody>';
        foreach (AiPromptRegistry::features() as $feature) {
            $fp = is_array($config[$feature] ?? null) ? $config[$feature] : [];
            printf(
                '<tr><td><code>%s</code></td><td>%s</td><td><input type="text" name="feature[%s][model]" value="%s"></td></tr>',
                esc_html($feature),
                $this->providerSelect("feature[{$feature}][provider]", $providers, (string) ($fp['provider'] ?? ''), true),
                esc_attr($feature),
                esc_attr((string) ($fp['model'] ?? ''))
            );
        }
        echo '</tbody></table>';
        submit_button();
        echo '</form></div>';
    }

    /**
     * @param  array<string, \FoldedNews\Newsroom\Ai\AiProviderInterface>  $providers
     */
    private function providerSelect(string $name, array $providers, string $current, bool $allowDefault = false): string
    {
        $html = '<select name="'.esc_attr($name).'">';
        if ($allowDefault) {
            $html .= '<option value="">'.esc_html__('— use default —', 'foldednews').'</option>';
        }
        foreach ($providers as $provider) {
            $html .= sprintf('<option value="%s" %s>%s</option>', esc_attr($provider->id()), selected($current, $provider->id(), false), esc_html($provider->label()));
        }

        return $html.'</select>';
    }

    public function saveSettings(): void
    {
        if (! current_user_can('manage_options') || ! check_admin_referer('fn_ai_settings')) {
            wp_die(esc_html__('Forbidden', 'foldednews'));
        }

        update_option('fn_ai_default_provider', isset($_POST['default_provider']) ? sanitize_key((string) wp_unslash($_POST['default_provider'])) : 'openai');
        update_option('fn_ai_fallback_provider', isset($_POST['fallback_provider']) ? sanitize_key((string) wp_unslash($_POST['fallback_provider'])) : '');

        $features = [];
        $submitted = isset($_POST['feature']) && is_array($_POST['feature']) ? wp_unslash($_POST['feature']) : [];
        foreach ($submitted as $feature => $values) {
            if (! is_array($values)) {
                continue;
            }
            $features[sanitize_key((string) $feature)] = [
                'provider' => sanitize_key((string) ($values['provider'] ?? '')),
                'model' => sanitize_text_field((string) ($values['model'] ?? '')),
            ];
        }
        update_option('fn_ai_features', $features);

        wp_safe_redirect(add_query_arg('updated', '1', admin_url('admin.php?page=fn-ai')));
        exit;
    }

    public function renderLog(): void
    {
        echo '<div class="wrap"><h1>'.esc_html__('AI usage log', 'foldednews').'</h1>';
        echo '<table class="widefat striped"><thead><tr><th>'.esc_html__('When', 'foldednews').'</th><th>'.esc_html__('Feature', 'foldednews').'</th><th>'.esc_html__('Provider', 'foldednews').'</th><th>'.esc_html__('Model', 'foldednews').'</th><th>'.esc_html__('Tokens', 'foldednews').'</th><th>'.esc_html__('Cost', 'foldednews').'</th><th>'.esc_html__('Status', 'foldednews').'</th></tr></thead><tbody>';
        foreach (AiUsageLogger::recent() as $row) {
            printf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%d/%d</td><td>$%s</td><td>%s</td></tr>',
                esc_html((string) ($row['created_at'] ?? '')),
                esc_html((string) ($row['feature'] ?? '')),
                esc_html((string) ($row['provider'] ?? '')),
                esc_html((string) ($row['model'] ?? '')),
                (int) ($row['prompt_tokens'] ?? 0),
                (int) ($row['completion_tokens'] ?? 0),
                esc_html((string) ($row['cost'] ?? '0')),
                esc_html((string) ($row['status'] ?? ''))
            );
        }
        echo '</tbody></table></div>';
    }

    public function metaBox(): void
    {
        add_meta_box('fn-ai-copilot', __('AI Copilot', 'foldednews'), [$this, 'renderMetaBox'], ['fn_article', 'fn_video', 'fn_podcast'], 'side', 'default');
    }

    public function renderMetaBox(WP_Post $post): void
    {
        $features = ['summary', 'headline', 'seo', 'tagging', 'editorial_notes', 'alt_text'];
        $rest = esc_url(rest_url('foldednews/v1/ai/run'));
        $nonce = wp_create_nonce('wp_rest');

        echo '<p class="description">'.esc_html__('Suggestions only — review before publishing. AI never publishes automatically.', 'foldednews').'</p>';
        echo '<div id="fn-ai-buttons" style="display:flex;flex-wrap:wrap;gap:4px">';
        foreach ($features as $feature) {
            printf('<button type="button" class="button" data-feature="%s">%s</button>', esc_attr($feature), esc_html($feature));
        }
        echo '</div><textarea id="fn-ai-out" rows="8" style="width:100%;margin-top:8px" readonly placeholder="'.esc_attr__('Output appears here…', 'foldednews').'"></textarea>';

        printf(
            '<script>(function(){var p=%d,rest=%s,nonce=%s;document.querySelectorAll("#fn-ai-buttons button").forEach(function(b){b.addEventListener("click",function(){var o=document.getElementById("fn-ai-out");o.value="…";fetch(rest,{method:"POST",headers:{"Content-Type":"application/json","X-WP-Nonce":nonce},body:JSON.stringify({feature:b.dataset.feature,post_id:p})}).then(function(r){return r.json()}).then(function(d){o.value=d.ok?(d.text+(d.requires_approval?"\n\n[Requires editor approval before use]":"")):("Error: "+(d.error||"failed"))}).catch(function(){o.value="Network error"})})})})();</script>',
            (int) $post->ID,
            wp_json_encode($rest),
            wp_json_encode($nonce)
        );
    }
}
