@extends('layouts.app')

@section('content')
  @php(the_post())
  @php($id = get_the_ID())

  <article class="fn-video-single bg-ink-900 text-white">
    <div class="mx-auto max-w-4xl px-4 py-8">
      <x-video-player :video="$id" />

      <h1 class="mt-5 font-display text-3xl font-bold leading-tight">{!! get_the_title() !!}</h1>
      <div class="prose prose-invert mt-4 max-w-none">
        @php(the_content())
      </div>

      @php($transcript = App\Newsroom\video_transcript($id))
      @if ($transcript)
        <details class="mt-6 rounded border border-ink-700 p-4">
          <summary class="cursor-pointer font-semibold">{{ __('Transcript', 'foldednews') }}</summary>
          <div class="prose prose-invert mt-3 max-w-none">{!! wpautop(esc_html($transcript)) !!}</div>
        </details>
      @endif
    </div>
  </article>
@endsection
