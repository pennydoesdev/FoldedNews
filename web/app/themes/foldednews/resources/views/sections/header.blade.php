<header class="banner border-b border-neutral-200" role="banner">
  <div class="mx-auto max-w-5xl px-4 py-4 flex items-center justify-between gap-4">
    <a class="brand font-display text-2xl font-bold text-brand-700" href="{{ home_url('/') }}" rel="home">
      {{ get_bloginfo('name', 'display') }}
    </a>

    <nav class="nav-primary" aria-label="{{ __('Primary Navigation', 'foldednews') }}">
      @if (has_nav_menu('primary_navigation'))
        {!! wp_nav_menu([
          'theme_location' => 'primary_navigation',
          'menu_class' => 'flex flex-wrap gap-x-5 gap-y-2 text-sm font-medium',
          'container' => false,
          'echo' => false,
        ]) !!}
      @endif
    </nav>
  </div>
</header>
