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
                    <h4 class="title">{{ __('Edit Car') }}</h4>
                </div>
                <div class="card-body">
                    <form class="card-form" action="{{ setRoute('vendor.car.update', $cars->id) }}" method="POST"
                        enctype="multipart/form-data">
                        @method('PUT')
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
                                                        $cars->car_title->$lang_code->car_title ?? ''),
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
                                        @foreach ($car_type as $type)
                                            <option value="{{ $type->id }}"
                                                {{ $type->id == $cars->car_type_id ? 'selected' : '' }}>
                                                {{ $type->name }}
                                            </option>
                                        @endforeach
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
                                <input type="number" class="form--control" name="seat" placeholder="Enter Number"
                                    value="{{ old('seat', $cars->seat) }}">
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 mb-10 form-group">
                                <label>{{ __('Rental Price Day') }}<span>*</span></label>
                                <input type="text" class="form--control klm-charge" name="fees"
                                    placeholder="{{ __('Enter Rental Price') }}"
                                    value="{{ old('fees', get_amount($cars->fees)) }}">
                                <span class="charge-currency">{{ get_default_currency_code($default_currency) }}</span>
                            </div>
                            <div class="col-xl-12 col-lg-12 mb-10 form-group">
                                <label>{{ __('Car Model Image') }}</label>
                                <div class="car-model-image-display">
                                    @if ($cars->carModel && $cars->carModel->image)
                                        <img id="modelImage" src="{{ get_image($cars->carModel->image, 'car-models') }}"
                                            alt="Car Model Image"
                                            style="max-width: 100%; height: auto; border-radius: 8px;">
                                    @else
                                        <img id="modelImage" src="" alt="Car Model Image"
                                            style="max-width: 100%; height: auto; border-radius: 8px; display: none;">
                                    @endif
                                    <p id="noImage"
                                        style="color: #999; {{ $cars->carModel && $cars->carModel->image ? 'display: none;' : '' }}">
                                        {{ __('Select a vehicle model to see the image') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="button pt-3">
                            <button type="submit" class="btn btn--base w-100">{{ __('Update') }}</button>
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
            var selectedModelId = "{{ $cars->car_model_id ?? '' }}";

            // Load models on page load if type is selected
            var initialTypeId = $('select[name="type"]').val();
            if (initialTypeId) {
                loadModels(initialTypeId, selectedModelId);
            }

            $('select[name="type"]').on('change', function() {
                var typeId = $(this).val();
                if (typeId == "" || typeId == null) {
                    return false;
                }
                loadModels(typeId, null);
            });

            // Load image when car model is selected
            $('select[name="car_model_id"]').on('change', function() {
                loadModelImage($(this).val());
            });

            function loadModelImage(modelId) {
                if (!modelId) {
                    $('#modelImage').hide();
                    $('#noImage').show();
                    return;
                }

                // Get model data from the select options
                var selectedOption = $('#car_model option:selected');
                var modelImage = selectedOption.data('image');

                if (modelImage) {
                    var imageUrl = "{{ files_asset_path('car-models') }}" + "/" + modelImage;
                    $('#modelImage').attr('src', imageUrl).show();
                    $('#noImage').hide();
                } else {
                    $('#modelImage').hide();
                    $('#noImage').show();
                }
            }

            function loadModels(typeId, selectedId) {
                $.post(getModelsURL, {
                    type_id: typeId,
                    _token: "{{ csrf_token() }}"
                }, function(response) {
                    var option = '<option disabled selected>{{ __('Select Vehicle Model') }}</option>';
                    if (response.data.models.length > 0) {
                        $.each(response.data.models, function(index, item) {
                            var selected = (selectedId && selectedId == item.id) ? 'selected' : '';
                            option +=
                                `<option value="${item.id}" ${selected} data-image="${item.image}">${item.name}</option>`;
                        });

                        $("select[name=car_model_id]").html(option);
                        $("select[name=car_model_id]").select2();
                        if (selectedId) {
                            loadModelImage(selectedId);
                        }
                    } else {
                        $("select[name=car_model_id]").html(option);
                        $("select[name=car_model_id]").select2();
                    }
                }).fail(function(response) {
                    var errorText = response.responseJSON;
                });
            }
        });
    </script>
@endpush
