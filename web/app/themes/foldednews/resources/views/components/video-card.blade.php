@props(['video', 'aspect' => null])

@php($p = is_int($video) ? get_post($video) : $video)
@php($id = $p->ID)
@php($ratio = $aspect ?? App\Newsroom\video_aspect($p))
@php($poster = App\Newsroom\video_poster($p))

<article {{ $attributes->merge(['class' => 'fn-video-card']) }}>
  <a href="{{ get_permalink($id) }}" tabindex="-1" aria-hidden="true"
     class="block overflow-hidden rounded bg-neutral-900 {{ App\Newsroom\aspect_class($ratio) }}">
    @if ($poster)
      <img src="{{ esc_url($poster) }}" alt="" loading="lazy" class="h-full w-full object-cover">
    @endif
  </a>
  @php($live = App\Newsroom\video_is_live($p))
  <h3 class="mt-2 font-display text-base font-semibold leading-snug">
    @if ($live)<span class="mr-1 rounded bg-brand-600 px-1.5 py-0.5 text-xs font-bold uppercase text-white">{{ __('Live', 'foldednews') }}</span>@endif
    <a href="{{ get_permalink($id) }}" class="hover:text-brand-700">{!! get_the_title($id) !!}</a>
  </h3>
</article>
