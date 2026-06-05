<?php

namespace App\View\Composers;

use FoldedNews\Newsroom\Live\Updates;
use Roots\Acorn\View\Composer;

/**
 * Prepares the single live-blog view: what we know / don't know, the pinned
 * update, the (ordered) approved update stream, and the latest timestamp the
 * front-end poller starts from.
 */
class LiveBlog extends Composer
{
    /**
     * @var array<int, string>
     */
    protected static $views = ['single-fn_live_blog'];

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $id = (int) get_the_ID();
        $order = (isset($_GET['order']) && strtoupper((string) $_GET['order']) === 'ASC') ? 'ASC' : 'DESC';
        $updates = Updates::forBlog($id, $order);

        $latest = 0;
        foreach ($updates as $update) {
            $latest = max($latest, (int) get_post_time('U', true, $update));
        }

        return [
            'blogId' => $id,
            'order' => $order,
            'latest' => $latest,
            'archived' => (bool) get_post_meta($id, '_fn_archived', true),
            'whatWeKnow' => (string) get_post_meta($id, '_fn_what_we_know', true),
            'whatWeDontKnow' => (string) get_post_meta($id, '_fn_what_we_dont_know', true),
            'pinned' => Updates::pinned($id),
            'updates' => $updates,
        ];
    }
}
