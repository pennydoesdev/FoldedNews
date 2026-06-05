@extends('layouts.app')

@section('content')
  <div class="mx-auto max-w-6xl px-4 py-8">
    <h1 class="font-display text-3xl font-bold">{{ __('Podcasts', 'foldednews') }}</h1>

    @if ($hero)
      <section class="mt-6 grid items-center gap-6 md:grid-cols-2">
        @if (has_post_thumbnail($hero->ID))
          <div>{!! get_the_post_thumbnail($hero->ID, 'large', ['class' => 'w-full rounded']) !!}</div>
        @endif
        <div>
          <h2 class="font-display text-2xl font-bold"><a href="{{ get_permalink($hero->ID) }}" class="hover:text-brand-700">{!! get_the_title($hero->ID) !!}</a></h2>
          @php($audio = App\Newsroom\podcast_audio($hero->ID))
          @if ($audio)<x-audio-player :src="$audio" preload="metadata" />@endif
        </div>
      </section>
    @endif

    @if (! empty($shows))
      <section class="mt-10" aria-label="{{ __('Shows', 'foldednews') }}">
        <h2 class="font-display text-xl font-bold">{{ __('Shows', 'foldednews') }}</h2>
        <div class="mt-3 grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-6">
          @foreach ($shows as $show)
            <a href="{{ esc_url(get_term_link($show)) }}" class="block">
              @php($art = App\Newsroom\show_meta($show->term_id, 'artwork'))
              <div class="aspect-square overflow-hidden rounded bg-neutral-200">
                @if ($art)<img src="{{ esc_url($art) }}" alt="" loading="lazy" class="h-full w-full object-cover">@endif
              </div>
              <p class="mt-1 text-sm font-semibold">{{ $show->name }}</p>
            </a>
          @endforeach
        </div>
      </section>
    @endif

    @if (! empty($categories))
      <section class="mt-8" aria-label="{{ __('Categories', 'foldednews') }}">
        <ul class="flex flex-wrap gap-2 text-sm">
          @foreach ($categories as $cat)
            <li><a href="{{ esc_url(get_term_link($cat)) }}" class="rounded bg-neutral-100 px-3 py-1.5 hover:bg-neutral-200">{{ $cat->name }}</a></li>
          @endforeach
        </ul>
      </section>
    @endif

    <section class="mt-10" aria-label="{{ __('Latest episodes', 'foldednews') }}">
      <h2 class="font-display text-xl font-bold">{{ __('Latest episodes', 'foldednews') }}</h2>
      <div class="mt-4 space-y-6">
        @forelse ($latest as $ep)
          <x-podcast-card :episode="$ep" />
        @empty
          <p class="text-ink-600">{{ __('No episodes yet.', 'foldednews') }}</p>
        @endforelse
      </div>
    </section>
  </div>
@endsection
