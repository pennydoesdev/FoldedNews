@extends('layouts.app')

@section('content')
  <div class="mx-auto max-w-4xl px-4 py-8">
    <div class="flex items-center justify-between">
      <h1 class="font-display text-3xl font-bold">{!! post_type_archive_title('', false) !!}</h1>
      <div class="text-sm text-ink-600">
        <a href="{{ esc_url(add_query_arg('layout', 'vertical')) }}" class="{{ $layout === 'vertical' ? 'font-bold text-ink-900' : '' }}">{{ __('Vertical', 'foldednews') }}</a>
        <span aria-hidden="true">·</span>
        <a href="{{ esc_url(add_query_arg('layout', 'horizontal')) }}" class="{{ $layout === 'horizontal' ? 'font-bold text-ink-900' : '' }}">{{ __('Horizontal', 'foldednews') }}</a>
      </div>
    </div>

    @if (empty($groups))
      <p class="mt-6 text-ink-600">{{ __('No timeline events yet.', 'foldednews') }}</p>
    @else
      <x-timeline :groups="$groups" :layout="$layout" />
    @endif
  </div>
@endsection
