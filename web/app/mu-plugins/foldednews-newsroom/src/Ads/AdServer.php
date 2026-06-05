<?php

namespace FoldedNews\Newsroom\Ads;

use WP_Query;

/**
 * Selects an approved creative for a placement from active (non-paused)
 * campaigns, weighted via Selector. Click-throughs route through a tracking
 * endpoint; impressions are beaconed on viewability.
 */
final class AdServer
{
    /**
     * @return array{id: int, campaign: int, image: string, url: string, format: string}|null
     */
    public static function select(string $placement): ?array
    {
        $candidates = self::eligible($placement);
        if ($candidates === []) {
            return null;
        }

        $id = Selector::pick(array_map(
            static fn (array $c): array => ['id' => $c['id'], 'weight' => $c['weight']],
            $candidates
        ));

        foreach ($candidates as $candidate) {
            if ($candidate['id'] === $id) {
                return [
                    'id' => $candidate['id'],
                    'campaign' => $candidate['campaign'],
                    'image' => $candidate['image'],
                    'url' => self::clickUrl($candidate['id']),
                    'format' => $candidate['format'],
                ];
            }
        }

        return null;
    }

    public static function clickUrl(int $creativeId): string
    {
        return add_query_arg(['c' => $creativeId], rest_url('foldednews/v1/ads/click'));
    }

    /**
     * @return list<array{id: int, campaign: int, image: string, url: string, format: string, weight: int}>
     */
    private static function eligible(string $placement): array
    {
        $creatives = (new WP_Query([
            'post_type' => 'fn_creative',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'no_found_rows' => true,
            'meta_query' => [
                'relation' => 'AND',
                ['key' => '_fn_approved', 'value' => '1'],
                ['key' => '_fn_placement', 'value' => $placement],
            ],
        ]))->posts;

        $out = [];
        foreach ($creatives as $creative) {
            $campaignId = (int) get_post_meta($creative->ID, '_fn_campaign', true);
            if ($campaignId > 0 && get_post_meta($campaignId, '_fn_status', true) === 'paused') {
                continue;
            }

            $image = get_the_post_thumbnail_url($creative->ID, 'large');
            if (! is_string($image) || $image === '') {
                $image = (string) wp_get_attachment_url((int) get_post_meta($creative->ID, '_fn_image', true));
            }

            $url = (string) get_post_meta($creative->ID, '_fn_click_url', true);
            if ($image === '' || $url === '') {
                continue;
            }

            $out[] = [
                'id' => $creative->ID,
                'campaign' => $campaignId,
                'image' => $image,
                'url' => $url,
                'format' => (string) get_post_meta($creative->ID, '_fn_format', true) ?: 'leaderboard',
                'weight' => max(1, (int) get_post_meta($creative->ID, '_fn_weight', true)),
            ];
        }

        return $out;
    }
}
