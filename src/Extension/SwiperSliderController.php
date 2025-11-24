<?php
namespace Antlion\SwiperSlider\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;

class SwiperSliderController extends Extension
{
    public function onAfterInit(): void
    {

        $page = $this->owner->data();

        if (!$page || !$page->hasMethod('getSwiperOptionsJSON')) {
            return;
        }

        $id      = (int) $page->ID;
        $options = $page->getSwiperOptionsJSON();

        $js = <<<JS
        (function(){
          function initSlider_$id(){
            var el = document.getElementById('slider-$id');
            if (!el || el.__swiperInit) return;
            el.__swiperInit = true;
            var options = $options;

            var progressWrap = el.querySelector('.autoplay-progress');
            if (options.autoplay && progressWrap) {
              var progressCircle  = progressWrap.querySelector('svg');
              var progressContent = progressWrap.querySelector('span');

              options.on = options.on || {};
              options.on.autoplayTimeLeft = function (swiper, time, progress) {
                if (progressCircle) {
                  progressCircle.style.setProperty('--progress', 1 - progress);
                }
                if (progressContent) {
                  progressContent.textContent = Math.ceil(time / 1000) + 's';
                }
              };
            }

            if (window.Swiper) new Swiper(el, options);
          }

          if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSlider_$id, { once:true });
          } else {
            initSlider_$id();
          }

        })();
        JS;

        Requirements::customScript($js, "swiper-page-init-$id");
    }
}