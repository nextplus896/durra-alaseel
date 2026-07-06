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
                    <div>
                        <h4 class="title">{{ __('Edit Car') }}</h4>
                        <p class="subtitle text-muted mt-2">
                            <strong>{{ __('Car Title') }}*:</strong>
                            @if ($cars->car_title)
                                {{ $cars->car_title->{get_default_language_code()}->car_title ?? 'N/A' }}
                            @else
                                <span class="text-warning">{{ __('Not Set') }}</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="card-body">
                    <form class="card-form" action="{{ setRoute('vendor.car.update', $cars->id) }}" method="POST"
                        enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="row mb-20-none">
                            {{-- Fields Section --}}
                            <div class="col-lg-8 col-md-12 mb-20">
                                <div class="row mb-10-none">
                                    <div class="col-xl-12 col-lg-12 col-md-12 mb-10 form-group">
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
                                        <input type="number" class="form--control" name="seat"
                                            placeholder="Enter Number" value="{{ old('seat', $cars->seat) }}">
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6 mb-10 form-group">
                                        <label>{{ __('Year') }}<span>*</span></label>
                                        <select class="select2 select2-basic" name="year" id="car_year">
                                            <option disabled selected>{{ __('Select Year') }}</option>
                                            @for ($y = date('Y') + 1; $y >= 1900; $y--)
                                                <option value="{{ $y }}"
                                                    {{ old('year', $cars->year) == $y ? 'selected' : '' }}>
                                                    {{ $y }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6 mb-10 form-group">
                                        <div class="vehicle-type">
                                            <label>{{ __('Branch') }}<span>*</span></label>
                                            <select class="select2 select2-basic" name="branch_id" id="branch_id">
                                                <option disabled selected>{{ __('Select Branch') }}</option>
                                                @forelse ($branches ?? [] as $branch)
                                                    <option value="{{ $branch->id }}"
                                                        {{ old('branch_id', $cars->branch_id) == $branch->id ? 'selected' : '' }}>
                                                        {{ $branch->name }}
                                                    </option>
                                                @empty
                                                    <option disabled>{{ __('No branches available') }}</option>
                                                @endforelse
                                            </select>
                                        </div>
                                    </div>

                                    {{-- Pricing & Usage Limits Section --}}
                                    <div class="col-xl-12 col-lg-12 mb-10 mt-20">
                                        <div class="dashboard-header-wrapper">
                                            <h5 class="title">{{ __('Pricing & Usage Limits') }}</h5>
                                        </div>
                                    </div>

                                    {{-- Hidden fees syncer --}}
                                    <input type="hidden" name="fees" id="fees_input"
                                        value="{{ old('fees', get_amount($cars->fees)) }}">

                                    <div class="col-xl-4 col-lg-6 col-md-6 mb-10 form-group">
                                        <label>{{ __('Price per Day') }}<span>*</span></label>
                                        <input type="number" step="0.01" class="form--control klm-charge"
                                            name="price_per_day" id="price_per_day"
                                            placeholder="{{ __('Enter Daily Price') }}"
                                            value="{{ old('price_per_day', get_amount($cars->price_per_day > 0 ? $cars->price_per_day : $cars->fees)) }}"
                                            oninput="document.getElementById('fees_input').value = this.value">
                                        <span
                                            class="charge-currency">{{ get_default_currency_code($default_currency ?? 'USD') }}</span>
                                    </div>

                                    <div class="col-xl-4 col-lg-6 col-md-6 mb-10 form-group">
                                        <label>{{ __('Price per Week') }}<span>*</span></label>
                                        <input type="number" step="0.01" class="form--control klm-charge"
                                            name="price_per_week" placeholder="{{ __('Enter Weekly Price') }}"
                                            value="{{ old('price_per_week', get_amount($cars->price_per_week)) }}">
                                        <span
                                            class="charge-currency">{{ get_default_currency_code($default_currency ?? 'USD') }}</span>
                                    </div>

                                    <div class="col-xl-4 col-lg-6 col-md-6 mb-10 form-group">
                                        <label>{{ __('Price per Month') }}<span>*</span></label>
                                        <input type="number" step="0.01" class="form--control klm-charge"
                                            name="price_per_month" placeholder="{{ __('Enter Monthly Price') }}"
                                            value="{{ old('price_per_month', get_amount($cars->price_per_month)) }}">
                                        <span
                                            class="charge-currency">{{ get_default_currency_code($default_currency ?? 'USD') }}</span>
                                    </div>

                                    {{-- Allowance KM Input --}}
                                    <div class="col-xl-6 col-lg-6 col-md-6 mb-10 form-group">
                                        <label>{{ __('Allowance KM') }}</label>
                                        <input type="number" step="any" class="form--control" name="allowance_km"
                                            id="allowance_km" placeholder="{{ __('Enter Allowance KM') }}"
                                            value="{{ old('allowance_km', $cars->allowance_km ?? 0) }}">
                                    </div>

                                    {{-- Allowance Price Per KM Input --}}
                                    <div class="col-xl-6 col-lg-6 col-md-6 mb-10 form-group">
                                        <label>{{ __('Price Per Extra KM') }}</label>
                                        <input type="number" step="any" class="form--control"
                                            name="allowance_price_per_km" id="allowance_price_per_km"
                                            placeholder="{{ __('Enter Price Per Extra KM') }}"
                                            value="{{ old('allowance_price_per_km', get_amount($cars->allowance_price_per_km ?? 0)) }}">
                                        <span
                                            class="charge-currency">{{ get_default_currency_code($default_currency ?? 'USD') }}</span>
                                    </div>

                                    {{-- Insurance Settings Section --}}
                                    <div class="col-xl-12 col-lg-12 mb-10 mt-20">
                                        <div class="dashboard-header-wrapper">
                                            <h5 class="title">{{ __('Insurance') }}</h5>
                                        </div>
                                    </div>

                                    <div class="col-xl-6 col-lg-6 col-md-6 mb-10 form-group">
                                        <label>{{ __('Daily Insurance') }} <span>*</span></label>
                                        <input type="number" step="0.01" min="0" class="form--control"
                                            name="daily_insurance"
                                            placeholder="{{ __('e.g. 20.00') }}"
                                            value="{{ old('daily_insurance', get_amount($cars->daily_insurance ?? 0)) }}" required>
                                        <span class="charge-currency">{{ get_default_currency_code($default_currency ?? 'USD') }}</span>
                                        <small class="text-muted d-block mt-1">{{ __('Charged per rental day and included in the booking total.') }}</small>
                                    </div>

                                    <div class="col-xl-6 col-lg-6 col-md-6 mb-10 form-group">
                                        <label>{{ __('Deductible Insurance (Excess)') }} <span>*</span></label>
                                        <input type="number" step="0.01" min="0" class="form--control"
                                            name="deductible_insurance"
                                            placeholder="{{ __('e.g. 3500.00') }}"
                                            value="{{ old('deductible_insurance', get_amount($cars->deductible_insurance ?? 0)) }}" required>
                                        <span class="charge-currency">{{ get_default_currency_code($default_currency ?? 'USD') }}</span>
                                        <small class="text-muted d-block mt-1">{{ __('Customer liability in case of accident. Displayed only — never charged.') }}</small>
                                    </div>

                                    {{-- Delivery Settings Section --}}
                                    <div class="col-xl-12 col-lg-12 mb-10 mt-20">
                                        <div class="dashboard-header-wrapper">
                                            <h5 class="title">{{ __('Delivery Settings') }}</h5>
                                        </div>
                                    </div>
                                    <div class="col-xl-12 col-lg-12 col-md-12 mb-10 form-group">
                                        <div class="switch-wrapper d-flex align-items-center mt-4"
                                            style="display: flex; align-items: center;">
                                            <label class="switch m-0">
                                                <input type="checkbox" name="delivery_available" value="1"
                                                    {{ old('delivery_available', $delivery_setting->delivery_available ?? false) ? 'checked' : '' }}>
                                                <span class="slider round"></span>
                                            </label>
                                            <div class="ms-2">
                                                <h6 class="mb-0 text-dark fw-bold">{{ __('Delivery Available') }}</h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Image and Info Section --}}
                            <div class="col-lg-4 col-md-12 mb-20">
                                <div class="row mb-10-none">
                                    <div class="col-xl-12 col-lg-12 mb-10 form-group">
                                        <label>{{ __('Car Model Image') }}</label>
                                        <div class="car-model-image-display">
                                            @if ($cars->carModel && $cars->carModel->image)
                                                <img id="modelImage" src="{{ $cars->carModel->image_url }}"
                                                    alt="Car Model Image"
                                                    style="max-width: 100%; max-height: 300px; height: auto; border-radius: 8px;">
                                            @else
                                                <img id="modelImage" src="" alt="Car Model Image"
                                                    style="max-width: 100%; max-height: 300px; height: auto; border-radius: 8px; display: none;">
                                            @endif
                                            <p id="noImage"
                                                style="color: #999; {{ $cars->carModel && $cars->carModel->image ? 'display: none;' : '' }}">
                                                {{ __('Select a vehicle model to see the image') }}</p>
                                        </div>
                                    </div>
                                    <div class="col-xl-12 col-lg-12 mb-10">
                                        <div class="alert alert-info" role="alert"
                                            style="direction: rtl; text-align: justify;">
                                            <span id="allowance-text">الكيلومترات المسموح بها هي 0 كم، وفي حال تجاوزها يتم
                                                احتساب 0 {{ __(get_default_currency_code($default_currency ?? 'SAR')) }} لكل كيلومتر إضافي</span>
                                        </div>
                                    </div>
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
        var currencyCode = '{{ __(get_default_currency_code($default_currency ?? "SAR")) }}';

        $(document).ready(function() {
            function updateAllowanceText() {
                var km = $('#allowance_km').val() || 0;
                var amount = $('#allowance_price_per_km').val() || 0;
                var text =
                    `الكيلومترات المسموح بها هي ${km} كم، وفي حال تجاوزها يتم احتساب ${amount} ${currencyCode} لكل كيلومتر إضافي`;
                $('#allowance-text').text(text);
            }

            $('#allowance_km, #allowance_price_per_km').on('input change', updateAllowanceText);

            // Initial call
            updateAllowanceText();

            var getModelsURL = "{{ setRoute('vendor.car.get.models') }}";
            var selectedModelId = "{{ $cars->car_model_id ?? '' }}";
            var selectedTypeName = $('select[name="type"] option:selected').text().trim();
            var selectedModelName = '';

            // Function to update all car title inputs
            function updateCarTitles() {
                if (selectedTypeName && selectedModelName && selectedTypeName !==
                    '{{ __('Select Vehicle Type') }}') {
                    var combinedTitle = selectedTypeName + ' ' + selectedModelName;
                    // Update all language tab title inputs
                    $('input[name$="_car_title"]').each(function() {
                        $(this).val(combinedTitle);
                    });
                }
            }

            // Load models on page load if type is selected
            var initialTypeId = $('select[name="type"]').val();
            if (initialTypeId) {
                loadModels(initialTypeId, selectedModelId);
            }

            $('select[name="type"]').on('change', function() {
                var typeId = $(this).val();
                selectedTypeName = $(this).find('option:selected').text().trim();
                if (typeId == "" || typeId == null) {
                    return false;
                }
                // Clear image when type changes
                $('#modelImage').hide();
                $('#noImage').show();
                selectedModelName = '';
                loadModels(typeId, null);
            });

            // Load image and update title when car model is selected
            $(document).on('change', 'select[name="car_model_id"]', function() {
                loadModelImage();
                selectedModelName = $(this).find('option:selected').data('name') || $(this).find(
                    'option:selected').text().trim();
                updateCarTitles();
            });

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
                                `<option value="${item.id}" ${selected} data-image-url="${item.image_url}" data-name="${item.name}">${item.name}</option>`;
                        });

                        $("select[name=car_model_id]").html(option);
                        $("select[name=car_model_id]").select2();

                        // Load image for pre-selected model and get model name
                        if (selectedId) {
                            loadModelImage();
                            selectedModelName = $('#car_model option:selected').data('name') || $(
                                '#car_model option:selected').text().trim();
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
