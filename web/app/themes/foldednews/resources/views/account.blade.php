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
      <h2 class="font-display text-xl font-bold">{{ __('Email preferences', 'foldednews') }}</h2>
      <p class="mt-1 text-sm text-ink-500">{{ __('Newsletters, topic alerts and bookmarks are added in Stages 12 & 15.', 'foldednews') }}</p>
    </section>
  </div>
@endsection
