<% if $HasSlides %>
<% require css('antlion/swiper-slider:client/css/swiperhero.css') %>
<div class="hero swiper"
    id="slider-$ID"
    data-swiper='{$getSwiperOptionsJSON.RAW}'
>
  <div class="swiper-wrapper">
    <% loop $SlidesActive %>
      <div class="swiper-slide swiper-{$Theme}">
        <% if $CoverLink %>
          <a class="cover-link" href="$CoverLink.URL" aria-label="$CoverLink.Title.XML"></a>
        <% end_if %>

        <% if $IsVideo %>
          <div class="swiper-media">
            <video
              class="swiper-video swiper-h-{$Up.Height}"
              autoplay
              muted
              playsinline
              loop
              preload="auto"
              <% if $PosterURL %>poster="$PosterURL"<% end_if %>
              <% if $VideoStart %>data-start="$VideoStart"<% end_if %>
              <% if $VideoEnd %>data-end="$VideoEnd"<% end_if %>
            >
              <% if $VideoWebM %><source src="$VideoWebM.URL" type="video/webm"><% end_if %>
              <% if $VideoMP4 %><source src="$VideoMP4.URL" type="video/mp4"><% end_if %>
              Your browser does not support HTML5 video.
            </video>
          </div>
        <% else %>
          <% if $Image %>
            <% if $Up.Lazy %>
              <picture>
                <source media="(max-width: 639px)" data-srcset="$MobileImageURL">
                <img
                  class="swiper-lazy swiper-h-{$Up.Height}"
                  data-src="$DesktopImageURL"
                  alt="$Image.Title.ATT"
                  width="$Up.DesktopWidth" height="$Up.DesktopHeight"
                  style="width:100%;height:100%;object-fit:cover;object-position:center;">
              </picture>
              <div class="swiper-lazy-preloader"></div>
            <% else %>
              <picture>
                <source media="(max-width: 639px)" srcset="$MobileImageURL">
                <img
                  class="swiper-h-{$Up.Height}"
                  src="$DesktopImageURL"
                  alt="$Image.Title.ATT"
                  width="$Up.DesktopWidth" height="$Up.DesktopHeight"
                  style="width:100%;height:100%;object-fit:cover;object-position:center;">
              </picture>
            <% end_if %>
          <% end_if %>
        <% end_if %>

        <% if $OverlayOpacity %>
          <div class="swiper-overlay" style="--overlay: {$OverlayOpacityCss};"></div>
        <% else %>
          <div class="swiper-overlay"></div>
        <% end_if %>

        <div class="slide-content">
          <div class="grid-container" style="width: 100%;">
            <div class="grid-x align-middle <% if $Align == 'center' %>align-center<% else_if $Align == 'right' %>align-right<% else %>align-left<% end_if %>">
              <div class="cell large-shrink small-12">
                <% if $Headline %><h2>$Headline</h2><% end_if %>
                <% if $Description %><p>$Description</p><% end_if %>
                $Content
                <% if $Links.Exists %>
                  <div class="button-group gap-6 large <% if $Align == 'center' %>align-center<% else_if $Align == 'right' %>align-right<% else %>align-left<% end_if %>">
                    <% loop $Links %>
                      <a class="button $CssClass" href="$URL" <% if $OpenInNew %>target="_blank" rel="noopener noreferrer"<% end_if %>>$Title.XML</a>
                    <% end_loop %>
                  </div>
                <% end_if %>
              </div>
            </div>
          </div>
        </div>
      </div>
    <% end_loop %>
  </div>

  <% if $Pagination %><div class="swiper-pagination"></div><% end_if %>
  <% if $Navigation %>
    <div class="swiper-button-container">
      <div class="swiper-button-prev"></div>
      <div class="swiper-button-next"></div>
    </div>
  <% end_if %>
  <% if $Autoplay && $AutoplayProgress %>
    <div class="autoplay-progress">
      <svg viewBox="0 0 48 48">
        <circle cx="24" cy="24" r="20"></circle>
      </svg>
      <span></span>
    </div>
  <% end_if %>
  <% if $Scrollbar %><div class="swiper-scrollbar"></div><% end_if %>
</div>
<% end_if %>
