@php
    $banned_slug = Str::slug(site_section_const()::BANNER_SECTION);
    $banner = App\Models\Admin\SiteSections::getData($banned_slug)->first();
    $default = get_default_language_code() ?? 'en';
    $default_lng = 'en';
@endphp
<section class="banner-section  bg_img" data-background="{{ asset('frontend/images/banner/banner-bg.webp') }}">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lx-6 col-lg-6">
                <div class="banner-content">
                    <h1 class="title">
                        <span class="text--base">{{ $site_name }} - </span>
                        {{ $banner->value->language->$default->heading ?? ''  }}
                    </h1>
                    <p>{{ $banner->value->language->$default->sub_heading ?? '' }}</p>
                    <div class="banner-btn mb-10-none">
                        <a href="{{ url($banner->value->button_link_one) }}" class="btn--base mb-10">{{ $banner->value->language->$default->button_name_one ?? "" }}</a>
                        <a href="{{ url($banner->value->button_link_two) }}" class="btn--base mb-10">{{ $banner->value->language->$default->button_name_two ?? "" }}</a>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6">
                <div class="banner-img">
                    <img src="{{ asset('frontend/images/banner/banner-img.webp') }}" alt="img">
                    <div class="banner-inner-img">
                        <img id="myImg" alt="img">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@push('script')
    <script>
        const img = document.getElementById('myImg');
        let currentImageIndex = 1; // Start with the 4th image
        const totalImages = 19; // Adjust the total number of images
        const animationInterval = 200; // Adjust the interval between images in milliseconds
        let isForward = true; // Flag to indicate the direction of animation

        function changeImage() {
            if (isForward) {
                currentImageIndex = (currentImageIndex % totalImages) + 1;
            } else {
                currentImageIndex = (currentImageIndex === 1) ? totalImages : currentImageIndex - 1;
            }
            img.src = `{{ asset('frontend/images/car') }}/car-${currentImageIndex}.webp`;
        }

        function toggleDirection() {
            isForward = !isForward;
        }

        function startAnimation() {
            setInterval(function() {
                changeImage();
                if (currentImageIndex === 1 || currentImageIndex === totalImages) {
                    toggleDirection();
                }
            }, animationInterval);
        }

        // Start the animation
        startAnimation();
    </script>
@endpush
