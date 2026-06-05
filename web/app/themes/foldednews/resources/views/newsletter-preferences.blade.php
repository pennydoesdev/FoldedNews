@extends('layouts.app')

@section('content')
  <div class="mx-auto max-w-xl px-4 py-8">
    <h1 class="font-display text-3xl font-bold">{{ __('Email preferences', 'foldednews') }}</h1>

    @unless ($valid)
      <p class="mt-4 text-ink-700">{{ __('This link is invalid or has expired.', 'foldednews') }}</p>
    @else
      <p class="mt-2 text-ink-600">{{ $email }}</p>

      <form class="mt-6" data-fn-prefs
            data-rest="{{ esc_url(rest_url('foldednews/v1/newsletter/preferences')) }}"
            data-email="{{ esc_attr($email) }}" data-token="{{ esc_attr($token) }}">
        <fieldset class="space-y-2">
          <legend class="font-semibold">{{ __('Subscribed lists', 'foldednews') }}</legend>
          @forelse ($lists as $list)
            <label class="flex items-center gap-2">
              <input type="checkbox" name="lists" value="{{ $list->slug }}" @checked(in_array($list->slug, $subscribed, true))>
              {{ $list->name }}
            </label>
          @empty
            <p class="text-sm text-ink-500">{{ __('No lists yet.', 'foldednews') }}</p>
          @endforelse
        </fieldset>

        <div class="mt-4 flex flex-wrap gap-3">
          <button type="submit" class="rounded bg-brand-600 px-4 py-2 font-semibold text-white hover:bg-brand-700">{{ __('Update preferences', 'foldednews') }}</button>
          <button type="button" data-action="unsubscribe" class="rounded border border-neutral-300 px-4 py-2 font-semibold hover:bg-neutral-50">{{ __('Unsubscribe from all', 'foldednews') }}</button>
        </div>
        <p class="mt-3 text-sm" data-status aria-live="polite"></p>
      </form>
    @endunless
  </div>
@endsection
