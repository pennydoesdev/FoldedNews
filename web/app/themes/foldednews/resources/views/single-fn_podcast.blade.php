@extends('layouts.app')

@section('content')
  @php(the_post())
  @php($id = get_the_ID())
  @php($shows = get_the_terms($id, 'podcast_show'))
  @php($audio = App\Newsroom\podcast_audio($id))
  @php($duration = App\Newsroom\podcast_duration($id))

  <article class="mx-auto max-w-3xl px-4 py-8">
    @if (is_array($shows) && $shows)
      <a href="{{ esc_url(get_term_link($shows[0])) }}" class="text-xs font-semibold uppercase tracking-wide text-brand-700">{{ $shows[0]->name }}</a>
    @endif

    <h1 class="mt-1 font-display text-3xl font-bold leading-tight">
      @if (App\Newsroom\podcast_is_premium($id))
        <span class="mr-1 rounded bg-amber-300 px-1.5 py-0.5 text-xs font-bold uppercase text-amber-900">{{ __('Premium', 'foldednews') }}</span>
      @endif
      {!! get_the_title() !!}
    </h1>
    <p class="mt-1 text-sm text-ink-500">
      <time datetime="{{ get_post_time('c', true, $id) }}">{{ get_the_date() }}</time>@if ($duration) · {{ $duration }}@endif
    </p>

    @if ($audio)<x-audio-player :src="$audio" preload="metadata" />@endif

    <div class="prose mt-6 max-w-none">
      @php(the_content())
    </div>

    @php($transcript = App\Newsroom\video_transcript($id))
    @if ($transcript)
      <details class="mt-6">
        <summary class="cursor-pointer font-semibold">{{ __('Transcript', 'foldednews') }}</summary>
        <div class="prose mt-2 max-w-none">{!! wpautop(esc_html($transcript)) !!}</div>
      </details>
    @endif
  </article>
@endsection
