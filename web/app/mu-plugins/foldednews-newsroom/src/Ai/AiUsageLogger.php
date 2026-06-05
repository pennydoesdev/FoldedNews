<?php

namespace FoldedNews\Newsroom\Ai;

/**
 * Logs every AI call (feature, provider, model, tokens, cost, status, a short
 * output excerpt) to a dedicated table. Never logs API keys.
 */
final class AiUsageLogger
{
    public static function table(): string
    {
        global $wpdb;

        return $wpdb->prefix.'fn_ai_log';
    }

    public static function install(): void
    {
        global $wpdb;

        require_once ABSPATH.'wp-admin/includes/upgrade.php';
        $table = self::table();
        $charset = $wpdb->get_charset_collate();

        dbDelta("CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            created_at datetime NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            feature varchar(64) NOT NULL,
            provider varchar(32) NOT NULL,
            model varchar(96) NOT NULL,
            prompt_tokens int unsigned NOT NULL DEFAULT 0,
            completion_tokens int unsigned NOT NULL DEFAULT 0,
            cost decimal(12,6) NOT NULL DEFAULT 0,
            status varchar(16) NOT NULL,
            excerpt text NULL,
            PRIMARY KEY  (id),
            KEY feature (feature),
            KEY provider (provider)
        ) {$charset};");
    }

    public static function log(string $feature, AiResult $result, float $cost): void
    {
        global $wpdb;

        $wpdb->insert(self::table(), [
            'created_at' => gmdate('Y-m-d H:i:s'),
            'user_id' => get_current_user_id(),
            'feature' => $feature,
            'provider' => $result->provider,
            'model' => $result->model,
            'prompt_tokens' => $result->promptTokens,
            'completion_tokens' => $result->completionTokens,
            'cost' => $cost,
            'status' => $result->ok ? 'ok' : 'error',
            'excerpt' => mb_substr($result->ok ? $result->text : $result->error, 0, 500),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function recent(int $limit = 50): array
    {
        global $wpdb;
        $table = self::table();

        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit), 'ARRAY_A');

        return is_array($rows) ? $rows : [];
    }
}
