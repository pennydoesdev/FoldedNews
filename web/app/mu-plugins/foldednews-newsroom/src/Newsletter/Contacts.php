<?php

namespace FoldedNews\Newsroom\Newsletter;

use WP_Post;
use WP_Query;

/**
 * Contact store (fn_contact): email, status, consent log, list/tag taxonomies.
 * Unsubscribed contacts act as the suppression list (sends skip them).
 */
final class Contacts
{
    public const EMAIL_META = '_fn_email';
    public const STATUS_META = '_fn_status';
    public const CONSENT_META = '_fn_consent';

    public static function findByEmail(string $email): int
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return 0;
        }

        /** @var list<int> $ids */
        $ids = (new WP_Query([
            'post_type' => 'fn_contact',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => [['key' => self::EMAIL_META, 'value' => $email]],
        ]))->posts;

        return $ids !== [] ? (int) $ids[0] : 0;
    }

    /**
     * Create or update a subscribed contact and log consent.
     */
    public static function subscribe(string $email, string $name, string $source, int $listId = 0): int
    {
        $email = sanitize_email($email);
        if ($email === '' || ! is_email($email)) {
            return 0;
        }

        $id = self::findByEmail($email);
        if ($id === 0) {
            $insert = wp_insert_post([
                'post_type' => 'fn_contact',
                'post_status' => 'publish',
                'post_title' => $name !== '' ? sanitize_text_field($name) : $email,
            ], true);

            if (! is_int($insert) || $insert <= 0) {
                return 0;
            }
            $id = $insert;
            update_post_meta($id, self::EMAIL_META, strtolower($email));
        }

        update_post_meta($id, self::STATUS_META, 'subscribed');
        self::logConsent($id, $source);

        if ($listId > 0) {
            wp_set_object_terms($id, [$listId], 'mailing_list', true);
        }

        return $id;
    }

    public static function setStatus(int $id, string $status): void
    {
        if ($id > 0) {
            update_post_meta($id, self::STATUS_META, sanitize_text_field($status));
        }
    }

    public static function isSubscribed(int $id): bool
    {
        return (string) get_post_meta($id, self::STATUS_META, true) === 'subscribed';
    }

    public static function email(int $id): string
    {
        return (string) get_post_meta($id, self::EMAIL_META, true);
    }

    public static function logConsent(int $id, string $source): void
    {
        $log = get_post_meta($id, self::CONSENT_META, true);
        $log = is_array($log) ? $log : [];
        $log[] = [
            'time' => time(),
            'source' => sanitize_text_field($source),
            'ip' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) $_SERVER['REMOTE_ADDR']) : '',
        ];

        update_post_meta($id, self::CONSENT_META, array_slice($log, -20));
    }

    public static function unsubscribeUrl(string $email): string
    {
        $email = strtolower(trim($email));

        return add_query_arg([
            'email' => rawurlencode($email),
            'token' => Token::sign($email, wp_salt('nonce')),
        ], home_url('/newsletter/preferences/'));
    }

    /**
     * Render rows to a CSV string. Pure — unit-tested.
     *
     * @param  list<list<string>>  $rows
     */
    public static function toCsv(array $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            return '';
        }

        foreach ($rows as $row) {
            fputcsv($stream, $row, ',', '"', '\\');
        }

        rewind($stream);

        return (string) stream_get_contents($stream);
    }

    public static function exportCsv(): string
    {
        $rows = [['email', 'name', 'status', 'lists']];

        /** @var list<WP_Post> $contacts */
        $contacts = (new WP_Query([
            'post_type' => 'fn_contact',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'no_found_rows' => true,
        ]))->posts;

        foreach ($contacts as $contact) {
            $lists = wp_get_object_terms($contact->ID, 'mailing_list', ['fields' => 'names']);
            $rows[] = [
                self::email($contact->ID),
                $contact->post_title,
                (string) get_post_meta($contact->ID, self::STATUS_META, true),
                is_array($lists) ? implode('|', array_map('strval', $lists)) : '',
            ];
        }

        return self::toCsv($rows);
    }

    /**
     * Import a CSV with an email column (optional name). Returns count.
     */
    public static function importCsv(string $csv, int $listId = 0): int
    {
        $count = 0;
        foreach (preg_split('/\r\n|\r|\n/', trim($csv)) ?: [] as $i => $line) {
            if ($line === '') {
                continue;
            }
            $cols = str_getcsv($line, ',', '"', '\\');
            $email = sanitize_email((string) ($cols[0] ?? ''));
            if ($i === 0 && ($email === '' || ! is_email($email))) {
                continue; // header row
            }
            $name = isset($cols[1]) ? (string) $cols[1] : '';
            if (self::subscribe($email, $name, 'csv_import', $listId) > 0) {
                $count++;
            }
        }

        return $count;
    }

    public static function importWordPressUsers(): int
    {
        $count = 0;
        foreach (get_users(['fields' => ['user_email', 'display_name']]) as $user) {
            if (self::subscribe((string) $user->user_email, (string) $user->display_name, 'wp_user_import') > 0) {
                $count++;
            }
        }

        return $count;
    }
}
