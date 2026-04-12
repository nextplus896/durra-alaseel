@extends('vendor-end.layouts.master')

@section('content')
    @php
        $pickupDateTime = null;
        $pickupDateTimeFormatted = __('N/A');
        $rentalDays = (int) ($booking->rental_days ?? 0);
        $originalRentalDays = (int) ($booking->original_rental_days ?? $rentalDays);
        $extensionDays = (int) ($booking->total_extension_days ?? 0);
        $returnDateTimeFormatted = __('N/A');
        $originalReturnDateTimeFormatted = __('N/A');

        if (!empty($booking->pickup_date) && !empty($booking->pickup_time)) {
            try {
                $pickupDateTime = \Carbon\Carbon::parse($booking->pickup_date . ' ' . $booking->pickup_time);
                $pickupDateTimeFormatted = $pickupDateTime->format('d M Y, h:i A');
            } catch (\Exception $e) {
                $pickupDateTime = null;
            }
        }

        if ($pickupDateTime && $rentalDays > 0) {
            $returnDateTimeFormatted = $pickupDateTime->copy()->addDays($rentalDays)->format('d M Y, h:i A');
            if ($extensionDays > 0) {
                $originalReturnDateTimeFormatted = $pickupDateTime
                    ->copy()
                    ->addDays($originalRentalDays)
                    ->format('d M Y, h:i A');
            }
        }
    @endphp

    <div class="booking-details pt-40">
        <div class="row mb-4 align-items-center">
            <div class="col-md-6 mb-3 mb-md-0">
                <h3 class="title mb-1 d-flex align-items-center gap-2">
                    {{ __('Booking Details') }}
                    <span class="badge bg-primary bg-opacity-10 text-primary fs-6 px-3 py-2 rounded-pill">
                        {{ $booking->trx_id ?? ($booking->trip_id ?? '#' . $booking->id) }}
                    </span>
                </h3>
                <p class="text-muted small mb-0 d-flex align-items-center gap-1">
                    <i class="las la-calendar-alt"></i>
                    {{ $booking->created_at->timezone($display_timezone)->format('d M Y, h:i A') }}
                </p>
            </div>
            <div class="col-md-6 d-flex flex-wrap gap-2 justify-content-md-end">
                <a href="{{ route('vendor.booking.index') }}" class="btn btn-light border shadow-sm">
                    <i class="las la-arrow-left me-1"></i> {{ __('Back to List') }}
                </a>

                @if ($booking->status === 1)
                    <a href="{{ route('vendor.booking.accept', $booking->id) }}"
                        class="btn btn-success shadow-sm rounded-pill px-4 py-2 fw-medium d-flex align-items-center gap-2"
                        onclick="return confirm('{{ __('Are you sure you want to accept this booking?') }}')">
                        <i class="las la-check-circle fs-5"></i> {{ __('Accept Request') }}
                    </a>
                    <button type="button"
                        class="btn btn-danger shadow-sm rounded-pill px-4 py-2 fw-medium d-flex align-items-center gap-2"
                        data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="las la-times-circle fs-5"></i> {{ __('Reject') }}
                    </button>
                @elseif ($booking->status === 2)
                    <a href="{{ route('vendor.booking.complete', $booking->id) }}"
                        class="btn btn-primary shadow-sm rounded-pill px-4 py-2 fw-medium d-flex align-items-center gap-2"
                        onclick="return confirm('{{ __('Are you sure you want to mark this booking as complete?') }}')">
                        <i class="las la-check-double fs-5"></i> {{ __('Complete') }}
                    </a>
                @endif
            </div>
        </div>

        <div class="row g-4">
            {{-- Left Column: Customer & Car --}}
            <div class="col-lg-4">
                {{-- Status Card --}}
                <div class="card mb-4 border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <h6 class="text-uppercase text-muted fw-bold small mb-3 d-flex align-items-center gap-2">
                            <i class="las la-info-circle fs-5"></i> {{ __('Status') }}
                        </h6>
                        @if ($booking->status === 1)
                            <div class="alert alert-warning d-flex align-items-center mb-0 border-0 text-dark rounded-3">
                                <div
                                    class="bg-white bg-opacity-50 rounded-circle p-2 me-3 d-flex align-items-center justify-content-center">
                                    <i class="las la-clock fs-3 text-warning"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-bold">{{ __('Pending Approval') }}</h6>
                                    <small class="opacity-75">{{ __('Waiting for your confirmation') }}</small>
                                </div>
                            </div>
                        @elseif($booking->status === 2)
                            <div
                                class="alert alert-success d-flex align-items-center mb-0 border-0 text-white bg-success rounded-3">
                                <div
                                    class="bg-white bg-opacity-25 rounded-circle p-2 me-3 d-flex align-items-center justify-content-center">
                                    <i class="las la-running fs-3 text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-bold">{{ __('Ongoing') }}</h6>
                                    <small class="opacity-75">{{ __('Trip is currently in progress') }}</small>
                                </div>
                            </div>
                        @elseif($booking->status === 3)
                            <div class="alert alert-secondary d-flex align-items-center mb-0 border-0 rounded-3">
                                <div
                                    class="bg-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center shadow-sm">
                                    <i class="las la-check fs-3 text-secondary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-bold">{{ __('Completed') }}</h6>
                                    <small class="text-muted">{{ __('Trip finished successfully') }}</small>
                                </div>
                            </div>
                        @elseif($booking->status === 4)
                            <div class="alert alert-danger d-flex align-items-start mb-0 border-0 rounded-3">
                                <div
                                    class="bg-white bg-opacity-50 rounded-circle p-2 me-3 mt-1 d-flex align-items-center justify-content-center">
                                    <i class="las la-times fs-3 text-danger"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                        <h6 class="mb-0 fw-bold">{{ __('Cancelled') }}</h6>
                                        @if ($booking->transaction?->refundable == 2)
                                            <span
                                                class="badge bg-warning text-dark rounded-pill px-2">{{ __('Refund Pending') }}</span>
                                        @endif
                                    </div>
                                    <small class="opacity-75">{{ __('Trip was cancelled') }}</small>
                                    @if (!empty($booking->rejection_reason))
                                        <div class="mt-3 pt-3 border-top border-danger border-opacity-25">
                                            <small class="d-block fw-bold mb-1 text-uppercase"
                                                style="font-size: 0.7rem;">{{ __('Reason') }}</small>
                                            <small class="fst-italic opacity-75">{{ $booking->rejection_reason }}</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Customer Info --}}
                <div class="card mb-4 border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h6 class="text-uppercase text-muted fw-bold small mb-4 d-flex align-items-center gap-2">
                            <i class="las la-user-circle fs-5"></i> {{ __('Customer Information') }}
                        </h6>
                        @if ($booking->user)
                            <div class="text-center mb-4">
                                <div class="mx-auto rounded-circle overflow-hidden border border-2 border-light shadow-sm mb-3 position-relative"
                                    style="width: 90px; height: 90px;">
                                    @if ($booking->user->image)
                                        <img src="{{ get_image($booking->user->image, 'user-profile') }}"
                                            class="w-100 h-100 object-fit-cover">
                                    @else
                                        <div
                                            class="w-100 h-100 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center fw-bold fs-1 text-primary">
                                            {{ substr($booking->user->firstname ?? 'U', 0, 1) }}
                                        </div>
                                    @endif
                                    @php
                                        $kycStatus = $booking->user->kyc_verified;
                                    @endphp
                                    @if ($kycStatus == 1)
                                        <div class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-2 border-white d-flex align-items-center justify-content-center"
                                            style="width: 24px; height: 24px; transform: translate(-5px, -5px);">
                                            <i class="las la-check text-white" style="font-size: 12px;"></i>
                                        </div>
                                    @endif
                                </div>
                                <h5 class="fw-bold mb-1">{{ $booking->user->fullname }}</h5>
                                <div class="d-flex align-items-center justify-content-center gap-2 mt-2">
                                    @php
                                        $kycObject = $booking->user->kycStringStatus;
                                        $kycLabel = is_object($kycObject)
                                            ? $kycObject->value ?? 'Unverified'
                                            : $kycObject ?? 'Unverified';
                                    @endphp
                                    <span
                                        class="badge {{ $kycStatus == 1 ? 'bg-success bg-opacity-10 text-success' : 'bg-warning bg-opacity-10 text-warning' }} rounded-pill px-3 py-2 border {{ $kycStatus == 1 ? 'border-success border-opacity-25' : 'border-warning border-opacity-25' }}">
                                        <i
                                            class="las {{ $kycStatus == 1 ? 'la-shield-alt' : 'la-exclamation-triangle' }} me-1"></i>
                                        {{ $kycLabel }}
                                    </span>
                                </div>
                            </div>

                            <div class="list-group list-group-flush small">
                                <div
                                    class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-light">
                                    <span class="text-muted d-flex align-items-center gap-2">
                                        <div class="bg-light rounded p-2 d-flex align-items-center justify-content-center">
                                            <i class="las la-envelope fs-5 text-primary"></i>
                                        </div>
                                        {{ __('Email') }}
                                    </span>
                                    <span class="fw-medium text-break text-end">{{ $booking->user->email }}</span>
                                </div>
                                <div
                                    class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-light">
                                    <span class="text-muted d-flex align-items-center gap-2">
                                        <div class="bg-light rounded p-2 d-flex align-items-center justify-content-center">
                                            <i class="las la-phone fs-5 text-primary"></i>
                                        </div>
                                        {{ __('Phone') }}
                                    </span>
                                    <span class="fw-medium text-end"
                                        dir="ltr">{{ $booking->user->full_mobile ?? $booking->user->mobile }}</span>
                                </div>
                                <div
                                    class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-light">
                                    <span class="text-muted d-flex align-items-center gap-2">
                                        <div class="bg-light rounded p-2 d-flex align-items-center justify-content-center">
                                            <i class="las la-id-card fs-5 text-primary"></i>
                                        </div>
                                        {{ __('License Image') }}
                                    </span>
                                    @if (!empty($booking->user->driving_license))
                                        <a class="doc-preview position-relative d-block"
                                            href="{{ get_image($booking->user->driving_license, 'user-driving-license') }}"
                                            data-doc-title="{{ __('License Image') }}">
                                            <img src="{{ get_image($booking->user->driving_license, 'user-driving-license') }}"
                                                alt="{{ __('License Image') }}"
                                                class="rounded-3 border shadow-sm object-fit-cover"
                                                style="width: 64px; height: 48px;">
                                            <div class="position-absolute top-50 start-50 translate-middle bg-dark bg-opacity-50 rounded-circle d-flex align-items-center justify-content-center"
                                                style="width: 24px; height: 24px;">
                                                <i class="las la-search-plus text-white" style="font-size: 14px;"></i>
                                            </div>
                                        </a>
                                    @else
                                        <span
                                            class="fw-medium text-muted small">{{ __('No License / لا يوجد ترخيص') }}</span>
                                    @endif
                                </div>
                                <div
                                    class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-light">
                                    <span class="text-muted d-flex align-items-center gap-2">
                                        <div class="bg-light rounded p-2 d-flex align-items-center justify-content-center">
                                            <i class="las la-address-card fs-5 text-primary"></i>
                                        </div>
                                        {{ __('National ID Image') }}
                                    </span>
                                    @if (!empty($booking->user->national_id))
                                        <a class="doc-preview position-relative d-block"
                                            href="{{ get_image($booking->user->national_id, 'user-national-id') }}"
                                            data-doc-title="{{ __('National ID Image') }}">
                                            <img src="{{ get_image($booking->user->national_id, 'user-national-id') }}"
                                                alt="{{ __('National ID Image') }}"
                                                class="rounded-3 border shadow-sm object-fit-cover"
                                                style="width: 64px; height: 48px;">
                                            <div class="position-absolute top-50 start-50 translate-middle bg-dark bg-opacity-50 rounded-circle d-flex align-items-center justify-content-center"
                                                style="width: 24px; height: 24px;">
                                                <i class="las la-search-plus text-white" style="font-size: 14px;"></i>
                                            </div>
                                        </a>
                                    @else
                                        <span
                                            class="fw-medium text-muted small">{{ __('No National Id / لا يوجد هوية') }}</span>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                                    style="width: 50px; height: 50px;">
                                    <i class="las la-user-secret text-muted fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ __('Guest User') }}</h6>
                                    <small class="text-muted d-block">{{ $booking->email }}</small>
                                    <small class="text-muted d-block">{{ $booking->phone }}</small>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Car Info --}}
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        @php
                            $car = $booking->cars;
                            $carImage = $car?->image_url ?? files_asset_path('default');
                        @endphp
                        <h6 class="text-uppercase text-muted fw-bold small mb-4 d-flex align-items-center gap-2">
                            <i class="las la-car fs-5"></i> {{ __('Car Details') }}
                        </h6>
                        <a class="doc-preview d-block rounded-4 overflow-hidden mb-4 position-relative shadow-sm border border-light"
                            href="{{ $carImage }}" data-doc-title="{{ __('Car Image Preview') }}">
                            <img src="{{ $carImage }}" alt="car"
                                onerror="this.onerror=null;this.src='{{ files_asset_path('default') }}';"
                                class="w-100 object-fit-cover car-preview-image" style="aspect-ratio: 16/9;">
                            <div class="position-absolute top-50 start-50 translate-middle bg-dark bg-opacity-50 rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; opacity: 0; transition: opacity 0.2s;"
                                onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0">
                                <i class="las la-search-plus text-white fs-5"></i>
                            </div>
                        </a>
                        <h5 class="fw-bold mb-1">{{ $car?->car_model ?: __('Car') }}</h5>
                        @if (!empty($car?->car_number))
                            <div
                                class="d-inline-block bg-light border rounded px-2 py-1 mb-3 font-monospace small fw-bold text-secondary">
                                {{ $car->car_number }}
                            </div>
                        @endif

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3 h-100">
                                    <small class="text-muted d-block mb-1">{{ __('Type') }}</small>
                                    <span class="fw-semibold text-dark">{{ $car?->type?->name ?? __('N/A') }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3 h-100">
                                    <small class="text-muted d-block mb-1">{{ __('Model') }}</small>
                                    <span
                                        class="fw-semibold text-dark">{{ $car?->carModel?->name ?? ($car?->car_model ?? __('N/A')) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @if (!empty($car?->fuel_type))
                                <span
                                    class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2 fw-medium">
                                    <i class="las la-gas-pump me-1"></i> {{ $car->fuel_type }}
                                </span>
                            @endif

                            @if (!empty($car?->seat))
                                <span
                                    class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2 fw-medium">
                                    <i class="las la-users me-1"></i> {{ $car->seat }} {{ __('Seats') }}
                                </span>
                            @endif

                            @if (!empty($car?->top_speed))
                                <span
                                    class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2 fw-medium">
                                    <i class="las la-tachometer-alt me-1"></i> {{ $car->top_speed }} km/h
                                </span>
                            @endif

                            @if (empty($car?->fuel_type) && empty($car?->seat) && empty($car?->top_speed))
                                <span class="text-muted small fst-italic">{{ __('Car specs unavailable') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Trip Info --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100 rounded-4">
                    <div class="card-body p-4 p-md-5">
                        <h5 class="card-title fw-bold mb-5 d-flex align-items-center gap-2">
                            <i class="las la-route fs-4 text-primary"></i> {{ __('Journey Details') }}
                        </h5>

                        <ul class="activity-timeline ms-3 ms-md-4 mb-5">
                            <li class="ps-4 ps-md-5 position-relative">
                                <span
                                    class="position-absolute top-0 start-0 translate-middle p-2 bg-success border border-3 border-white rounded-circle shadow-sm"
                                    style="width: 20px; height: 20px;"></span>
                                <p class="text-muted small mb-1 text-uppercase fw-bold tracking-wide">
                                    {{ __('Pick-up Location') }}</p>
                                <h5 class="mb-3 fw-bold text-dark">{{ $booking->location }}</h5>
                                <a href="https://maps.google.com/?q={{ urlencode($booking->location) }}" target="_blank"
                                    class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm">
                                    <i class="las la-map-marked-alt me-1"></i> {{ __('View on Map') }}
                                </a>

                                <div
                                    class="mt-4 mt-md-5 bg-light rounded-4 p-4 p-md-5 border border-light-subtle shadow-sm">
                                    @if ($extensionDays > 0)
                                        {{-- ── Extended booking: two-segment timeline ── --}}
                                        <div
                                            class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-4 text-center text-md-start">

                                            <!-- Pick-up -->
                                            <div
                                                class="d-flex flex-column align-items-center align-items-md-start text-md-start position-relative z-1">
                                                <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow mb-3 border border-2 border-primary border-opacity-25"
                                                    style="width: 56px; height: 56px;">
                                                    <i class="las la-calendar-check text-primary fs-2"></i>
                                                </div>
                                                <small
                                                    class="text-muted text-uppercase fw-bold tracking-wide">{{ __('Pick-up') }}</small>
                                                <span class="fw-bold text-dark mt-1 text-nowrap"
                                                    dir="ltr">{{ $pickupDateTimeFormatted }}</span>
                                            </div>

                                            <!-- Segment 1: Rental Days -->
                                            <div
                                                class="flex-grow-1 d-flex flex-column align-items-center px-2 w-100 position-relative">
                                                <div class="d-flex align-items-center w-100 position-absolute top-50 start-50 translate-middle d-none d-md-flex"
                                                    style="z-index: 0;">
                                                    <div
                                                        class="border-top border-2 border-primary border-dashed opacity-50 flex-grow-1">
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-center w-100 position-absolute top-50 start-50 translate-middle d-md-none"
                                                    style="z-index: 0; height: 100%;">
                                                    <div
                                                        class="border-start border-2 border-primary border-dashed opacity-50 h-100">
                                                    </div>
                                                </div>
                                                <div
                                                    class="px-4 text-primary fw-bold small bg-white rounded-pill border border-2 border-primary border-opacity-25 shadow-sm py-2 mx-2 text-nowrap position-relative z-1 my-3 my-md-0">
                                                    <i
                                                        class="las la-stopwatch me-1 fs-5 align-middle"></i>{{ $originalRentalDays }}
                                                    {{ __('Days') }}
                                                </div>
                                            </div>

                                            <!-- Mid-point: Original Return (= Extension start) -->
                                            <div class="d-flex flex-column align-items-center position-relative z-1">
                                                <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow mb-3 border border-2 border-warning"
                                                    style="width: 56px; height: 56px;">
                                                    <i class="las la-plus-circle text-warning fs-2"></i>
                                                </div>
                                                <small
                                                    class="text-warning text-uppercase fw-bold tracking-wide">{{ __('Extension') }}</small>
                                                <span class="text-muted mt-1 text-nowrap"
                                                    dir="ltr">{{ $originalReturnDateTimeFormatted }}</span>
                                            </div>

                                            <!-- Segment 2: Extension Days -->
                                            <div
                                                class="flex-grow-1 d-flex flex-column align-items-center px-2 w-100 position-relative">
                                                <div class="d-flex align-items-center w-100 position-absolute top-50 start-50 translate-middle d-none d-md-flex"
                                                    style="z-index: 0;">
                                                    <div
                                                        class="border-top border-2 border-warning border-dashed opacity-50 flex-grow-1">
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-center w-100 position-absolute top-50 start-50 translate-middle d-md-none"
                                                    style="z-index: 0; height: 100%;">
                                                    <div
                                                        class="border-start border-2 border-warning border-dashed opacity-50 h-100">
                                                    </div>
                                                </div>
                                                <div
                                                    class="px-4 text-warning fw-bold small bg-white rounded-pill border border-2 border-warning border-opacity-50 shadow-sm py-2 mx-2 text-nowrap position-relative z-1 my-3 my-md-0">
                                                    <i class="las la-plus me-1 fs-5 align-middle"></i>{{ $extensionDays }}
                                                    {{ __('Days') }}
                                                </div>
                                            </div>

                                            <!-- Final Return -->
                                            <div
                                                class="d-flex flex-column align-items-center align-items-md-end text-md-end position-relative z-1">
                                                <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow mb-3 border border-2 border-danger border-opacity-25"
                                                    style="width: 56px; height: 56px;">
                                                    <i class="las la-calendar-times text-danger fs-2"></i>
                                                </div>
                                                <small
                                                    class="text-muted text-uppercase fw-bold tracking-wide">{{ __('Return') }}</small>
                                                <span class="fw-bold text-dark mt-1 text-nowrap"
                                                    dir="ltr">{{ $returnDateTimeFormatted }}</span>
                                                <span
                                                    class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill mt-2 px-2 py-1"
                                                    style="font-size:0.7rem;"><i
                                                        class="las la-exclamation-circle me-1"></i>{{ __('Extended') }}</span>
                                            </div>

                                        </div>
                                    @else
                                        {{-- ── Standard booking: single-segment timeline ── --}}
                                        <div
                                            class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-4 text-center text-md-start">

                                            <!-- Pick-up -->
                                            <div
                                                class="d-flex flex-column align-items-center align-items-md-start text-md-start position-relative z-1">
                                                <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow mb-3 border border-2 border-primary border-opacity-25"
                                                    style="width: 56px; height: 56px;">
                                                    <i class="las la-calendar-check text-primary fs-2"></i>
                                                </div>
                                                <small
                                                    class="text-muted text-uppercase fw-bold tracking-wide">{{ __('Pick-up') }}</small>
                                                <span class="fw-bold text-dark mt-1 text-nowrap"
                                                    dir="ltr">{{ $pickupDateTimeFormatted }}</span>
                                            </div>

                                            <!-- Separator & Rental Days -->
                                            <div
                                                class="flex-grow-1 d-flex flex-column align-items-center px-3 w-100 position-relative">
                                                <div class="d-flex align-items-center w-100 position-absolute top-50 start-50 translate-middle d-none d-md-flex"
                                                    style="z-index: 0;">
                                                    <div
                                                        class="border-top border-2 border-primary border-dashed opacity-50 flex-grow-1">
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-center w-100 position-absolute top-50 start-50 translate-middle d-md-none"
                                                    style="z-index: 0; height: 100%;">
                                                    <div
                                                        class="border-start border-2 border-primary border-dashed opacity-50 h-100">
                                                    </div>
                                                </div>
                                                <div
                                                    class="px-4 text-primary fw-bold small bg-white rounded-pill border border-2 border-primary border-opacity-25 shadow-sm py-2 mx-2 text-nowrap position-relative z-1 my-4 my-md-0">
                                                    <i
                                                        class="las la-stopwatch me-1 fs-5 align-middle"></i>{{ $rentalDays > 0 ? $rentalDays . ' ' . __('Days') : __('N/A') }}
                                                </div>
                                            </div>

                                            <!-- Return -->
                                            <div
                                                class="d-flex flex-column align-items-center align-items-md-end text-md-end position-relative z-1">
                                                <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow mb-3 border border-2 border-danger border-opacity-25"
                                                    style="width: 56px; height: 56px;">
                                                    <i class="las la-calendar-times text-danger fs-2"></i>
                                                </div>
                                                <small
                                                    class="text-muted text-uppercase fw-bold tracking-wide">{{ __('Return') }}</small>
                                                <span class="fw-bold text-dark mt-1 text-nowrap"
                                                    dir="ltr">{{ $returnDateTimeFormatted }}</span>
                                            </div>

                                        </div>
                                    @endif
                                </div>
                            </li>
                        </ul>

                        <hr class="my-5 border-secondary opacity-10">

                        {{-- ============================================================ --}}
                        {{-- PRICING BREAKDOWN — per-transaction table                    --}}
                        {{-- ============================================================ --}}
                        <div class="row g-4">
                            <div class="col-12">
                                <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                                    <i class="las la-file-invoice-dollar fs-4 text-primary"></i>
                                    {{ __('Pricing Breakdown') }}
                                </h5>

                                @if ($booking->bookingTransactions->isNotEmpty())
                                    <div class="d-none d-lg-block table-responsive rounded-4 border shadow-sm">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th
                                                        class="text-muted small fw-semibold ps-4 py-3 text-uppercase tracking-wide">
                                                        {{ __('Transaction Date') }}</th>
                                                    <th
                                                        class="text-muted small fw-semibold py-3 text-uppercase tracking-wide">
                                                        {{ __('Transaction Type') }}
                                                    </th>
                                                    <th
                                                        class="text-muted small fw-semibold py-3 text-uppercase tracking-wide">
                                                        {{ __('Description') }}</th>
                                                    <th
                                                        class="text-muted small fw-semibold text-end py-3 text-uppercase tracking-wide">
                                                        {{ __('Amount') }}
                                                    </th>
                                                    <th
                                                        class="text-muted small fw-semibold text-end py-3 text-uppercase tracking-wide">
                                                        {{ __('Tax') }}
                                                    </th>
                                                    <th
                                                        class="text-muted small fw-semibold text-end pe-4 py-3 text-uppercase tracking-wide">
                                                        {{ __('Total') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($booking->bookingTransactions as $trx)
                                                    <tr>
                                                        <td class="text-muted small ps-4 py-3">
                                                            {{ $trx->transacted_at?->format('d M Y') ?? '—' }}
                                                        </td>
                                                        <td class="py-3">
                                                            @if ($trx->type === 'rental')
                                                                <span class="badge rounded-pill px-3 py-2"
                                                                    style="background:rgba(var(--bs-primary-rgb),.12);color:var(--bs-primary)">
                                                                    <i class="las la-car me-1"></i>{{ __('Rental') }}
                                                                </span>
                                                            @elseif ($trx->type === 'extension')
                                                                <span class="badge rounded-pill px-3 py-2"
                                                                    style="background:rgba(13,202,240,.12);color:#0dcaf0">
                                                                    <i
                                                                        class="las la-calendar-plus me-1"></i>{{ __('Extension') }}
                                                                </span>
                                                            @elseif ($trx->type === 'delivery')
                                                                <span class="badge rounded-pill px-3 py-2"
                                                                    style="background:rgba(255,193,7,.15);color:#997404">
                                                                    <i class="las la-truck me-1"></i>{{ __('Delivery') }}
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td class="text-muted small py-3">{{ $trx->description ?? '—' }}
                                                        </td>
                                                        <td class="text-end fw-semibold py-3">
                                                            {{ $trx->amount !== null ? get_amount($trx->amount) . ' ' . $booking->currency : '—' }}
                                                        </td>
                                                        <td class="text-end small text-muted py-3">
                                                            @if (!is_null($trx->tax_amount) && $trx->tax_amount > 0)
                                                                {{ get_amount($trx->tax_amount) }}
                                                                @if ($trx->tax_percentage > 0)
                                                                    <span
                                                                        class="opacity-75">({{ $trx->tax_percentage }}%)</span>
                                                                @endif
                                                            @else
                                                                <span class="text-secondary">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-end fw-bold pe-4 py-3">
                                                            {{ $trx->total !== null ? get_amount($trx->total) . ' ' . $booking->currency : '—' }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-light border-top">
                                                    <td colspan="5"
                                                        class="fw-bold text-dark text-end py-3 text-uppercase tracking-wide">
                                                        {{ __('Grand Total') }}
                                                    </td>
                                                    <td class="text-end fw-bold text-primary pe-4 py-3 fs-5">
                                                        {{ get_amount($booking->total_amount) }}
                                                        {{ $booking->currency }}
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>

                                    {{-- Mobile Pricing Cards --}}
                                    <div class="d-lg-none d-flex flex-column gap-3">
                                        @foreach ($booking->bookingTransactions as $trx)
                                            <div class="card border shadow-sm rounded-4">
                                                <div class="card-body p-3">
                                                    <div
                                                        class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom border-light">
                                                        <div class="text-muted small">
                                                            <i class="las la-calendar me-1"></i>
                                                            {{ $trx->transacted_at?->format('d M Y') ?? '—' }}
                                                        </div>
                                                        <div>
                                                            @if ($trx->type === 'rental')
                                                                <span class="badge rounded-pill px-2 py-1"
                                                                    style="background:rgba(var(--bs-primary-rgb),.12);color:var(--bs-primary)">
                                                                    <i class="las la-car me-1"></i>{{ __('Rental') }}
                                                                </span>
                                                            @elseif ($trx->type === 'extension')
                                                                <span class="badge rounded-pill px-2 py-1"
                                                                    style="background:rgba(13,202,240,.12);color:#0dcaf0">
                                                                    <i
                                                                        class="las la-calendar-plus me-1"></i>{{ __('Extension') }}
                                                                </span>
                                                            @elseif ($trx->type === 'delivery')
                                                                <span class="badge rounded-pill px-2 py-1"
                                                                    style="background:rgba(255,193,7,.15);color:#997404">
                                                                    <i class="las la-truck me-1"></i>{{ __('Delivery') }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <small
                                                            class="text-muted d-block mb-1">{{ __('Description') }}</small>
                                                        <span class="fw-medium">{{ $trx->description ?? '—' }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <small class="text-muted">{{ __('Amount') }}</small>
                                                        <span
                                                            class="fw-semibold">{{ $trx->amount !== null ? get_amount($trx->amount) . ' ' . $booking->currency : '—' }}</span>
                                                    </div>
                                                    <div
                                                        class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom border-light">
                                                        <small class="text-muted">{{ __('Tax') }}</small>
                                                        <span class="small text-muted">
                                                            @if (!is_null($trx->tax_amount) && $trx->tax_amount > 0)
                                                                {{ get_amount($trx->tax_amount) }}
                                                                @if ($trx->tax_percentage > 0)
                                                                    <span
                                                                        class="opacity-75">({{ $trx->tax_percentage }}%)</span>
                                                                @endif
                                                            @else
                                                                <span class="text-secondary">—</span>
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="fw-bold text-dark">{{ __('Total') }}</span>
                                                        <span
                                                            class="fw-bold text-primary fs-6">{{ $trx->total !== null ? get_amount($trx->total) . ' ' . $booking->currency : '—' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                        <div class="card border-0 bg-primary bg-opacity-10 rounded-4 mt-2">
                                            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                                                <span
                                                    class="fw-bold text-primary text-uppercase tracking-wide">{{ __('Grand Total') }}</span>
                                                <span
                                                    class="fw-bold text-primary fs-4">{{ get_amount($booking->total_amount) }}
                                                    {{ $booking->currency }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    {{-- Fallback for bookings with no transaction rows --}}
                                    <div class="table-responsive rounded-3 border border-light shadow-sm">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th
                                                        class="text-muted small fw-semibold ps-4 py-3 text-uppercase tracking-wide">
                                                        {{ __('Description') }}</th>
                                                    <th
                                                        class="text-muted small fw-semibold text-end pe-4 py-3 text-uppercase tracking-wide">
                                                        {{ __('Amount') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="ps-4 py-3 fw-medium text-dark">{{ __('Trip Cost') }}</td>
                                                    <td class="text-end fw-semibold pe-4 py-3 text-dark">
                                                        {{ get_amount($booking->amount) }} {{ $booking->currency }}</td>
                                                </tr>
                                                @if (!empty($booking->charges) && $booking->charges > 0)
                                                    <tr>
                                                        <td class="text-muted small ps-4 py-3">{{ __('Charges') }}</td>
                                                        <td class="text-end fw-medium pe-4 py-3 text-dark">
                                                            {{ get_amount($booking->charges) }} {{ $booking->currency }}
                                                        </td>
                                                    </tr>
                                                @endif
                                                @if (!empty($booking->delivery_fee) && $booking->delivery_fee > 0)
                                                    <tr>
                                                        <td class="text-muted small ps-4 py-3">
                                                            <i
                                                                class="las la-truck me-2 text-primary fs-5 align-middle"></i>{{ __('Delivery Fee') }}
                                                        </td>
                                                        <td class="text-end fw-medium pe-4 py-3 text-dark">
                                                            {{ get_amount($booking->delivery_fee) }}
                                                            {{ $booking->currency }}</td>
                                                    </tr>
                                                @endif
                                                @if (!empty($booking->subtotal) && $booking->subtotal > 0)
                                                    <tr>
                                                        <td class="text-muted small ps-4 py-3">{{ __('Subtotal') }}</td>
                                                        <td class="text-end fw-medium pe-4 py-3 text-dark">
                                                            {{ get_amount($booking->subtotal) }}
                                                            {{ $booking->currency }}</td>
                                                    </tr>
                                                @endif
                                                @if (!empty($booking->tax_amount) && $booking->tax_amount > 0)
                                                    <tr>
                                                        <td class="text-muted small ps-4 py-3">
                                                            {{ __('Tax') }}
                                                            @if (!empty($booking->tax_percentage))
                                                                <span
                                                                    class="opacity-75 ms-1">({{ $booking->tax_percentage }}%)</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-end fw-medium pe-4 py-3 text-dark">
                                                            {{ get_amount($booking->tax_amount) }}
                                                            {{ $booking->currency }}</td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-light border-top border-light">
                                                    <td
                                                        class="fw-bold text-dark text-end py-3 pe-4 text-uppercase tracking-wide">
                                                        {{ __('Total Paid') }}</td>
                                                    <td class="text-end fw-bold text-primary pe-4 py-3 fs-5">
                                                        {{ get_amount($booking->total_amount) }}
                                                        {{ $booking->currency }}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                @endif
                            </div>

                            {{-- Additional Notes (full width, below table) --}}
                            <div class="col-12 mt-4">
                                <div class="card border-0 bg-light rounded-4">
                                    <div class="card-body p-4">
                                        <h6
                                            class="text-uppercase text-muted fw-bold small mb-3 d-flex align-items-center gap-2">
                                            <i class="las la-sticky-note fs-5"></i>
                                            {{ __('Additional Notes') }}
                                        </h6>
                                        <p class="text-dark mb-0 fst-italic lh-lg">
                                            {{ $booking->message ?? __('No additional notes provided by the customer.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($booking->status === 1)
        {{-- ============================================================ --}}
        {{-- REJECTION MODAL                                              --}}
        {{-- ============================================================ --}}
        @php
            $rejectReasons = [
                [
                    'label' => __('السيارة غير متاحة'),
                    'icon' => 'las la-car-side',
                    'id' => 'reason_car',
                ],
                [
                    'label' => __('يوجد تعارض في الحجز'),
                    'icon' => 'las la-calendar-times',
                    'id' => 'reason_conflict',
                ],
                [
                    'label' => __('معلومات العميل غير مكتملة'),
                    'icon' => 'las la-user-edit',
                    'id' => 'reason_info',
                ],
                [
                    'label' => __('لا توجد رخصة قيادة أو هوية وطنية'),
                    'icon' => 'las la-id-card',
                    'id' => 'reason_no_id',
                ],
                [
                    'label' => __('أخرى'),
                    'icon' => 'las la-ellipsis-h',
                    'id' => 'reasonOther',
                ],
            ];
        @endphp
        <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-xl" style="border-radius: 24px; overflow: hidden;">
                    <div class="modal-header border-0 pb-0 position-absolute top-0 end-0 z-3">
                        <button type="button" class="btn-close bg-white rounded-circle p-2 shadow-sm m-3"
                            data-bs-dismiss="modal" aria-label="Close"
                            style="opacity: 1; transition: transform 0.2s ease;"></button>
                    </div>
                    <form method="POST" action="{{ route('vendor.booking.reject', $booking->id) }}">
                        @csrf
                        <div class="modal-body p-0">
                            <!-- Header Section -->
                            <div class="bg-light px-4 pt-5 pb-4 text-center position-relative overflow-hidden">
                                <div class="position-absolute top-0 start-0 w-100 h-100"
                                    style="background: radial-gradient(circle at top right, rgba(220, 53, 69, 0.08), transparent 70%);">
                                </div>
                                <div class="d-inline-flex align-items-center justify-content-center bg-white text-danger rounded-circle shadow-sm mb-3 position-relative z-1"
                                    style="width: 72px; height: 72px;">
                                    <i class="las la-times-circle" style="font-size: 42px;"></i>
                                </div>
                                <h4 class="fw-bold mb-2 position-relative z-1" id="rejectModalLabel"
                                    style="letter-spacing: -0.5px;">{{ __('Decline Booking') }}</h4>
                                <p class="text-muted small mb-0 mx-auto position-relative z-1" dir="auto"
                                    style="max-width: 280px; line-height: 1.5;">
                                    {{ __('Please let us know why you cannot accept this booking. We will notify the customer gently.') }}
                                </p>
                            </div>

                            <!-- Form Section -->
                            <div class="px-4 py-4">
                                <div class="d-flex flex-column gap-3" id="rejectReasonGroup">
                                    @foreach ($rejectReasons as $reason)
                                        <label
                                            class="reject-card position-relative d-flex align-items-center gap-3 border rounded-4 p-3 cursor-pointer m-0">
                                            <input type="radio" name="reason" value="{{ $reason['label'] }}"
                                                class="d-none" @if ($reason['id'] === 'reasonOther') id="reasonOther" @endif
                                                required>

                                            <div class="reject-card-icon d-flex align-items-center justify-content-center rounded-circle bg-light text-secondary"
                                                style="width: 40px; height: 40px; transition: all 0.3s ease;">
                                                <i class="{{ $reason['icon'] }} fs-5"></i>
                                            </div>

                                            <div class="flex-grow-1">
                                                <span class="fw-semibold text-dark d-block"
                                                    style="font-size: 0.95rem;">{{ $reason['label'] }}</span>
                                            </div>

                                            <div class="reject-card-check text-danger opacity-0"
                                                style="transform: scale(0.5); transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
                                                <i class="las la-check-circle fs-4"></i>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>

                                <div class="mt-3" id="customReasonWrap"
                                    style="display:none; opacity: 0; transform: translateY(-10px); transition: all 0.3s ease;">
                                    <textarea name="custom_reason" id="custom_reason" class="form-control bg-light border-0 rounded-4 p-3"
                                        rows="3" placeholder="{{ __('Please provide more details...') }}"
                                        style="resize: none; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex flex-nowrap gap-3">
                            <button type="button" class="btn btn-secondary flex-fill py-3 fw-semibold rounded-pill"
                                data-bs-dismiss="modal" style="transition: all 0.2s ease;">{{ __('لا') }}</button>
                            <button type="submit"
                                class="btn btn-danger flex-fill py-3 fw-semibold rounded-pill shadow-sm"
                                style="transition: all 0.2s ease;">
                                {{ __('Confirm Decline') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="doc-lightbox" id="docLightbox" aria-hidden="true">
        <div class="doc-lightbox__backdrop" data-doc-close></div>
        <div class="doc-lightbox__panel" role="dialog" aria-modal="true" aria-label="Document preview">
            <button class="doc-lightbox__close" type="button" data-doc-close aria-label="Close preview">
                <i class="las la-times"></i>
            </button>
            <div class="doc-lightbox__header">
                <span class="doc-lightbox__title" id="docLightboxTitle"></span>
            </div>
            <div class="doc-lightbox__body">
                <img id="docLightboxImage" alt="">
                <div class="doc-lightbox__pdf-fallback"
                    style="display:none;flex-direction:column;align-items:center;justify-content:center;gap:12px;color:#e9eef7;text-align:center">
                    <i class="las la-file-pdf" style="font-size:3rem;opacity:.6"></i>
                    <span
                        style="font-size:.9rem;opacity:.7">{{ __('PDF preview is not available in the browser.') }}</span>
                    <a href="#" target="_blank" rel="noopener" download class="btn btn-sm btn-outline-light">
                        <i class="las la-download me-1"></i>{{ __('Download File') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <style>
        .activity-timeline {
            list-style: none;
            padding: 0;
        }

        .car-spec-badge {
            font-size: 0.95rem;
            font-weight: 600;
            padding: 0.5rem 0.75rem;
        }

        .car-preview-image {
            aspect-ratio: 16 / 9;
            width: 100%;
            object-fit: cover;
            border-radius: 0.5rem;
            background: #f8f9fa;
        }

        /* Timeline: allow date wrapping on narrow screens */
        .activity-timeline .text-nowrap {
            white-space: normal !important;
            word-break: break-word;
        }

        /* Timeline: prevent connector lines collapsing to zero */
        .activity-timeline .border-top.flex-grow-1 {
            min-width: 20px;
        }

        /* Reject Modal Delightful UI */
        .reject-card {
            transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
            background: #ffffff;
            border-color: #e9ecef !important;
        }

        .reject-card:hover {
            border-color: #dee2e6 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .reject-card:has(input:checked) {
            border-color: var(--bs-danger) !important;
            background: rgba(var(--bs-danger-rgb), 0.03);
            box-shadow: 0 0 0 1px var(--bs-danger);
        }

        .reject-card:has(input:checked) .reject-card-icon {
            background: var(--bs-danger) !important;
            color: #ffffff !important;
        }

        .reject-card:has(input:checked) .reject-card-check {
            opacity: 1 !important;
            transform: scale(1) !important;
        }

        .btn-close:hover {
            transform: rotate(90deg);
        }

        .modal-footer .btn:hover {
            transform: translateY(-2px);
        }

        .modal-footer .btn-danger:hover {
            box-shadow: 0 6px 16px rgba(var(--bs-danger-rgb), 0.3) !important;
        }

        .doc-lightbox {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 180ms ease;
        }

        .doc-lightbox.is-open {
            opacity: 1;
            pointer-events: auto;
        }

        .doc-lightbox__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 18, 24, 0.72);
            backdrop-filter: blur(3px);
            opacity: 0;
            transition: opacity 180ms ease;
        }

        .doc-lightbox.is-open .doc-lightbox__backdrop {
            opacity: 1;
        }

        .doc-lightbox__panel {
            position: relative;
            width: min(960px, 92vw);
            max-height: 88vh;
            background: #0f1218;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.45);
            overflow: hidden;
            color: #e9eef7;
            transform: translateY(16px) scale(0.98);
            transition: transform 180ms ease, opacity 180ms ease;
            opacity: 0;
            z-index: 1;
        }

        .doc-lightbox.is-open .doc-lightbox__panel {
            transform: translateY(0) scale(1);
            opacity: 1;
        }

        .doc-lightbox__header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 56px 12px 20px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0));
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .doc-lightbox__title {
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .doc-lightbox__body {
            padding: 16px;
            background: #0b0e14;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 320px;
            max-height: calc(88vh - 64px);
        }

        .doc-lightbox__body img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 12px;
            background: #121720;
        }

        .doc-lightbox__close {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 36px;
            height: 36px;
            border-radius: 999px;
            border: none;
            background: rgba(255, 255, 255, 0.12);
            color: #e9eef7;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background 150ms ease, transform 150ms ease;
        }

        .doc-lightbox__close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }
    </style>
@endpush

@push('script')
    <script>
        (function() {
            var lightbox = document.getElementById('docLightbox');
            if (!lightbox) {
                return;
            }

            var image = document.getElementById('docLightboxImage');
            var title = document.getElementById('docLightboxTitle');

            var pdfFallback = lightbox.querySelector('.doc-lightbox__pdf-fallback');

            var openLightbox = function(src, label) {
                if (!image) {
                    return;
                }
                var isPdf = src && src.toLowerCase().endsWith('.pdf');
                if (title) {
                    title.textContent = label || '';
                }
                if (isPdf) {
                    image.style.display = 'none';
                    if (pdfFallback) {
                        pdfFallback.querySelector('a').href = src;
                        pdfFallback.style.display = 'flex';
                    }
                } else {
                    image.src = src;
                    image.alt = label || '';
                    image.style.display = '';
                    if (pdfFallback) pdfFallback.style.display = 'none';
                }
                lightbox.classList.add('is-open');
                lightbox.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            };

            var closeLightbox = function() {
                lightbox.classList.remove('is-open');
                lightbox.setAttribute('aria-hidden', 'true');
                if (image) {
                    image.src = '';
                    image.style.display = '';
                }
                if (pdfFallback) pdfFallback.style.display = 'none';
                document.body.style.overflow = '';
            };

            document.querySelectorAll('.doc-preview').forEach(function(item) {
                item.addEventListener('click', function(event) {
                    event.preventDefault();
                    var src = item.getAttribute('href');
                    var label = item.getAttribute('data-doc-title');
                    if (src) {
                        openLightbox(src, label);
                    }
                });
            });

            lightbox.querySelectorAll('[data-doc-close]').forEach(function(closeBtn) {
                closeBtn.addEventListener('click', closeLightbox);
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && lightbox.classList.contains('is-open')) {
                    closeLightbox();
                }
            });
        })();

        // Rejection modal: show/hide custom reason textarea with animation
        (function() {
            var reasonOther = document.getElementById('reasonOther');
            var customWrap = document.getElementById('customReasonWrap');
            var customInput = document.getElementById('custom_reason');
            if (!reasonOther || !customWrap) return;

            document.querySelectorAll('input[name="reason"]').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    var isOther = reasonOther.checked;
                    if (isOther) {
                        customWrap.style.display = 'block';
                        // Small delay to allow display:block to apply before animating opacity
                        setTimeout(function() {
                            customWrap.style.opacity = '1';
                            customWrap.style.transform = 'translateY(0)';
                        }, 10);
                        if (customInput) customInput.required = true;
                    } else {
                        customWrap.style.opacity = '0';
                        customWrap.style.transform = 'translateY(-10px)';
                        setTimeout(function() {
                            customWrap.style.display = 'none';
                        }, 300); // match transition duration
                        if (customInput) customInput.required = false;
                    }
                });
            });

            var rejectModal = document.getElementById('rejectModal');
            if (rejectModal) {
                rejectModal.addEventListener('hidden.bs.modal', function() {
                    document.querySelectorAll('input[name="reason"]').forEach(function(r) {
                        r.checked = false;
                    });
                    customWrap.style.opacity = '0';
                    customWrap.style.transform = 'translateY(-10px)';
                    setTimeout(function() {
                        customWrap.style.display = 'none';
                    }, 300);
                    if (customInput) {
                        customInput.value = '';
                        customInput.required = false;
                    }
                });
            }
        })();
    </script>
@endpush
