@props(['post'])

@php($id = is_int($post) ? $post : $post->ID)
@php($items = App\Newsroom\source_notes($id))

@if (! empty($items))
  <section class="my-6 rounded border border-neutral-200 bg-white p-4" aria-label="{{ __('Source notes', 'foldednews') }}">
    <h2 class="font-display text-sm font-bold uppercase tracking-wide text-ink-700">{{ __('Source notes', 'foldednews') }}</h2>
    <ul class="mt-2 space-y-2 text-sm text-ink-700">
      @foreach ($items as $sid)
        <li>
          <strong>{!! get_the_title($sid) !!}</strong>
          <div>{!! wp_kses_post(get_the_content(null, false, $sid)) !!}</div>
        </li>
      @endforeach
    </ul>
  </section>
@endif
