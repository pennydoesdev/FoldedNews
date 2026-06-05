@props(['videos' => []])

@if (! empty($videos))
  <section class="fn-video-strip my-8" aria-label="{{ __('Vertical videos', 'foldednews') }}">
    <h2 class="mb-3 font-display text-lg font-bold">{{ __('Shorts', 'foldednews') }}</h2>
    <div class="flex gap-4 overflow-x-auto pb-2">
      @foreach ($videos as $v)
        <div class="w-44 shrink-0">
          <x-video-card :video="$v" aspect="9:16" />
        </div>
      @endforeach
    </div>
  </section>
@endif
