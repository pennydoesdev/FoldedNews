@props(['post'])

@php($id = is_int($post) ? $post : $post->ID)
@php($userId = get_current_user_id())
@php($saved = $userId > 0 && \FoldedNews\Newsroom\Bookmarks\Bookmarks::isSaved($userId, $id))

<button type="button" {{ $attributes->merge(['class' => 'fn-bookmark inline-flex items-center gap-1 text-sm']) }}
        data-fn-bookmark
        data-post="{{ $id }}"
        data-saved="{{ $saved ? '1' : '0' }}"
        data-logged-in="{{ $userId > 0 ? '1' : '0' }}"
        data-rest="{{ esc_url(rest_url('foldednews/v1/bookmarks')) }}"
        data-nonce="{{ wp_create_nonce('wp_rest') }}"
        aria-pressed="{{ $saved ? 'true' : 'false' }}">
  <span class="fn-bookmark-icon" aria-hidden="true">{{ $saved ? '★' : '☆' }}</span>
  <span class="fn-bookmark-label">{{ $saved ? __('Saved', 'foldednews') : __('Save', 'foldednews') }}</span>
</button>
