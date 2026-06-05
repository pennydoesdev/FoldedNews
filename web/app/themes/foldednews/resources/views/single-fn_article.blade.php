@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())
    @php($id = get_the_ID())
    @php($topic = App\Newsroom\primary_topic($id))
    @php($dek = App\Newsroom\dek($id))
    @php($byline = App\Newsroom\byline($id))

    <article class="mx-auto max-w-3xl px-4 py-8">
      @if ($topic)
        <span class="text-xs font-semibold uppercase tracking-wide text-brand-700">{{ $topic }}</span>
      @endif

      <h1 class="mt-1 font-display text-3xl font-bold leading-tight md:text-4xl">{!! get_the_title() !!}</h1>

      @if ($dek)
        <p class="mt-3 text-lg text-ink-700">{{ $dek }}</p>
      @endif

      <div class="mt-4 flex flex-wrap items-center gap-4 border-y border-neutral-200 py-3">
        @foreach ($byline as $person)
          <x-author-card :person="$person" />
        @endforeach
        <x-article-date-line :post="$id" />
      </div>

      @if (has_post_thumbnail())
        <figure class="my-6">
          {!! get_the_post_thumbnail($id, 'large', ['class' => 'w-full h-auto rounded']) !!}
        </figure>
      @endif

      @if (\FoldedNews\Newsroom\Meter\Meter::allowed($id))
        <div class="prose max-w-none">
          @php(the_content())
        </div>

        <x-ad-slot placement="in_article" />

        <x-source-note :post="$id" />
        <x-correction-notice :post="$id" />
      @else
        <div class="prose max-w-none">
          <p>{{ wp_trim_words(get_the_excerpt(), 60) }}</p>
        </div>

        <x-meter-wall :post="$id" />
      @endif
    </article>
  @endwhile
@endsection
