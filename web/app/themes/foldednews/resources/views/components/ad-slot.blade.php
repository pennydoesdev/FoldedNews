@props(['placement' => 'leaderboard'])

@php($ad = \FoldedNews\Newsroom\Ads\AdServer::select($placement))

@if ($ad)
  <aside class="fn-ad fn-ad--{{ $ad['format'] }} my-6 text-center"
         data-ad="{{ $ad['id'] }}"
         data-beacon="{{ esc_url(rest_url('foldednews/v1/ads/impression')) }}">
    <span class="block text-[10px] uppercase tracking-wide text-ink-500">{{ __('Advertisement', 'foldednews') }}</span>
    <a href="{{ esc_url($ad['url']) }}" rel="sponsored nofollow noopener" target="_blank" class="mt-1 inline-block">
      <img src="{{ esc_url($ad['image']) }}" alt="" loading="lazy" class="mx-auto h-auto max-w-full">
    </a>
  </aside>
@endif
