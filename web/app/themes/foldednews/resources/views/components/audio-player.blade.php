@props(['src', 'preload' => 'none'])

@if ($src)
  <audio {{ $attributes->merge(['class' => 'fn-audio mt-2 w-full']) }} controls preload="{{ $preload }}">
    <source src="{{ esc_url($src) }}" type="audio/mpeg">
    {{ __('Your browser does not support the audio element.', 'foldednews') }}
  </audio>
@endif
