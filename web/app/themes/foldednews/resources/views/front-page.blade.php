@extends('layouts.app')

@section('content')
  <x-breaking-banner :posts="$breaking" />

  <div class="mx-auto max-w-5xl px-4">
    <h1 class="sr-only">{{ get_bloginfo('name', 'display') }} — {{ __('Latest news', 'foldednews') }}</h1>

    @if ($hero)
      <div class="py-8">
        <x-article-hero :post="$hero" />
      </div>
    @endif

    @if (! empty($lead))
      <section class="grid gap-6 border-t border-neutral-200 py-8 sm:grid-cols-2 lg:grid-cols-4" aria-label="{{ __('Top stories', 'foldednews') }}">
        @foreach ($lead as $post)
          <x-article-card :post="$post" :show-dek="true" />
        @endforeach
      </section>
    @endif

    <div class="grid gap-8 lg:grid-cols-3">
      <div class="lg:col-span-2">
        <x-section-rail :title="__('Latest', 'foldednews')" :posts="$latest" />
      </div>

      <div class="lg:col-span-1">
        @if (! empty($live))
          <section class="border-t border-neutral-200 py-8" aria-label="{{ __('Live updates', 'foldednews') }}">
            <h2 class="font-display text-xl font-bold text-ink-900">{{ __('Live updates', 'foldednews') }}</h2>
            <ul class="mt-4 space-y-4">
              @foreach ($live as $post)
                @php($id = is_int($post) ? $post : $post->ID)
                <li class="border-l-2 border-brand-600 pl-3">
                  <x-article-date-line :post="$id" />
                  <a href="{{ get_permalink($id) }}" class="font-semibold hover:text-brand-700">{!! get_the_title($id) !!}</a>
                </li>
              @endforeach
            </ul>
          </section>
        @endif

        <x-newsletter-signup />
      </div>
    </div>

    @foreach ($sections as $section)
      <x-section-rail :title="$section['label']" :url="$section['url']" :posts="$section['posts']" />
    @endforeach
  </div>
@endsection
