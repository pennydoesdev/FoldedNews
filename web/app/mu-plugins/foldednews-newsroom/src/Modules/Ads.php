<?php

namespace FoldedNews\Newsroom\Modules;

use FoldedNews\Newsroom\Ads\AdServer;
use FoldedNews\Newsroom\Ads\Events;
use FoldedNews\Newsroom\Ads\Interest;
use FoldedNews\Newsroom\Module;
use FoldedNews\Newsroom\Support\Labels;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Publisher-owned ad platform: Advertiser role, Creative CPT with admin
 * approval, campaign pricing/status, a weighted ad server, impression/click
 * tracking, a metrics dashboard, first-party interest signals, and
 * pause-on-billing-failure via the shared Stripe webhook.
 */
final class Ads implements Module
{
    private const VERSION = '1';

    /** @var list<string> */
    public const PLACEMENTS = [
        'leaderboard', 'billboard', 'mobile_banner', 'mpu', 'large_rectangle',
        'sticky_footer', 'sidebar', 'in_article', 'between_paragraphs',
        'homepage_takeover', 'section_sponsorship', 'newsletter', 'podcast',
        'video_preroll', 'video_midroll', 'liveblog_sponsorship', 'topic_sponsorship',
    ];

    public function register(): void
    {
        add_action('init', [$this, 'install'], 1);
        add_action('init', [$this, 'boot']);
        add_action('add_meta_boxes', [$this, 'metaBoxes']);
        add_action('save_post_fn_creative', [$this, 'saveCreative']);
        add_action('save_post_fn_campaign', [$this, 'saveCampaign']);
        add_action('rest_api_init', [$this, 'routes']);
        add_action('template_redirect', [$this, 'recordInterest'], 6);
        add_action('foldednews/stripe/event', [$this, 'onStripeEvent'], 10, 2);
        add_action('admin_menu', [$this, 'metricsMenu']);
    }

    public function install(): void
    {
        if (get_option('fn_ads_v') === self::VERSION) {
            return;
        }

        Events::install();
        self::roles();
        update_option('fn_ads_v', self::VERSION);
    }

    private static function roles(): void
    {
        $own = ['edit_fn_creatives', 'edit_published_fn_creatives', 'publish_fn_creatives', 'delete_fn_creatives', 'delete_published_fn_creatives'];
        $all = array_merge($own, ['edit_others_fn_creatives', 'read_private_fn_creatives', 'delete_others_fn_creatives']);

        $advertiser = ['read' => true, 'upload_files' => true];
        foreach ($own as $cap) {
            $advertiser[$cap] = true;
        }
        remove_role('advertiser');
        add_role('advertiser', __('Advertiser', 'foldednews'), $advertiser);

        if (($admin = get_role('administrator')) !== null) {
            foreach ($all as $cap) {
                $admin->add_cap($cap);
            }
        }
    }

    public function boot(): void
    {
        register_post_type('fn_creative', [
            'labels' => Labels::postType('Creative', 'Creatives'),
            'public' => false,
            'show_ui' => true,
            'menu_icon' => 'dashicons-format-image',
            'supports' => ['title', 'thumbnail'],
            'capability_type' => ['fn_creative', 'fn_creatives'],
            'map_meta_cap' => true,
        ]);

        $auth = static fn (): bool => current_user_can('edit_posts');
        register_post_meta('fn_creative', '_fn_campaign', ['type' => 'integer', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'absint', 'auth_callback' => $auth]);
        register_post_meta('fn_creative', '_fn_placement', ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_key', 'auth_callback' => $auth]);
        register_post_meta('fn_creative', '_fn_format', ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_key', 'auth_callback' => $auth]);
        register_post_meta('fn_creative', '_fn_click_url', ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'esc_url_raw', 'auth_callback' => $auth]);
        register_post_meta('fn_creative', '_fn_weight', ['type' => 'integer', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'absint', 'auth_callback' => $auth]);
        register_post_meta('fn_creative', '_fn_approved', ['type' => 'boolean', 'single' => true, 'show_in_rest' => true, 'auth_callback' => static fn (): bool => current_user_can('edit_others_fn_creatives')]);

        register_post_meta('fn_campaign', '_fn_status', ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_key', 'auth_callback' => $auth]);
        register_post_meta('fn_campaign', '_fn_pricing_model', ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_key', 'auth_callback' => $auth]);
        register_post_meta('fn_campaign', '_fn_price', ['type' => 'number', 'single' => true, 'show_in_rest' => true, 'auth_callback' => $auth]);
    }

    public function metaBoxes(): void
    {
        add_meta_box('fn-creative', __('Creative', 'foldednews'), [$this, 'renderCreative'], 'fn_creative', 'normal', 'high');
        add_meta_box('fn-campaign-ads', __('Ad campaign', 'foldednews'), [$this, 'renderCampaign'], 'fn_campaign', 'side', 'high');
    }

    public function renderCreative(WP_Post $post): void
    {
        wp_nonce_field('fn_creative_save', 'fn_creative_nonce');
        $campaigns = get_posts(['post_type' => 'fn_campaign', 'numberposts' => 100, 'post_status' => 'any']);

        echo '<p><label>'.esc_html__('Campaign', 'foldednews').'<br><select name="fn_campaign" style="width:100%">';
        $current = (int) get_post_meta($post->ID, '_fn_campaign', true);
        foreach ($campaigns as $campaign) {
            printf('<option value="%d" %s>%s</option>', $campaign->ID, selected($current, $campaign->ID, false), esc_html(get_the_title($campaign)));
        }
        echo '</select></label></p>';

        $placement = (string) get_post_meta($post->ID, '_fn_placement', true);
        echo '<p><label>'.esc_html__('Placement', 'foldednews').'<br><select name="fn_placement" style="width:100%">';
        foreach (self::PLACEMENTS as $p) {
            printf('<option value="%1$s" %2$s>%1$s</option>', esc_attr($p), selected($placement, $p, false));
        }
        echo '</select></label></p>';

        printf('<p><label>%s<br><input type="url" name="fn_click_url" value="%s" style="width:100%%"></label></p>', esc_html__('Click-through URL', 'foldednews'), esc_attr((string) get_post_meta($post->ID, '_fn_click_url', true)));
        printf('<p><label>%s<br><input type="number" name="fn_image" value="%d" style="width:120px"> %s</label></p>', esc_html__('Image (attachment ID — or set the featured image)', 'foldednews'), (int) get_post_meta($post->ID, '_fn_image', true), '');
        printf('<p><label>%s <input type="number" name="fn_weight" value="%d" min="1" style="width:80px"></label></p>', esc_html__('Weight', 'foldednews'), max(1, (int) get_post_meta($post->ID, '_fn_weight', true)));

        if (current_user_can('edit_others_fn_creatives')) {
            printf('<p><label><input type="checkbox" name="fn_approved" %s> <strong>%s</strong></label></p>', checked((bool) get_post_meta($post->ID, '_fn_approved', true), true, false), esc_html__('Approved (admin/sales)', 'foldednews'));
        }
    }

    public function renderCampaign(WP_Post $post): void
    {
        wp_nonce_field('fn_campaign_ads_save', 'fn_campaign_ads_nonce');
        $status = (string) get_post_meta($post->ID, '_fn_status', true) ?: 'active';
        $model = (string) get_post_meta($post->ID, '_fn_pricing_model', true) ?: 'cpm';

        echo '<p><label>'.esc_html__('Status', 'foldednews').'<br><select name="fn_status">';
        foreach (['active', 'paused'] as $option) {
            printf('<option value="%1$s" %2$s>%1$s</option>', esc_attr($option), selected($status, $option, false));
        }
        echo '</select></label></p>';

        echo '<p><label>'.esc_html__('Pricing', 'foldednews').'<br><select name="fn_pricing_model">';
        foreach (['cpm', 'cpc', 'flat', 'sponsorship'] as $option) {
            printf('<option value="%1$s" %2$s>%1$s</option>', esc_attr($option), selected($model, $option, false));
        }
        echo '</select></label></p>';

        printf('<p><label>%s<br><input type="number" step="0.01" name="fn_price" value="%s" style="width:120px"></label></p>', esc_html__('Price', 'foldednews'), esc_attr((string) get_post_meta($post->ID, '_fn_price', true)));
    }

    public function saveCreative(int $postId): void
    {
        $nonce = isset($_POST['fn_creative_nonce']) ? sanitize_key((string) $_POST['fn_creative_nonce']) : '';
        if ($nonce === '' || wp_verify_nonce($nonce, 'fn_creative_save') === false || ! current_user_can('edit_post', $postId)) {
            return;
        }

        update_post_meta($postId, '_fn_campaign', isset($_POST['fn_campaign']) ? absint($_POST['fn_campaign']) : 0);
        update_post_meta($postId, '_fn_placement', isset($_POST['fn_placement']) ? sanitize_key((string) wp_unslash($_POST['fn_placement'])) : 'leaderboard');
        update_post_meta($postId, '_fn_format', isset($_POST['fn_placement']) ? sanitize_key((string) wp_unslash($_POST['fn_placement'])) : 'leaderboard');
        update_post_meta($postId, '_fn_click_url', isset($_POST['fn_click_url']) ? esc_url_raw((string) wp_unslash($_POST['fn_click_url'])) : '');
        update_post_meta($postId, '_fn_image', isset($_POST['fn_image']) ? absint($_POST['fn_image']) : 0);
        update_post_meta($postId, '_fn_weight', isset($_POST['fn_weight']) ? max(1, absint($_POST['fn_weight'])) : 1);

        // Only approvers may toggle approval.
        if (current_user_can('edit_others_fn_creatives')) {
            update_post_meta($postId, '_fn_approved', isset($_POST['fn_approved']));
        }
    }

    public function saveCampaign(int $postId): void
    {
        $nonce = isset($_POST['fn_campaign_ads_nonce']) ? sanitize_key((string) $_POST['fn_campaign_ads_nonce']) : '';
        if ($nonce === '' || wp_verify_nonce($nonce, 'fn_campaign_ads_save') === false || ! current_user_can('edit_post', $postId)) {
            return;
        }

        update_post_meta($postId, '_fn_status', isset($_POST['fn_status']) ? sanitize_key((string) wp_unslash($_POST['fn_status'])) : 'active');
        update_post_meta($postId, '_fn_pricing_model', isset($_POST['fn_pricing_model']) ? sanitize_key((string) wp_unslash($_POST['fn_pricing_model'])) : 'cpm');
        update_post_meta($postId, '_fn_price', isset($_POST['fn_price']) ? (float) $_POST['fn_price'] : 0.0);
    }

    public function routes(): void
    {
        register_rest_route('foldednews/v1', '/ads/click', ['methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => [$this, 'click']]);
        register_rest_route('foldednews/v1', '/ads/impression', ['methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => [$this, 'impression']]);
    }

    public function click(WP_REST_Request $request): WP_REST_Response
    {
        $creativeId = (int) $request->get_param('c');
        $campaignId = (int) get_post_meta($creativeId, '_fn_campaign', true);
        $url = (string) get_post_meta($creativeId, '_fn_click_url', true);

        if ($creativeId > 0 && $url !== '') {
            Events::record($creativeId, $campaignId, 'click');
            wp_redirect($url, 302); // external advertiser URL
            exit;
        }

        return new WP_REST_Response(['error' => 'not_found'], 404);
    }

    public function impression(WP_REST_Request $request): WP_REST_Response
    {
        $creativeId = (int) $request->get_param('c');
        if ($creativeId > 0) {
            Events::record($creativeId, (int) get_post_meta($creativeId, '_fn_campaign', true), 'impression');
        }

        return new WP_REST_Response(['ok' => true], 200);
    }

    public function recordInterest(): void
    {
        if (is_singular('fn_article')) {
            Interest::recordTopics((int) get_queried_object_id());
        }
    }

    /**
     * Pause a campaign when its Stripe invoice fails (invoice metadata carries
     * the campaign ID). Hooked to the shared Stage 10 webhook dispatcher.
     *
     * @param  array<string, mixed>  $object
     */
    public function onStripeEvent(string $type, array $object): void
    {
        if ($type !== 'invoice.payment_failed') {
            return;
        }

        $metadata = is_array($object['metadata'] ?? null) ? $object['metadata'] : [];
        $campaignId = (int) ($metadata['fn_campaign'] ?? 0);

        if ($campaignId > 0 && get_post_type($campaignId) === 'fn_campaign') {
            update_post_meta($campaignId, '_fn_status', 'paused');
        }
    }

    public function metricsMenu(): void
    {
        add_submenu_page('edit.php?post_type=fn_campaign', __('Ad metrics', 'foldednews'), __('Ad metrics', 'foldednews'), 'edit_others_fn_creatives', 'fn-ad-metrics', [$this, 'renderMetrics']);
    }

    public function renderMetrics(): void
    {
        $byCampaign = Events::byCampaign();

        echo '<div class="wrap"><h1>'.esc_html__('Ad metrics', 'foldednews').'</h1>';
        echo '<table class="widefat striped"><thead><tr><th>'.esc_html__('Campaign', 'foldednews').'</th><th>'.esc_html__('Impressions', 'foldednews').'</th><th>'.esc_html__('Clicks', 'foldednews').'</th><th>CTR</th></tr></thead><tbody>';

        if ($byCampaign === []) {
            echo '<tr><td colspan="4">'.esc_html__('No ad events recorded yet.', 'foldednews').'</td></tr>';
        }

        foreach ($byCampaign as $campaignId => $stats) {
            $ctr = $stats['impressions'] > 0 ? round(($stats['clicks'] / $stats['impressions']) * 100, 2) : 0.0;
            printf(
                '<tr><td>%s</td><td>%d</td><td>%d</td><td>%s%%</td></tr>',
                esc_html(get_the_title($campaignId) ?: ('#'.$campaignId)),
                $stats['impressions'],
                $stats['clicks'],
                esc_html((string) $ctr)
            );
        }

        echo '</tbody></table></div>';
    }
}
