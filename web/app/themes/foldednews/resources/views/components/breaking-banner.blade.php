@props(['posts' => []])

@if (! empty($posts))
  <aside class="bg-breaking text-white" role="region" aria-label="{{ __('Breaking news', 'foldednews') }}">
    <div class="mx-auto max-w-5xl px-4 py-2 flex items-center gap-3 text-sm">
      <span class="font-display font-bold uppercase tracking-wide shrink-0">{{ __('Breaking', 'foldednews') }}</span>
      <ul class="flex flex-wrap gap-x-6 gap-y-1">
        @foreach ($posts as $post)
          @php($id = is_int($post) ? $post : $post->ID)
          <li><a href="{{ get_permalink($id) }}" class="underline-offset-2 hover:underline">{!! get_the_title($id) !!}</a></li>
        @endforeach
      </ul>
    </div>
  </aside>
@endif
