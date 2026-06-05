@extends('layouts.app')

@section('content')
  <div class="fn-video-hub bg-ink-900 text-white">
    <div class="mx-auto max-w-6xl px-4 py-8">
      <h1 class="sr-only">{{ __('Video', 'foldednews') }}</h1>

      @if ($hero)
        <section class="mb-10">
          <x-video-player :video="$hero" />
          <h2 class="mt-3 font-display text-2xl font-bold md:text-3xl">
            <a href="{{ get_permalink($hero->ID) }}" class="hover:text-brand-600">{!! get_the_title($hero->ID) !!}</a>
          </h2>
        </section>
      @endif

      @if (! empty($live))
        <section class="mb-10" aria-label="{{ __('Live', 'foldednews') }}">
          <h2 class="font-display text-xl font-bold text-brand-600">{{ __('Live now', 'foldednews') }}</h2>
          <div class="mt-3 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($live as $v)<x-video-card :video="$v" />@endforeach
          </div>
        </section>
      @endif

      <form role="search" method="get" action="{{ esc_url(home_url('/')) }}" class="mb-8 flex gap-2">
        <input type="hidden" name="post_type" value="fn_video">
        <label class="sr-only" for="video-search">{{ __('Search videos', 'foldednews') }}</label>
        <input id="video-search" type="search" name="s" value="{{ esc_attr(get_search_query()) }}"
               placeholder="{{ __('Search videos', 'foldednews') }}" class="flex-1 rounded px-3 py-2 text-ink-900">
        <button type="submit" class="rounded bg-brand-600 px-4 py-2 font-semibold">{{ __('Search', 'foldednews') }}</button>
      </form>

      @forelse ($rows as $row)
        @if ($row['type'] === 'strip')
          <x-video-strip :videos="$row['videos']" />
        @else
          <div class="mb-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($row['videos'] as $v)<x-video-card :video="$v" />@endforeach
          </div>
        @endif
      @empty
        <p class="text-neutral-300">{{ __('No videos yet.', 'foldednews') }}</p>
      @endforelse

      @if (! empty($shows))
        <section class="my-8" aria-label="{{ __('Shows', 'foldednews') }}">
          <h2 class="font-display text-xl font-bold">{{ __('Shows', 'foldednews') }}</h2>
          <ul class="mt-3 flex flex-wrap gap-3">
            @foreach ($shows as $show)
              <li><a href="{{ esc_url(get_term_link($show)) }}" class="rounded bg-ink-700 px-3 py-1.5 text-sm hover:bg-brand-700">{{ $show->name }}</a></li>
            @endforeach
          </ul>
        </section>
      @endif

      @if (! empty($topics))
        <section class="my-8" aria-label="{{ __('Topics', 'foldednews') }}">
          <h2 class="font-display text-xl font-bold">{{ __('Topics', 'foldednews') }}</h2>
          <ul class="mt-3 flex flex-wrap gap-3">
            @foreach ($topics as $topic)
              <li><a href="{{ esc_url(get_term_link($topic)) }}" class="rounded bg-ink-700 px-3 py-1.5 text-sm hover:bg-brand-700">{{ $topic->name }}</a></li>
            @endforeach
          </ul>
        </section>
      @endif
    </div>
  </div>
@endsection
