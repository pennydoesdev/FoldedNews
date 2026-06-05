@props(['title' => null, 'list' => 'daily-briefing'])

@php($uid = wp_unique_id('newsletter-'))

<section class="my-8 rounded-lg bg-ink-900 p-6 text-white" aria-labelledby="{{ $uid }}-heading">
  <h2 id="{{ $uid }}-heading" class="font-display text-xl font-bold">{{ $title ?? __('The Daily Briefing', 'foldednews') }}</h2>
  <p class="mt-1 text-sm text-neutral-300">{{ __('Top stories in your inbox every morning.', 'foldednews') }}</p>

  <form class="mt-4 flex flex-col gap-2 sm:flex-row" method="post"
        data-fn-newsletter
        data-rest="{{ esc_url(rest_url('foldednews/v1/newsletter/subscribe')) }}"
        data-nonce="{{ wp_create_nonce('foldednews_newsletter') }}">
    <input type="hidden" name="list" value="{{ esc_attr($list) }}">
    <label class="sr-only" for="{{ $uid }}-email">{{ __('Email address', 'foldednews') }}</label>
    <input id="{{ $uid }}-email" name="email" type="email" required autocomplete="email"
           class="flex-1 rounded px-3 py-2 text-ink-900" placeholder="you@example.com">
    <button type="submit" class="rounded bg-brand-600 px-4 py-2 font-semibold hover:bg-brand-700">
      {{ __('Subscribe', 'foldednews') }}
    </button>
  </form>
  <p class="mt-2 text-xs text-neutral-400" data-status aria-live="polite"></p>
</section>
