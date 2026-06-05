@props(['episode'])

@php($id = is_int($episode) ? $episode : $episode->ID)
@php($audio = App\Newsroom\podcast_audio($id))
@php($duration = App\Newsroom\podcast_duration($id))

<article class="flex gap-4 border-b border-neutral-100 pb-6">
  @if (has_post_thumbnail($id))
    <a href="{{ get_permalink($id) }}" tabindex="-1" aria-hidden="true" class="shrink-0">
      {!! get_the_post_thumbnail($id, 'thumbnail', ['class' => 'h-20 w-20 rounded object-cover', 'loading' => 'lazy']) !!}
    </a>
  @endif
  <div class="min-w-0 flex-1">
    <h3 class="font-display text-lg font-semibold leading-snug">
      @if (App\Newsroom\podcast_is_premium($id))
        <span class="mr-1 rounded bg-amber-300 px-1.5 py-0.5 text-xs font-bold uppercase text-amber-900">{{ __('Premium', 'foldednews') }}</span>
      @endif
      <a href="{{ get_permalink($id) }}" class="hover:text-brand-700">{!! get_the_title($id) !!}</a>
    </h3>
    <p class="text-xs text-ink-500">
      <time datetime="{{ get_post_time('c', true, $id) }}">{{ get_the_date('', $id) }}</time>@if ($duration) · {{ $duration }}@endif
    </p>
    @if ($audio)<x-audio-player :src="$audio" />@endif
  </div>
</article>
