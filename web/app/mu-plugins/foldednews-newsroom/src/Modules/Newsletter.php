<?php

namespace FoldedNews\Newsroom\Modules;

use FoldedNews\Newsroom\Module;
use FoldedNews\Newsroom\Newsletter\Contacts;
use FoldedNews\Newsroom\Newsletter\Sender;
use FoldedNews\Newsroom\Newsletter\Token;
use FoldedNews\Newsroom\Support\Labels;
use WP_REST_Request;
use WP_REST_Response;
use WP_Term;

/**
 * Internal email marketing: Lists + Tags taxonomies, the subscribe + preferences
 * endpoints (consent logged), cron-batched campaign sending, and an admin page
 * for CSV import/export, WordPress-user import, and queueing a campaign.
 */
final class Newsletter implements Module
{
    public function register(): void
    {
        add_action('init', [$this, 'boot']);
        add_action('rest_api_init', [$this, 'routes']);
        add_action('fn_newsletter_send', [Sender::class, 'processBatch']);

        add_action('admin_menu', [$this, 'adminMenu']);
        add_action('admin_post_fn_nl_export', [$this, 'handleExport']);
        add_action('admin_post_fn_nl_import_users', [$this, 'handleImportUsers']);
        add_action('admin_post_fn_nl_import_csv', [$this, 'handleImportCsv']);
        add_action('admin_post_fn_nl_send', [$this, 'handleSend']);
    }

    public function boot(): void
    {
        register_taxonomy('mailing_list', ['fn_contact'], [
            'labels' => Labels::taxonomy('List', 'Lists'),
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
        ]);
        register_taxonomy('contact_tag', ['fn_contact'], [
            'labels' => Labels::taxonomy('Tag', 'Tags'),
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
        ]);

        $auth = static fn (): bool => current_user_can('edit_posts');
        register_post_meta('fn_contact', Contacts::EMAIL_META, ['type' => 'string', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => 'sanitize_email', 'auth_callback' => $auth]);
        register_post_meta('fn_contact', Contacts::STATUS_META, ['type' => 'string', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => 'sanitize_text_field', 'auth_callback' => $auth]);
    }

    public function routes(): void
    {
        register_rest_route('foldednews/v1', '/newsletter/subscribe', ['methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => [$this, 'subscribe']]);
        register_rest_route('foldednews/v1', '/newsletter/preferences', ['methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => [$this, 'preferences']]);
    }

    public function subscribe(WP_REST_Request $request): WP_REST_Response
    {
        if (wp_verify_nonce((string) $request->get_param('nonce'), 'foldednews_newsletter') === false) {
            return new WP_REST_Response(['error' => 'bad_nonce'], 403);
        }

        $id = Contacts::subscribe(
            (string) $request->get_param('email'),
            (string) $request->get_param('name'),
            'signup_form',
            $this->listId((string) $request->get_param('list'))
        );

        return $id > 0
            ? new WP_REST_Response(['subscribed' => true], 200)
            : new WP_REST_Response(['error' => 'invalid_email'], 400);
    }

    public function preferences(WP_REST_Request $request): WP_REST_Response
    {
        $email = strtolower(trim((string) $request->get_param('email')));

        if (! Token::verify($email, (string) $request->get_param('token'), wp_salt('nonce'))) {
            return new WP_REST_Response(['error' => 'bad_token'], 403);
        }

        $id = Contacts::findByEmail($email);
        if ($id === 0) {
            return new WP_REST_Response(['error' => 'not_found'], 404);
        }

        if ($request->get_param('unsubscribe')) {
            Contacts::setStatus($id, 'unsubscribed');

            return new WP_REST_Response(['unsubscribed' => true], 200);
        }

        $termIds = [];
        foreach ((array) $request->get_param('lists') as $slug) {
            if (($listId = $this->listId((string) $slug)) > 0) {
                $termIds[] = $listId;
            }
        }
        wp_set_object_terms($id, $termIds, 'mailing_list', false);
        Contacts::setStatus($id, 'subscribed');

        return new WP_REST_Response(['updated' => true], 200);
    }

    private function listId(string $slug): int
    {
        if ($slug === '') {
            return 0;
        }
        $term = get_term_by('slug', sanitize_title($slug), 'mailing_list');

        return $term instanceof WP_Term ? $term->term_id : 0;
    }

    public function adminMenu(): void
    {
        add_submenu_page('edit.php?post_type=fn_contact', __('Newsletter', 'foldednews'), __('Newsletter', 'foldednews'), 'manage_options', 'fn-newsletter', [$this, 'renderAdmin']);
    }

    public function renderAdmin(): void
    {
        $action = admin_url('admin-post.php');
        echo '<div class="wrap"><h1>'.esc_html__('Newsletter', 'foldednews').'</h1>';

        echo '<h2>'.esc_html__('Export / Import', 'foldednews').'</h2><p>';
        echo '<a class="button" href="'.esc_url(wp_nonce_url($action.'?action=fn_nl_export', 'fn_nl_export')).'">'.esc_html__('Export contacts CSV', 'foldednews').'</a> ';
        echo '</p>';

        printf('<form method="post" action="%s" style="margin-bottom:1em">', esc_url($action));
        wp_nonce_field('fn_nl_import_users');
        echo '<input type="hidden" name="action" value="fn_nl_import_users"><button class="button">'.esc_html__('Import WordPress users as contacts', 'foldednews').'</button></form>';

        printf('<form method="post" enctype="multipart/form-data" action="%s" style="margin-bottom:2em">', esc_url($action));
        wp_nonce_field('fn_nl_import_csv');
        echo '<input type="hidden" name="action" value="fn_nl_import_csv"><input type="file" name="csv" accept=".csv" required> '.$this->listSelect('list_id').' <button class="button">'.esc_html__('Import CSV', 'foldednews').'</button></form>';

        echo '<h2>'.esc_html__('Send a campaign', 'foldednews').'</h2>';
        printf('<form method="post" action="%s">', esc_url($action));
        wp_nonce_field('fn_nl_send');
        echo '<input type="hidden" name="action" value="fn_nl_send">'.$this->campaignSelect().' '.$this->listSelect('list_id').' <button class="button button-primary">'.esc_html__('Queue send', 'foldednews').'</button></form>';

        echo '</div>';
    }

    private function listSelect(string $name): string
    {
        $terms = get_terms(['taxonomy' => 'mailing_list', 'hide_empty' => false]);
        $html = '<select name="'.esc_attr($name).'"><option value="0">'.esc_html__('— list —', 'foldednews').'</option>';
        if (is_array($terms)) {
            foreach ($terms as $term) {
                $html .= sprintf('<option value="%d">%s</option>', $term->term_id, esc_html($term->name));
            }
        }

        return $html.'</select>';
    }

    private function campaignSelect(): string
    {
        $posts = get_posts(['post_type' => ['fn_campaign', 'fn_newsletter'], 'numberposts' => 50, 'post_status' => 'any']);
        $html = '<select name="campaign_id">';
        foreach ($posts as $post) {
            $html .= sprintf('<option value="%d">%s</option>', $post->ID, esc_html(get_the_title($post)));
        }

        return $html.'</select>';
    }

    public function handleExport(): void
    {
        $this->guard('fn_nl_export');
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="contacts.csv"');
        echo Contacts::exportCsv();
        exit;
    }

    public function handleImportUsers(): void
    {
        $this->guard('fn_nl_import_users');
        $this->redirect(['imported' => Contacts::importWordPressUsers()]);
    }

    public function handleImportCsv(): void
    {
        $this->guard('fn_nl_import_csv');
        $tmp = isset($_FILES['csv']['tmp_name']) ? (string) $_FILES['csv']['tmp_name'] : '';
        $csv = $tmp !== '' && is_uploaded_file($tmp) ? (string) file_get_contents($tmp) : '';
        $listId = isset($_POST['list_id']) ? absint($_POST['list_id']) : 0;
        $this->redirect(['imported' => $csv !== '' ? Contacts::importCsv($csv, $listId) : 0]);
    }

    public function handleSend(): void
    {
        $this->guard('fn_nl_send');
        $campaignId = isset($_POST['campaign_id']) ? absint($_POST['campaign_id']) : 0;
        $listId = isset($_POST['list_id']) ? absint($_POST['list_id']) : 0;
        if ($campaignId > 0 && $listId > 0) {
            Sender::queue($campaignId, $listId);
        }
        $this->redirect(['queued' => $campaignId > 0 && $listId > 0 ? 1 : 0]);
    }

    private function guard(string $action): void
    {
        if (! current_user_can('manage_options') || ! check_admin_referer($action)) {
            wp_die(esc_html__('Forbidden', 'foldednews'));
        }
    }

    /**
     * @param  array<string, int>  $args
     */
    private function redirect(array $args): void
    {
        wp_safe_redirect(add_query_arg($args, admin_url('edit.php?post_type=fn_contact&page=fn-newsletter')));
        exit;
    }
}
