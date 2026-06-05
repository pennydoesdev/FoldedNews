@props(['person'])

@php($id = is_int($person) ? $person : $person->ID)
@php($role = App\Newsroom\person_role($id))
@php($social = App\Newsroom\person_social($id))

<div {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
  @if (has_post_thumbnail($id))
    {!! get_the_post_thumbnail($id, 'thumbnail', ['class' => 'h-12 w-12 rounded-full object-cover', 'loading' => 'lazy', 'alt' => get_the_title($id)]) !!}
  @endif
  <div class="text-sm">
    <a href="{{ get_permalink($id) }}" class="font-semibold text-ink-900 hover:text-brand-700">{!! get_the_title($id) !!}</a>
    @if ($role)
      <div class="text-ink-500">{{ $role }}</div>
    @endif
    @if (! empty($social))
      <ul class="mt-1 flex gap-2" aria-label="{{ get_the_title($id) }} {{ __('social links', 'foldednews') }}">
        @foreach ($social as $url)
          <li><a href="{{ esc_url($url) }}" rel="me noopener" class="text-ink-500 hover:text-brand-700">{{ parse_url($url, PHP_URL_HOST) ?: __('Link', 'foldednews') }}</a></li>
        @endforeach
      </ul>
    @endif
  </div>
</div>
