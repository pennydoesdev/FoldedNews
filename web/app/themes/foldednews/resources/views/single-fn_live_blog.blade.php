@extends('layouts.app')

@section('content')
  @php(the_post())

  <article class="mx-auto max-w-3xl px-4 py-8">
    <h1 class="font-display text-3xl font-bold leading-tight md:text-4xl">{!! get_the_title() !!}</h1>

    @if ($archived)
      <p class="mt-2 inline-block rounded bg-neutral-200 px-2 py-1 text-xs font-semibold uppercase">{{ __('Archived', 'foldednews') }}</p>
    @endif

    @if ($whatWeKnow || $whatWeDontKnow)
      <div class="mt-6 grid gap-4 sm:grid-cols-2">
        @if ($whatWeKnow)
          <section class="rounded border border-neutral-200 p-4">
            <h2 class="font-display text-sm font-bold uppercase tracking-wide">{{ __('What we know', 'foldednews') }}</h2>
            <div class="mt-2 text-sm text-ink-700">{!! wpautop(esc_html($whatWeKnow)) !!}</div>
          </section>
        @endif
        @if ($whatWeDontKnow)
          <section class="rounded border border-neutral-200 p-4">
            <h2 class="font-display text-sm font-bold uppercase tracking-wide">{{ __('What we do not know', 'foldednews') }}</h2>
            <div class="mt-2 text-sm text-ink-700">{!! wpautop(esc_html($whatWeDontKnow)) !!}</div>
          </section>
        @endif
      </div>
    @endif

    @if ($pinned)
      <div class="mt-6">
        <x-live-update :post="$pinned" :pinned="true" />
      </div>
    @endif

    <div class="mt-8 flex items-center justify-between">
      <h2 class="font-display text-xl font-bold">{{ __('Live updates', 'foldednews') }}</h2>
      <div class="text-sm text-ink-600">
        <a href="{{ esc_url(add_query_arg('order', 'DESC')) }}" class="{{ $order === 'DESC' ? 'font-bold text-ink-900' : '' }}">{{ __('Newest', 'foldednews') }}</a>
        <span aria-hidden="true">·</span>
        <a href="{{ esc_url(add_query_arg('order', 'ASC')) }}" class="{{ $order === 'ASC' ? 'font-bold text-ink-900' : '' }}">{{ __('Oldest', 'foldednews') }}</a>
      </div>
    </div>

    <div id="fn-update-stream" class="mt-4 space-y-6"
         data-blog="{{ $blogId }}"
         data-order="{{ $order }}"
         data-rest="{{ esc_url(rest_url('foldednews/v1/live/' . $blogId . '/updates')) }}"
         data-since="{{ $latest }}"
         data-archived="{{ $archived ? '1' : '0' }}">
      @forelse ($updates as $update)
        <x-live-update :post="$update" />
      @empty
        <p class="text-ink-600">{{ __('No updates yet.', 'foldednews') }}</p>
      @endforelse
    </div>
  </article>
@endsection
