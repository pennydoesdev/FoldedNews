@props(['video'])

@php($p = is_int($video) ? get_post($video) : $video)
@php($id = $p->ID)
@php($src = App\Newsroom\video_src($p))
@php($aspect = App\Newsroom\video_aspect($p))
@php($poster = App\Newsroom\video_poster($p))
@php($captions = App\Newsroom\video_captions($p))
@php($chapters = App\Newsroom\video_chapters($p))

@if ($src)
  <div class="fn-video {{ App\Newsroom\aspect_class($aspect) }}">
    <video
      class="video-js vjs-big-play-centered"
      controls
      preload="none"
      playsinline
      @if (App\Newsroom\video_is_live($p)) data-live="1" @endif
      @if ($poster) poster="{{ esc_url($poster) }}" @endif
      data-fn-video>
      <source src="{{ esc_url($src) }}" type="{{ App\Newsroom\video_mime($src) }}">
      @foreach ($captions as $cap)
        <track kind="captions" src="{{ esc_url($cap['src']) }}" srclang="{{ esc_attr($cap['lang']) }}" label="{{ esc_attr($cap['label']) }}">
      @endforeach
      @if ($chapters)
        <track kind="chapters" src="{{ esc_url($chapters) }}">
      @endif
      <p class="vjs-no-js">{{ __('To view this video please enable JavaScript, and consider upgrading to a browser that supports HTML5 video.', 'foldednews') }}</p>
    </video>
  </div>
@endif
