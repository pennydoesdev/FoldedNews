<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use WP_Query;

/**
 * Groups timeline events by year for the timeline archive (vertical by default,
 * horizontal optional). Newest first.
 */
class TimelineArchive extends Composer
{
    /**
     * @var array<int, string>
     */
    protected static $views = ['archive-fn_timeline_event'];

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $query = new WP_Query([
            'post_type' => 'fn_timeline_event',
            'post_status' => 'publish',
            'posts_per_page' => 200,
            'meta_key' => '_fn_event_date',
            'orderby' => 'meta_value',
            'order' => 'DESC',
            'no_found_rows' => true,
        ]);

        $groups = [];
        foreach ($query->posts as $event) {
            $date = (string) get_post_meta($event->ID, '_fn_event_date', true);
            $timestamp = $date !== '' ? strtotime($date) : (int) get_post_time('U', false, $event);
            $year = $timestamp ? gmdate('Y', $timestamp) : __('Undated', 'foldednews');
            $groups[$year][] = $event;
        }

        return [
            'groups' => $groups,
            'layout' => (isset($_GET['layout']) && $_GET['layout'] === 'horizontal') ? 'horizontal' : 'vertical',
        ];
    }
}
