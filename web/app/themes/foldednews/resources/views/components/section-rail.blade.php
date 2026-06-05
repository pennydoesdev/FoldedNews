@props(['title', 'posts' => [], 'url' => ''])

@if (! empty($posts))
  @php($anchor = 'rail-' . sanitize_title($title))
  <section class="border-t border-neutral-200 py-8" aria-labelledby="{{ $anchor }}">
    <div class="mb-4 flex items-baseline justify-between gap-4">
      <h2 id="{{ $anchor }}" class="font-display text-xl font-bold text-ink-900">
        @if ($url)
          <a href="{{ $url }}" class="hover:text-brand-700">{{ $title }}</a>
        @else
          {{ $title }}
        @endif
      </h2>
    </div>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
      @foreach ($posts as $post)
        <x-article-card :post="$post" />
      @endforeach
    </div>
  </section>
@endif
