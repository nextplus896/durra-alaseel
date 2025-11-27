@extends('vendor-end.layouts.master')

@push('css')
    <style>
        .fileholder {
            min-height: 200px !important;
        }

        .fileholder-files-view-wrp.accept-single-file .fileholder-single-file-view,
        .fileholder-files-view-wrp.fileholder-perview-single .fileholder-single-file-view {
            height: 330px !important;
        }
    </style>
@endpush

@section('content')
    <div class="row mb-20-none">
        <div class="col-xl-12 col-lg-12 mb-20">
            <div class="custom-card mt-10">
                <div class="dashboard-header-wrapper">
                    <h4 class="title">{{ __('Add New Car') }}</h4>
                </div>
                <div class="card-body">
                    <form class="card-form" action="{{ setRoute('vendor.car.store') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="row mb-10-none">
                            <div class="col-xl-12 col-lg-12 col-md-6 mb-10 form-group">
                                <nav>
                                    <div class="nav nav-tabs car-tab-button" id="nav-tab" role="tablist">
                                        @foreach ($languages as $item)
                                            <button class="nav-link @if (get_default_language_code() == $item->code) active @endif"
                                                id="{{ $item->code }}-tab" data-bs-toggle="tab"
                                                data-bs-target="#{{ $item->code }}" type="button" role="tab"
                                                aria-controls="{{ $item->code }}"
                                                aria-selected="true">{{ $item->name }}</button>
                                        @endforeach

                                    </div>
                                </nav>
                                <div class="tab-content" id="nav-tabContent">
                                    @foreach ($languages as $item)
                                        @php
                                            $lang_code = $item->code;
                                        @endphp
                                        <div class="tab-pane @if (get_default_language_code() == $item->code) fade show active @endif"
                                            id="{{ $item->code }}" role="tabpanel" aria-labelledby="english-tab">
                                            <div class="form-group">
                                                @include('admin.components.form.input', [
                                                    'label' => __('Car Title'),
                                                    'label_after' => '*',
                                                    'placeholder' => __('Write Here') . '...',
                                                    'name' => $lang_code . '_car_title',
                                                    'value' => old(
                                                        $lang_code . '_car_title',
                                                        $data->value->language->$lang_code->car_title ?? ''),
                                                ])
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 mb-10 form-group">
                                <div class="vehicle-type">
                                    <label>{{ __('Vehicle Type') }}<span>*</span></label>
                                    <select class="select2 select2-basic" name="type" id="car_type"
                                        value="{{ old('type') }}">
                                        <option disabled selected>{{ __('Select Vehicle Type') }}</option>
                                        @forelse ($car_type ?? [] as $value)
                                            <option value="{{ $value->id }}">{{ $value->name }}</option>
                                        @empty
                                        @endforelse
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 mb-10 form-group">
                                <div class="vehicle-type">
                                    <label>{{ __('Vehicle Model') }}<span>*</span></label>
                                    <select class="select2 select2-basic" name="car_model_id" id="car_model">
                                        <option disabled selected>{{ __('Select Vehicle Model') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 mb-10 form-group">
                                <label>{{ __('Total Seat') }}<span>*</span></label>
                                <input type="number" class="form--control" id="seat" name="seat"
                                    placeholder="Enter Number" value="{{ old('seat') }}">
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 mb-10 form-group">
                                <label>{{ __('Year') }}<span>*</span></label>
                                <select class="select2 select2-basic" name="year" id="car_year">
                                    <option disabled selected>{{ __('Select Year') }}</option>
                                    @for ($y = date('Y') + 1; $y >= 1900; $y--)
                                        <option value="{{ $y }}" {{ old('year') == $y ? 'selected' : '' }}>
                                            {{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 mb-10 form-group">
                                <label>{{ __('Rental Price Day') }}<span>*</span></label>
                                <input type="text" class="form--control klm-charge" name="fees"
                                    placeholder="{{ __('Enter Rental Price') }}" value="{{ old('fees') }}">
                                <span class="charge-currency">{{ get_default_currency_code() }}</span>
                            </div>
                            <div class="col-xl-12 col-lg-12 mb-10 form-group">
                                <label>{{ __('Car Model Image') }}</label>
                                <div class="car-model-image-display">
                                    <img id="modelImage" src="" alt="Car Model Image"
                                        style="max-width: 100%; max-height: 300px; height: auto; border-radius: 8px; display: none;">
                                    <p id="noImage" style="color: #999;">
                                        {{ __('Select a vehicle model to see the image') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="button pt-3">
                            <button type="submit" class="btn btn--base w-100">{{ __('Add Now') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $(document).ready(function() {
            var getModelsURL = "{{ setRoute('vendor.car.get.models') }}";
            var selectedTypeName = '';
            var selectedModelName = '';

            // Function to update all car title inputs
            function updateCarTitles() {
                if (selectedTypeName && selectedModelName) {
                    var combinedTitle = selectedTypeName + ' ' + selectedModelName;
                    // Update all language tab title inputs
                    $('input[name$="_car_title"]').each(function() {
                        $(this).val(combinedTitle);
                    });
                }
            }

            $('select[name="type"]').on('change', function() {
                var typeId = $(this).val();
                selectedTypeName = $(this).find('option:selected').text().trim();

                if (typeId == "" || typeId == null) {
                    return false;
                }

                // Clear current selection and image
                $('#modelImage').hide();
                $('#noImage').show();
                selectedModelName = '';

                $.post(getModelsURL, {
                    type_id: typeId,
                    _token: "{{ csrf_token() }}"
                }, function(response) {
                    var option =
                        '<option disabled selected>{{ __('Select Vehicle Model') }}</option>';
                    if (response.data.models.length > 0) {
                        $.each(response.data.models, function(index, item) {
                            option +=
                                `<option value="${item.id}" data-image-url="${item.image_url}" data-name="${item.name}">${item.name}</option>`;
                        });

                        $("select[name=car_model_id]").html(option);
                        $("select[name=car_model_id]").select2();
                    } else {
                        $("select[name=car_model_id]").html(option);
                        $("select[name=car_model_id]").select2();
                    }
                }).fail(function(response) {
                    var errorText = response.responseJSON;
                });
            });

            // Load image and update title when car model is selected
            $(document).on('change', 'select[name="car_model_id"]', function() {
                loadModelImage();
                selectedModelName = $(this).find('option:selected').data('name') || $(this).find(
                    'option:selected').text().trim();
                updateCarTitles();
            });

            // Function to load and display model image
            function loadModelImage() {
                var selectedOption = $('#car_model option:selected');
                var modelImageUrl = selectedOption.data('image-url');

                if (modelImageUrl && selectedOption.val()) {
                    $('#modelImage').attr('src', modelImageUrl).show();
                    $('#noImage').hide();
                } else {
                    $('#modelImage').hide();
                    $('#noImage').show();
                }
            }
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const quantityInput = document.getElementById('seat');
            quantityInput.addEventListener('input', function(event) {
                let inputValue = event.target.value.trim();
                if (inputValue === '' || inputValue <= '0') {
                    inputValue = '1';
                }
                // Update the input value
                event.target.value = inputValue;
            });
        });
    </script>
@endpush
