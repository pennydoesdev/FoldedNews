<?php

namespace FoldedNews\Newsroom\Modules;

use FoldedNews\Newsroom\Module;

/**
 * WordPress Desktop Mode compatibility. Adds a responsive "Newsroom Desk" dock
 * (works in normal wp-admin and Desktop Mode — no fixed full-screen layouts),
 * a body flag when Desktop Mode is active, and a wp.desktop.fetch() fallback
 * helper (window.fnFetch) for admin scripts. Does not replace wp-admin.
 */
final class DesktopMode implements Module
{
    /**
     * Dock apps: label => admin target (relative to admin URL).
     *
     * @return array<string, string>
     */
    private function apps(): array
    {
        return [
            'Markdown Editor' => 'post-new.php?post_type=fn_article',
            'Live Blog Desk' => 'edit.php?post_type=fn_live_blog',
            'Timeline Builder' => 'edit.php?post_type=fn_timeline_event',
            'Newsletter Studio' => 'edit.php?post_type=fn_contact&page=fn-newsletter',
            'Contacts / Lists' => 'edit.php?post_type=fn_contact',
            'Campaigns' => 'edit.php?post_type=fn_campaign',
            'Advertiser Portal' => 'edit.php?post_type=fn_creative',
            'Ad Metrics' => 'edit.php?post_type=fn_campaign&page=fn-ad-metrics',
            'AI Copilot' => 'admin.php?page=fn-ai',
            'People / Entities' => 'edit.php?post_type=fn_person',
            'Corrections Ledger' => 'edit.php?post_type=fn_correction',
            'Source Notes' => 'edit.php?post_type=fn_source_note',
            'Editorial Review' => 'edit.php?post_type=fn_editorial_review',
            'Media Offload' => 'admin.php?page=fn-health',
            'System Health' => 'admin.php?page=fn-health',
        ];
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_body_class', [$this, 'bodyClass']);
        add_action('admin_print_footer_scripts', [$this, 'helper']);
    }

    public function menu(): void
    {
        add_menu_page(__('Newsroom Desk', 'foldednews'), __('Newsroom Desk', 'foldednews'), 'edit_posts', 'fn-desk', [$this, 'render'], 'dashicons-screenoptions', 2);
    }

    public function render(): void
    {
        echo '<div class="wrap"><h1>'.esc_html__('Newsroom Desk', 'foldednews').'</h1>';
        echo '<p class="description">'.esc_html__('Your newsroom apps. Works in normal admin and WordPress Desktop Mode.', 'foldednews').'</p>';
        echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-top:16px">';

        foreach ($this->apps() as $label => $target) {
            printf(
                '<a class="fn-desk-app" href="%s" style="display:block;padding:18px;border:1px solid #dcdcde;border-radius:8px;background:#fff;text-decoration:none;font-weight:600">%s</a>',
                esc_url(admin_url($target)),
                esc_html($label)
            );
        }

        echo '</div></div>';
    }

    public function bodyClass(string $classes): string
    {
        // The Desktop app exposes window.wp.desktop; add a hook for styling.
        return $classes.' fn-desk-ready';
    }

    public function helper(): void
    {
        // Prefer the Desktop bridge when present, otherwise standard fetch.
        echo '<script>window.fnFetch=function(u,o){return (window.wp&&window.wp.desktop&&window.wp.desktop.fetch)?window.wp.desktop.fetch(u,o):fetch(u,o)};'
            .'if(window.wp&&window.wp.desktop){document.body.classList.add("is-desktop-mode")}</script>';
    }
}
