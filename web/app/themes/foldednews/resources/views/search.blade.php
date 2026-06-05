@extends('layouts.app')

@section('content')
  <div class="mx-auto max-w-3xl px-4 py-8">
    <h1 class="font-display text-2xl font-bold text-ink-900">{!! App\title() !!}</h1>
    <div class="mt-4">{!! get_search_form(false) !!}</div>

    @if (! have_posts())
      <p class="mt-6 text-ink-700">{{ __('No results found.', 'foldednews') }}</p>
    @endif

    @while(have_posts()) @php(the_post())
      @include('partials.content')
    @endwhile

    {!! get_the_posts_navigation() !!}
  </div>
@endsection
