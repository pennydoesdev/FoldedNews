@props(['post'])

@php($id = is_int($post) ? $post : $post->ID)
@php($topic = App\Newsroom\primary_topic($id))
@php($dek = App\Newsroom\dek($id))
@php($byline = App\Newsroom\byline($id))

<article class="grid items-center gap-4 md:grid-cols-2 md:gap-8">
  @if (has_post_thumbnail($id))
    <a href="{{ get_permalink($id) }}" tabindex="-1" aria-hidden="true" class="order-1 block overflow-hidden rounded-lg md:order-2">
      {!! get_the_post_thumbnail($id, 'large', ['class' => 'w-full h-auto object-cover', 'fetchpriority' => 'high']) !!}
    </a>
  @endif

  <div class="order-2 flex flex-col gap-3 md:order-1">
    @if ($topic)
      <span class="text-xs font-semibold uppercase tracking-wide text-brand-700">{{ $topic }}</span>
    @endif

    <h2 class="font-display text-3xl font-bold leading-tight md:text-5xl">
      <a href="{{ get_permalink($id) }}" class="text-ink-900 hover:text-brand-700">{!! get_the_title($id) !!}</a>
    </h2>

    @if ($dek)
      <p class="text-lg text-ink-700">{{ $dek }}</p>
    @endif

    <x-article-date-line :post="$id" />

    @if (! empty($byline))
      <x-author-card :person="$byline[0]" />
    @endif
  </div>
</article>
