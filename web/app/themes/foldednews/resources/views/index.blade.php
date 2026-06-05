@extends('layouts.app')

@section('content')
  @include('partials.page-header')

  <div class="mx-auto max-w-5xl px-4 py-8">
    @if (! have_posts())
      <p class="text-neutral-600">{!! __('Sorry, no results were found.', 'foldednews') !!}</p>
      {!! get_search_form(false) !!}
    @endif

    @while(have_posts()) @php(the_post())
      @include('partials.content')
    @endwhile

    {!! get_the_posts_navigation() !!}
  </div>
@endsection
