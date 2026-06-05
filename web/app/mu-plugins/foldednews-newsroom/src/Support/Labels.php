<?php

namespace FoldedNews\Newsroom\Support;

final class Labels
{
    /**
     * @return array<string, string>
     */
    public static function postType(string $singular, string $plural): array
    {
        $lower = strtolower($plural);

        return [
            'name' => $plural,
            'singular_name' => $singular,
            'menu_name' => $plural,
            'all_items' => "All {$plural}",
            'add_new' => 'Add New',
            'add_new_item' => "Add New {$singular}",
            'edit_item' => "Edit {$singular}",
            'new_item' => "New {$singular}",
            'view_item' => "View {$singular}",
            'view_items' => "View {$plural}",
            'search_items' => "Search {$plural}",
            'not_found' => "No {$lower} found",
            'not_found_in_trash' => "No {$lower} found in Trash",
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function taxonomy(string $singular, string $plural): array
    {
        return [
            'name' => $plural,
            'singular_name' => $singular,
            'menu_name' => $plural,
            'all_items' => "All {$plural}",
            'edit_item' => "Edit {$singular}",
            'update_item' => "Update {$singular}",
            'add_new_item' => "Add New {$singular}",
            'new_item_name' => "New {$singular} Name",
            'search_items' => "Search {$plural}",
        ];
    }
}
