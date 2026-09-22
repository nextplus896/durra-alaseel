<!-- fontawesome css link -->
<link rel="stylesheet" href="{{ asset('frontend/css/fontawesome-all.css') }}">
<!-- bootstrap css link -->
<link rel="stylesheet" href="{{ asset('frontend/css/bootstrap.css') }}">
<!-- favicon -->
<link rel="shortcut icon" href="{{ get_fav_vendor($basic_settings) }}" type="image/x-icon">
<!-- swipper css link -->
<link rel="stylesheet" href="{{ asset('frontend/css/swiper.css') }}">
<!-- lightcase css links -->
<link rel="stylesheet" href="{{ asset('frontend/css/lightcase.css') }}">
<!-- AOS css link -->
<link rel="stylesheet" href="{{ asset('frontend/css/aos.css') }}">
<!-- odometer css link -->
<link rel="stylesheet" href="{{ asset('frontend/css/odometer.css') }}">
<!-- animate.css -->
<link rel="stylesheet" href="{{ asset('frontend/css/animate.css') }}">
<!-- line-awesome-icon css -->
<link rel="stylesheet" href="{{ asset('frontend/css/line-awesome.css') }}">
<!-- nice-select -->
<link rel="stylesheet" href="{{ asset('frontend/css/nice-select.css') }}">
<!-- select2 css -->
<link rel="stylesheet" href="{{ asset('frontend/css/select2.css') }}">
<!-- main style css link -->
<link rel="stylesheet" href="{{ asset('frontend/css/style.css') }}">
<!-- Typography Normalization -->
<link rel="stylesheet" href="{{ asset('frontend/css/typography.css') }}">
<!-- Popup  -->
<link rel="stylesheet" href="{{ asset('backend/library/popup/magnific-popup.css') }}">
<!-- dropzone css -->
<link rel="stylesheet" href="{{ asset('backend/library/dropzone/dropzone.min.css') }}" type="text/css">
<style>.dropzone{min-height:160px;border:2px dashed #bbb;border-radius:8px;background:#fafafa;padding:15px}.dropzone .dz-message{margin:1.5em 0;color:#888}.dropzone-wrapper{margin-bottom:5px}</style>


<style>
    :root {
        --primary-color: {{ $basic_settings->vendor_base_color }};
        --secondary-color: {{ $basic_settings->vendor_secondary_color }};
    }
</style>
