@props(['post', 'pinned' => false])

@php($p = is_int($post) ? get_post($post) : $post)
@php($id = $p->ID)
@php($reporter = FoldedNews\Newsroom\Live\Updates::reporter($p))
@php($correction = FoldedNews\Newsroom\Live\Updates::correction($p))
@php($sources = FoldedNews\Newsroom\Live\Updates::sources($p))

<article id="update-{{ $id }}" {{ $attributes->merge(['class' => 'fn-update border-l-2 border-brand-600 pl-4' . ($pinned ? ' rounded bg-neutral-50 p-4' : '')]) }}>
  <header class="flex flex-wrap items-center gap-2 text-xs text-ink-500">
    @if ($pinned)
      <span class="rounded bg-brand-600 px-1.5 py-0.5 font-semibold uppercase text-white">{{ __('Pinned', 'foldednews') }}</span>
    @endif
    <time datetime="{{ get_post_time('c', true, $id) }}">{{ get_the_time(get_option('date_format') . ' · ' . get_option('time_format'), $id) }}</time>
    @if ($reporter)<span>· {{ $reporter }}</span>@endif
    @if ($correction)
      <span class="rounded bg-amber-200 px-1.5 py-0.5 font-semibold uppercase text-amber-900">{{ $correction }}</span>
    @endif
  </header>

  @if (get_the_title($id))
    <h3 class="mt-1 font-display text-lg font-semibold">{!! get_the_title($id) !!}</h3>
  @endif

  <div class="prose prose-sm mt-2 max-w-none">{!! apply_filters('the_content', $p->post_content) !!}</div>

  @if (! empty($sources))
    <p class="mt-2 text-xs text-ink-500">{{ __('Sources:', 'foldednews') }}
      @foreach ($sources as $src)<a href="{{ esc_url($src) }}" rel="nofollow noopener" class="underline">{{ parse_url($src, PHP_URL_HOST) ?: $src }}</a>@if (! $loop->last), @endif @endforeach
    </p>
  @endif
</article>
