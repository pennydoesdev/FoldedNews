<article @php(post_class('py-6 border-b border-neutral-100')) aria-labelledby="post-{{ get_the_ID() }}-title">
  <h2 id="post-{{ get_the_ID() }}-title" class="font-display text-2xl font-semibold">
    <a href="{{ get_permalink() }}" class="text-ink-900 hover:text-brand-700">{!! get_the_title() !!}</a>
  </h2>

  <p class="mt-1 text-sm text-neutral-500">
    <time datetime="{{ get_post_time('c', true) }}">{{ get_the_date() }}</time>
  </p>

  <div class="mt-3 prose max-w-none">
    @php(the_excerpt())
  </div>
</article>
