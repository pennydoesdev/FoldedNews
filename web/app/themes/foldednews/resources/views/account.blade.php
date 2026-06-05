@extends('layouts.app')

@section('content')
  <div class="mx-auto max-w-2xl px-4 py-8">
    <h1 class="font-display text-3xl font-bold">{{ __('Your account', 'foldednews') }}</h1>
    <p class="mt-2 text-ink-700">{{ $user->display_name }} · {{ $user->user_email }}</p>

    @if (($_GET['checkout'] ?? '') === 'success')
      <p class="mt-4 rounded bg-green-100 px-3 py-2 text-sm text-green-800">{{ __('Thanks for subscribing!', 'foldednews') }}</p>
    @endif

    <section class="mt-6 rounded border border-neutral-200 p-4" data-fn-billing
             data-rest="{{ esc_url(rest_url('foldednews/v1/billing')) }}"
             data-nonce="{{ wp_create_nonce('wp_rest') }}">
      <h2 class="font-display text-xl font-bold">{{ __('Membership', 'foldednews') }}</h2>
      <p class="mt-1">{{ __('Status', 'foldednews') }}:
        <strong>{{ $isMember ? __('Active member', 'foldednews') : ($status ?: __('Free', 'foldednews')) }}</strong>
      </p>
      <div class="mt-4 flex flex-wrap gap-3">
        @unless ($isMember)
          <button type="button" data-action="checkout" class="rounded bg-brand-600 px-4 py-2 font-semibold text-white hover:bg-brand-700">{{ __('Subscribe', 'foldednews') }}</button>
        @endunless
        @if ($hasCustomer)
          <button type="button" data-action="portal" class="rounded border border-neutral-300 px-4 py-2 font-semibold hover:bg-neutral-50">{{ __('Manage billing', 'foldednews') }}</button>
        @endif
      </div>
      <p class="mt-2 text-xs text-ink-500">{{ __('Billing is handled securely by Stripe.', 'foldednews') }}</p>
    </section>

    <section class="mt-6 rounded border border-neutral-200 p-4">
      <h2 class="font-display text-xl font-bold">{{ __('Saved articles', 'foldednews') }}</h2>

      @if (empty($bookmarks))
        <p class="mt-1 text-sm text-ink-500">{{ __('No saved articles yet.', 'foldednews') }}</p>
      @else
        <div class="mt-3 flex flex-wrap gap-3">
          <label class="sr-only" for="saved-search">{{ __('Search saved', 'foldednews') }}</label>
          <input id="saved-search" type="search" data-fn-saved-search placeholder="{{ __('Search saved…', 'foldednews') }}" class="flex-1 rounded border border-neutral-300 px-3 py-1.5 text-sm">
          <label class="sr-only" for="saved-sort">{{ __('Sort', 'foldednews') }}</label>
          <select id="saved-sort" data-fn-saved-sort class="rounded border border-neutral-300 px-2 py-1.5 text-sm">
            <option value="saved">{{ __('Recently saved', 'foldednews') }}</option>
            <option value="published">{{ __('Recently published', 'foldednews') }}</option>
          </select>
        </div>

        <ul class="mt-4 divide-y divide-neutral-100" data-fn-saved-list>
          @foreach ($bookmarks as $b)
            <li class="flex items-center justify-between gap-4 py-3"
                data-saved-item data-title="{{ esc_attr($b['title']) }}" data-saved="{{ $b['saved'] }}" data-published="{{ $b['published'] }}">
              <a href="{{ $b['url'] }}" class="font-medium hover:text-brand-700">{!! $b['title'] !!}</a>
              <x-bookmark-button :post="$b['id']" />
            </li>
          @endforeach
        </ul>
      @endif
    </section>

    <section class="mt-6 rounded border border-neutral-200 p-4">
      <h2 class="font-display text-xl font-bold">{{ __('Email preferences', 'foldednews') }}</h2>
      <p class="mt-1 text-sm text-ink-500">{{ __('Manage newsletters from the link in any email (Stage 12).', 'foldednews') }}</p>
    </section>
  </div>
@endsection
