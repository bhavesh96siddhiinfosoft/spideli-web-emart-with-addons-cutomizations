{{--
    Banners at the bottom of a service's home page (admin "Banner position"):
      bottom        - full width (point 86)
      bottom_up     - full width, the upper slot          (5 Nov)
      bottom_left   - left half  } side by side           (5 Nov)
      bottom_right  - right half }                        (5 Nov)
      bottom_down   - full width, the lowest slot         (5 Nov)
    Each slot is a slider when it has several banners and is hidden when it has
    none. The home page calls homeBottomBannerCollect(bannerData, type, id) for
    each banner it shows (after its own region / zone checks); the slots are drawn
    once the page has gone through its list. A banner with no link opens nothing.
--}}
<div class="container home-bottom-banners mt-4 mb-4" id="home_bottom_banners" style="display:none">
    <div class="home-bottom-slot mb-3" id="bottom_banner_slot" style="display:none"><div id="bottom_banner"></div></div>
    <div class="home-bottom-slot mb-3" id="bottom_up_banner_slot" style="display:none"><div id="bottom_up_banner"></div></div>
    <div class="row">
        <div class="col-md-6 home-bottom-slot mb-3" id="bottom_left_banner_slot" style="display:none"><div id="bottom_left_banner"></div></div>
        <div class="col-md-6 home-bottom-slot mb-3" id="bottom_right_banner_slot" style="display:none"><div id="bottom_right_banner"></div></div>
    </div>
    <div class="home-bottom-slot mb-3" id="bottom_down_banner_slot" style="display:none"><div id="bottom_down_banner"></div></div>
</div>
<style>
    /* Kept to a banner's height, whatever the picture's size: full-width slots
       up to 320px tall, the two half slots up to 260px. */
    .home-bottom-banners .banner-img img { width: 100%; height: 320px; border-radius: 10px; object-fit: cover; display: block; }
    .home-bottom-banners .col-md-6 .banner-img img { height: 260px; }
    @media (max-width: 767px) {
        .home-bottom-banners .banner-img img, .home-bottom-banners .col-md-6 .banner-img img { height: 180px; }
    }
    .home-bottom-banners .slick-dots { position: static; }
</style>
<script>
    var HOME_BOTTOM_SLOTS = ['bottom', 'bottom_up', 'bottom_left', 'bottom_right', 'bottom_down'];
    var homeBottomBanners = [];
    var homeBottomBannerTimer = null;

    /* bannerData: the banner_items document; redirectType / redirectId: the link the
     * page has already checked (undefined = take the banner's own). */
    function homeBottomBannerCollect(bannerData, redirectType, redirectId) {
        if (!bannerData || HOME_BOTTOM_SLOTS.indexOf(bannerData.position) < 0) {
            return;
        }
        homeBottomBanners.push({
            position: bannerData.position,
            photo: bannerData.web_banner || '',
            redirect_type: redirectType !== undefined ? (redirectType || '') : (bannerData.redirect_type || ''),
            redirect_id: redirectId !== undefined ? (redirectId || '') : (bannerData.redirect_id || '')
        });
        clearTimeout(homeBottomBannerTimer);
        homeBottomBannerTimer = setTimeout(renderHomeBottomBanners, 50);
    }

    function homeBottomBannerLink(banner) {
        if (banner.redirect_type === 'store' && banner.redirect_id) {
            return "{{ route('vendor', ':id') }}".replace(':id', encodeURIComponent(banner.redirect_id));
        }
        if (banner.redirect_type === 'product' && banner.redirect_id) {
            return "{{ route('productdetail', ':id') }}".replace(':id', encodeURIComponent(banner.redirect_id));
        }
        if (banner.redirect_type === 'external_link' && /^https?:\/\//i.test(banner.redirect_id || '')) {
            return banner.redirect_id;
        }
        return '';   // no link: the picture only
    }

    function renderHomeBottomBanners() {
        var any = false;
        HOME_BOTTOM_SLOTS.forEach(function (slot) {
            var list = homeBottomBanners.filter(function (b) { return b.position === slot; });
            var $box = $('#' + slot + '_banner');
            if (!$box.length) {
                return;
            }
            if ($box.hasClass('slick-initialized')) {
                $box.slick('unslick');
            }
            if (!list.length) {
                $('#' + slot + '_banner_slot').hide();
                $box.empty();
                return;
            }
            any = true;
            var placeholder = typeof placeholderImageSrc !== 'undefined' ? placeholderImageSrc : '';
            var html = list.map(function (b) {
                var link = homeBottomBannerLink(b);
                var img = $('<img>').attr('src', b.photo || placeholder).attr('alt', '')
                    .attr('onerror', "this.onerror=null;this.src='" + placeholder + "'")[0].outerHTML;
                var inner = link
                    ? $('<a></a>').attr('href', link).attr('target', b.redirect_type === 'external_link' ? '_blank' : null).html(img)[0].outerHTML
                    : img;
                return '<div class="banner-item"><div class="banner-img">' + inner + '</div></div>';
            }).join('');
            $box.html(html);
            $('#' + slot + '_banner_slot').show();
            if (list.length > 1 && $.fn.slick) {
                $box.slick({slidesToShow: 1, dots: true, arrows: true, autoplay: true, autoplaySpeed: 4000});
            }
        });
        $('#home_bottom_banners').toggle(any);
    }
</script>
