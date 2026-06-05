<!doctype html>
<html @php(language_attributes())>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php(do_action('get_header'))
    @php(wp_head())

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body @php(body_class())>
    @php(wp_body_open())

    <a class="sr-only focus:not-sr-only" href="#main">
      {{ __('Skip to content', 'foldednews') }}
    </a>

    <div id="app" class="min-h-screen flex flex-col">
      @include('sections.header')

      <div class="mx-auto w-full max-w-5xl px-4">
        <x-ad-slot placement="leaderboard" />
      </div>

      <main id="main" class="main flex-1" role="main">
        @yield('content')
      </main>

      @include('sections.footer')
    </div>

    @php(do_action('get_footer'))
    @php(wp_footer())
  </body>
</html>
