@props(['post', 'size' => 'md', 'showDek' => false])

@php($id = is_int($post) ? $post : $post->ID)
@php($topic = App\Newsroom\primary_topic($id))

<article {{ $attributes->merge(['class' => 'flex flex-col gap-2']) }}>
  @if (has_post_thumbnail($id))
    <a href="{{ get_permalink($id) }}" tabindex="-1" aria-hidden="true" class="block overflow-hidden rounded">
      {!! get_the_post_thumbnail($id, $size === 'lg' ? 'large' : 'medium', ['class' => 'w-full h-auto object-cover', 'loading' => 'lazy']) !!}
    </a>
  @endif

  @if ($topic)
    <span class="text-xs font-semibold uppercase tracking-wide text-brand-700">{{ $topic }}</span>
  @endif

  <h3 class="font-display {{ $size === 'lg' ? 'text-2xl' : 'text-lg' }} font-semibold leading-snug">
    <a href="{{ get_permalink($id) }}" class="text-ink-900 hover:text-brand-700">{!! get_the_title($id) !!}</a>
  </h3>

  @if ($showDek && ($dek = App\Newsroom\dek($id)))
    <p class="text-sm text-ink-500">{{ $dek }}</p>
  @endif

  <x-article-date-line :post="$id" />
</article>
