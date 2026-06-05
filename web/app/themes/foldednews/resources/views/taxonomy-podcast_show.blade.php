@extends('layouts.app')

@section('content')
  @php($term = get_queried_object())
  @php($tid = $term->term_id)
  @php($artwork = App\Newsroom\show_meta($tid, 'artwork'))

  <div class="mx-auto max-w-4xl px-4 py-8">
    <header class="flex flex-col gap-4 sm:flex-row">
      @if ($artwork)
        <img src="{{ esc_url($artwork) }}" alt="" class="h-28 w-28 shrink-0 rounded object-cover">
      @endif
      <div>
        <h1 class="font-display text-3xl font-bold">{{ single_term_title('', false) }}</h1>
        @if ($term->description)<p class="mt-1 text-ink-700">{{ $term->description }}</p>@endif
        <div class="mt-3 flex flex-wrap gap-2 text-sm">
          @foreach (App\Newsroom\show_subscribe_links($tid) as $link)
            <a href="{{ esc_url($link['url']) }}" rel="noopener" class="rounded bg-ink-900 px-3 py-1.5 text-white hover:bg-brand-700">{{ $link['label'] }}</a>
          @endforeach
          <a href="{{ esc_url(home_url('/feed/podcast?podcast_show=' . $term->slug)) }}" class="rounded border border-neutral-300 px-3 py-1.5">{{ __('RSS', 'foldednews') }}</a>
        </div>
      </div>
    </header>

    <div class="mt-8 space-y-6">
      @while(have_posts()) @php(the_post())
        <x-podcast-card :episode="get_the_ID()" />
      @endwhile
    </div>

    {!! get_the_posts_navigation() !!}
  </div>
@endsection
