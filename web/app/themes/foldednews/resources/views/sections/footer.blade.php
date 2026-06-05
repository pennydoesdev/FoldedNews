<footer class="content-info border-t border-neutral-200 mt-12" role="contentinfo">
  <div class="mx-auto max-w-5xl px-4 py-8 text-sm text-neutral-500 flex flex-col gap-4">
    @if (has_nav_menu('footer_navigation'))
      <nav aria-label="{{ __('Footer Navigation', 'foldednews') }}">
        {!! wp_nav_menu([
          'theme_location' => 'footer_navigation',
          'menu_class' => 'flex flex-wrap gap-x-5 gap-y-2',
          'container' => false,
          'echo' => false,
        ]) !!}
      </nav>
    @endif

    <p>&copy; {{ date('Y') }} {{ get_bloginfo('name', 'display') }}. {{ __('All rights reserved.', 'foldednews') }}</p>
  </div>
</footer>
