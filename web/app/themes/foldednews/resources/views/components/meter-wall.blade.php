@props(['post'])

@php($id = is_int($post) ? $post : $post->ID)

<div class="fn-meter-wall my-8 rounded-lg border border-neutral-200 bg-neutral-50 p-6 text-center"
     data-fn-meter
     data-post="{{ $id }}"
     data-rest="{{ esc_url(rest_url('foldednews/v1/meter/unlock')) }}"
     data-nonce="{{ wp_create_nonce('fn_meter') }}">
  <h2 class="font-display text-2xl font-bold">{{ __('You’ve reached your free article limit', 'foldednews') }}</h2>
  <p class="mt-2 text-ink-700">{{ __('Watch a short ad to keep reading this article, or become a member for unlimited access.', 'foldednews') }}</p>

  <div class="mt-4 flex flex-wrap justify-center gap-3">
    <button type="button" data-action="ad" class="rounded bg-ink-900 px-4 py-2 font-semibold text-white hover:bg-black">{{ __('Watch ad to unlock', 'foldednews') }}</button>
    <a href="{{ home_url('/account/') }}" class="rounded bg-brand-600 px-4 py-2 font-semibold text-white hover:bg-brand-700">{{ __('Become a member', 'foldednews') }}</a>
    @if (! is_user_logged_in())
      <a href="{{ esc_url(wp_login_url(get_permalink($id))) }}" class="rounded border border-neutral-300 px-4 py-2 font-semibold hover:bg-white">{{ __('Log in', 'foldednews') }}</a>
    @endif
  </div>
</div>
