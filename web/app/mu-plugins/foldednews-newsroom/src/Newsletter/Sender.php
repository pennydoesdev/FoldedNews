<?php

namespace FoldedNews\Newsroom\Newsletter;

use WP_Post;
use WP_Query;

/**
 * Sends a campaign to a list in cron-driven batches via wp_mail (Mailpit in dev,
 * msmtp/SMTP in production). Skips non-subscribed (suppressed) contacts and adds
 * a one-click unsubscribe footer to every message.
 */
final class Sender
{
    private const BATCH = 50;

    public static function queue(int $campaignId, int $listId): void
    {
        update_post_meta($campaignId, '_fn_send_list', $listId);
        update_post_meta($campaignId, '_fn_send_offset', 0);
        update_post_meta($campaignId, '_fn_send_status', 'sending');

        wp_schedule_single_event(time() + 10, 'fn_newsletter_send', [$campaignId]);
    }

    public static function processBatch(int $campaignId): void
    {
        $campaign = get_post($campaignId);
        if (! $campaign instanceof WP_Post) {
            return;
        }

        $listId = (int) get_post_meta($campaignId, '_fn_send_list', true);
        $offset = (int) get_post_meta($campaignId, '_fn_send_offset', true);

        /** @var list<WP_Post> $contacts */
        $contacts = (new WP_Query([
            'post_type' => 'fn_contact',
            'post_status' => 'publish',
            'posts_per_page' => self::BATCH,
            'offset' => $offset,
            'no_found_rows' => true,
            'tax_query' => [['taxonomy' => 'mailing_list', 'field' => 'term_id', 'terms' => $listId]],
            'meta_query' => [['key' => Contacts::STATUS_META, 'value' => 'subscribed']],
        ]))->posts;

        $subject = get_the_title($campaign);
        $content = (string) apply_filters('the_content', $campaign->post_content);
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        foreach ($contacts as $contact) {
            $email = Contacts::email($contact->ID);
            if ($email === '') {
                continue;
            }

            $footer = '<hr><p style="font-size:12px;color:#888">'
                .esc_html__('You are receiving this because you subscribed.', 'foldednews')
                .' <a href="'.esc_url(Contacts::unsubscribeUrl($email)).'">'.esc_html__('Unsubscribe', 'foldednews').'</a></p>';

            wp_mail($email, $subject, $content.$footer, $headers);
        }

        if (count($contacts) < self::BATCH) {
            update_post_meta($campaignId, '_fn_send_status', 'sent');

            return;
        }

        update_post_meta($campaignId, '_fn_send_offset', $offset + self::BATCH);
        wp_schedule_single_event(time() + 30, 'fn_newsletter_send', [$campaignId]);
    }
}
