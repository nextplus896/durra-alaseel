@extends('admin.layouts.master')

@push('css')
    <style>
        .fileholder {
            min-height: 280px !important;
        }

        .fileholder-files-view-wrp.accept-single-file .fileholder-single-file-view,.fileholder-files-view-wrp.fileholder-perview-single .fileholder-single-file-view{
            height: 246px !important;
        }
    </style>
@endpush

@section('page-title')
    @include('admin.components.page-title',['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("admin.dashboard"),
        ]
    ], 'active' => __($page_title)])
@endsection

@section('content')
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __($page_title) }}</h6>
        </div>
        <div class="card-body">
            <form class="card-form" method="POST" enctype="multipart/form-data" action="{{ setRoute('admin.vendor.store') }}">
                @csrf
                <div class="row mb-10-none">
                    <div class="col-xl-6 col-lg-6 form-group">
                        @include('admin.components.form.input',[
                            'label'         => __("First Name")."*",
                            'name'          => "firstname",
                            'placeholder'   => __("Enter First Name"),
                            'value'         => old("firstname"),
                        ])
                    </div>
                    <div class="col-xl-6 col-lg-6 form-group">
                        @include('admin.components.form.input',[
                            'label'         =>__("Last Name")."*",
                            'name'          => "lastname",
                            'placeholder'   => __("Enter Last Name"),
                            'value'         => old("lastname"),
                        ])
                    </div>
                    <div class="col-xl-6 col-lg-6 form-group">
                        @include('admin.components.form.input',[
                            'label'         =>__("Email")."*",
                            'name'          => "email",
                            'placeholder'   =>__("Enter Email"),
                            'value'         => old("email"),
                        ])
                    </div>
                    <div class="col-xl-6 col-lg-6 form-group">
                        <label>{{ __("Password") }}*</label>
                        <div class="input-group">
                            <input type="text" class="form--control place_random_password @error("password") is-invalid @enderror" placeholder="{{ __('Enter Password') }}" name="password">
                            <button class="input-group-text rand_password_generator" type="button">{{ __("Generate") }}</button>
                        </div>
                        @error("password")
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group">
                        <label>{{ __('Country') }}<span>*</span></label>
                        <select name="country" class="form--control select2-auto-tokenize country-select"
                            data-placeholder="Select Country"
                            data-old="{{ old('country') }}"></select>
                    </div>

                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 form-group">
                        @include('admin.components.form.switcher', [
                            'label'         => __("Email Verification"),
                            'value'         => old('email_verified',1),
                            'name'          => "email_verified",
                            'options'       => [__("Verified") => 1, __("Unverified") => 0],
                            ])
                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 form-group">
                        @include('admin.components.form.switcher', [
                            'label'     => __("KYC Verification"),
                            'value'     => old('kyc_verified',1),
                            'name'      => "kyc_verified",
                            'options'       => [__("Verified") => 1, __("Unverified") => 0],
                        ])
                    </div>

                    <div class="col-xl-12 col-lg-12 form-group">
                        <button type="submit" class="btn--base w-100 btn-loading">{{ __("Add") }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push("script")
    <script>
        function placeRandomPassword(clickedButton,placeInput) {
            $(clickedButton).click(function(){
                var generateRandomPassword = makeRandomString(10);
                $(placeInput).val(generateRandomPassword);
            });
        }
        placeRandomPassword(".rand_password_generator",".place_random_password");

        getAllCountries("{{ setRoute('global.countries') }}");
        $(document).ready(function() {

            openModalWhenError("email-send", "#email-send");

            $("select[name=country]").change(function() {
                var phoneCode = $("select[name=country] :selected").attr("data-mobile-code");
                placePhoneCode(phoneCode);
            });

            setTimeout(() => {
                var phoneCodeOnload = $("select[name=country] :selected").attr("data-mobile-code");
                placePhoneCode(phoneCodeOnload);
            }, 400);

            countrySelect(".country-select", $(".country-select").siblings(".select2"));
            stateSelect(".state-select", $(".state-select").siblings(".select2"));

        })
    </script>
@endpush
