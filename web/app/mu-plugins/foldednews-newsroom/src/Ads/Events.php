<?php

namespace FoldedNews\Newsroom\Ads;

/**
 * Ad event counts (impressions / clicks) in a dedicated table, incremented
 * atomically (INSERT ... ON DUPLICATE KEY UPDATE) and aggregated for metrics.
 */
final class Events
{
    public static function table(): string
    {
        global $wpdb;

        return $wpdb->prefix.'fn_ad_events';
    }

    public static function install(): void
    {
        global $wpdb;

        require_once ABSPATH.'wp-admin/includes/upgrade.php';
        $table = self::table();
        $charset = $wpdb->get_charset_collate();

        dbDelta("CREATE TABLE {$table} (
            creative_id bigint(20) unsigned NOT NULL,
            campaign_id bigint(20) unsigned NOT NULL,
            event_type varchar(16) NOT NULL,
            event_day date NOT NULL,
            count bigint(20) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (creative_id,event_type,event_day),
            KEY campaign_id (campaign_id)
        ) {$charset};");
    }

    public static function record(int $creativeId, int $campaignId, string $type): void
    {
        if ($creativeId <= 0 || ! in_array($type, ['impression', 'click'], true)) {
            return;
        }

        global $wpdb;
        $table = self::table();

        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$table} (creative_id, campaign_id, event_type, event_day, count)
             VALUES (%d, %d, %s, %s, 1)
             ON DUPLICATE KEY UPDATE count = count + 1",
            $creativeId,
            $campaignId,
            $type,
            gmdate('Y-m-d')
        ));
    }

    /**
     * Aggregate impressions/clicks per campaign.
     *
     * @return array<int, array{impressions: int, clicks: int}>
     */
    public static function byCampaign(): array
    {
        global $wpdb;
        $table = self::table();

        $rows = $wpdb->get_results("SELECT campaign_id, event_type, SUM(count) AS total FROM {$table} GROUP BY campaign_id, event_type", 'ARRAY_A');

        $out = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $campaign = (int) $row['campaign_id'];
            $out[$campaign] ??= ['impressions' => 0, 'clicks' => 0];
            $key = $row['event_type'] === 'click' ? 'clicks' : 'impressions';
            $out[$campaign][$key] = (int) $row['total'];
        }

        return $out;
    }
}
