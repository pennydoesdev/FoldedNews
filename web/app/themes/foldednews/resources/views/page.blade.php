@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())
    <article class="mx-auto max-w-3xl px-4 py-8">
      <h1 class="font-display text-3xl font-bold leading-tight md:text-4xl">{!! get_the_title() !!}</h1>
      <div class="prose mt-6 max-w-none">
        @php(the_content())
      </div>
    </article>
  @endwhile
@endsection
