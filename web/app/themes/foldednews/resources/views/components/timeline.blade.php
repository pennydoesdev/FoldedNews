@props(['groups' => [], 'layout' => 'vertical'])

<div class="fn-timeline fn-timeline--{{ $layout }}">
  @foreach ($groups as $year => $events)
    <section class="fn-timeline-group mt-8">
      <h2 class="font-display text-2xl font-bold text-ink-900">{{ $year }}</h2>
      <ol class="{{ $layout === 'horizontal' ? 'mt-4 flex gap-6 overflow-x-auto pb-4' : 'mt-4 space-y-6 border-l-2 border-neutral-200 pl-4' }}">
        @foreach ($events as $event)
          @php($eid = $event->ID)
          @php($date = get_post_meta($eid, '_fn_event_date', true))
          @php($source = get_post_meta($eid, '_fn_source', true))
          <li class="fn-timeline-event {{ $layout === 'horizontal' ? 'w-64 shrink-0' : '' }}">
            <time class="text-xs font-semibold uppercase tracking-wide text-brand-700">{{ $date ?: get_the_date('', $eid) }}</time>
            <h3 class="mt-1 font-display text-lg font-semibold">{!! get_the_title($eid) !!}</h3>
            @if (has_post_thumbnail($eid))
              <div class="mt-2">{!! get_the_post_thumbnail($eid, 'medium', ['class' => 'rounded w-full h-auto', 'loading' => 'lazy']) !!}</div>
            @endif
            <div class="prose prose-sm mt-2 max-w-none">{!! apply_filters('the_content', $event->post_content) !!}</div>
            @if (is_string($source) && $source)
              <p class="mt-1 text-xs"><a href="{{ esc_url($source) }}" rel="nofollow noopener" class="underline">{{ __('Source', 'foldednews') }}</a></p>
            @endif
          </li>
        @endforeach
      </ol>
    </section>
  @endforeach
</div>
