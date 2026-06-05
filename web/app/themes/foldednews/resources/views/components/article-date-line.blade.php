@props(['post'])

@php($id = is_int($post) ? $post : $post->ID)

<p {{ $attributes->merge(['class' => 'text-xs text-ink-500']) }}>
  <time datetime="{{ App\Newsroom\published_iso($id) }}">{{ get_the_date('', $id) }}</time>
  @if (App\Newsroom\is_updated($id))
    <span aria-hidden="true"> · </span>
    <span>{{ __('Updated', 'foldednews') }}
      <time datetime="{{ App\Newsroom\modified_iso($id) }}">{{ get_the_modified_date('', $id) }}</time></span>
  @endif
</p>
