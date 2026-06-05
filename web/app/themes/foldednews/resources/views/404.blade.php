@extends('layouts.app')

@section('content')
  <div class="mx-auto max-w-3xl px-4 py-16 text-center">
    <h1 class="font-display text-4xl font-bold text-ink-900">{{ __('Page not found', 'foldednews') }}</h1>
    <p class="mt-4 text-ink-700">{{ __('The page you’re looking for doesn’t exist or has moved.', 'foldednews') }}</p>
    <div class="mt-6 flex justify-center">{!! get_search_form(false) !!}</div>
    <a href="{{ home_url('/') }}" class="mt-6 inline-block text-brand-700 hover:underline">{{ __('Back to the homepage', 'foldednews') }}</a>
  </div>
@endsection
