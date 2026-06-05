@props(['post'])

@php($id = is_int($post) ? $post : $post->ID)
@php($items = App\Newsroom\corrections($id))

@if (! empty($items))
  <section class="my-6 rounded border-l-4 border-brand-600 bg-neutral-50 p-4" aria-label="{{ __('Corrections', 'foldednews') }}">
    <h2 class="font-display text-sm font-bold uppercase tracking-wide text-brand-700">{{ __('Corrections', 'foldednews') }}</h2>
    <ul class="mt-2 space-y-2 text-sm text-ink-700">
      @foreach ($items as $cid)
        <li>
          <time class="text-ink-500" datetime="{{ App\Newsroom\published_iso($cid) }}">{{ get_the_date('', $cid) }}</time>
          <div>{!! wp_kses_post(get_the_content(null, false, $cid)) !!}</div>
        </li>
      @endforeach
    </ul>
  </section>
@endif
