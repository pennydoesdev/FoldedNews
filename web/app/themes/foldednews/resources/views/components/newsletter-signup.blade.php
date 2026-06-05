@props(['title' => null, 'list' => 'daily-briefing'])

@php($action = apply_filters('foldednews/newsletter/action', '#'))

<section class="my-8 rounded-lg bg-ink-900 p-6 text-white" aria-labelledby="newsletter-heading">
  <h2 id="newsletter-heading" class="font-display text-xl font-bold">{{ $title ?? __('The Daily Briefing', 'foldednews') }}</h2>
  <p class="mt-1 text-sm text-neutral-300">{{ __('Top stories in your inbox every morning.', 'foldednews') }}</p>

  <form class="mt-4 flex flex-col gap-2 sm:flex-row" action="{{ esc_url($action) }}" method="post">
    @php(wp_nonce_field('foldednews_newsletter', '_fn_nl_nonce'))
    <input type="hidden" name="list" value="{{ esc_attr($list) }}">
    <label class="sr-only" for="newsletter-email">{{ __('Email address', 'foldednews') }}</label>
    <input id="newsletter-email" name="email" type="email" required autocomplete="email"
           class="flex-1 rounded px-3 py-2 text-ink-900" placeholder="you@example.com">
    <button type="submit" class="rounded bg-brand-600 px-4 py-2 font-semibold hover:bg-brand-700">
      {{ __('Subscribe', 'foldednews') }}
    </button>
  </form>

  <p class="mt-2 text-xs text-neutral-400">{{ __('Backend wiring lands in Stage 12 (newsletters).', 'foldednews') }}</p>
</section>
