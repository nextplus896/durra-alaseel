@extends('vendor-end.layouts.master')

@section('content')
    <div class="booking-details pt-40">
        <div class="row mb-4">
            <div class="col-12 d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <h3 class="title mb-1">{{ __('Booking Details') }} #{{ $booking->trx_id ?? $booking->id }}</h3>
                    <p class="text-muted small mb-0">{{ $booking->created_at->format('d M Y, h:i A') }}</p>
                </div>
                <div class="d-flex gap-2 mt-3 mt-md-0">
                    <a href="{{ route('vendor.booking.index') }}" class="btn btn-outline-secondary">
                        <i class="las la-arrow-left me-1"></i> {{ __('Back to List') }}
                    </a>

                    @if ($booking->status === 1)
                        <a href="{{ route('vendor.booking.accept', $booking->id) }}" class="btn btn-success">
                            <i class="las la-check-circle me-1"></i> {{ __('Accept Request') }}
                        </a>
                        <a href="{{ route('vendor.booking.reject', $booking->id) }}" class="btn btn-danger">
                            <i class="las la-times-circle me-1"></i> {{ __('Reject') }}
                        </a>
                    @elseif ($booking->status === 2)
                        <a href="{{ route('vendor.booking.complete', $booking->id) }}" class="btn btn-primary">
                            <i class="las la-check-double me-1"></i> {{ __('Complete') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- Left Column: Customer & Car --}}
            <div class="col-lg-4">
                {{-- Status Card --}}
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted fw-bold small mb-3">{{ __('Status') }}</h6>
                        @if ($booking->status === 1)
                            <div class="alert alert-warning d-flex align-items-center mb-0 border-0 text-dark">
                                <i class="las la-clock fs-4 me-2"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold">{{ __('Pending Approval') }}</h6>
                                    <small>{{ __('Waiting for your confirmation') }}</small>
                                </div>
                            </div>
                        @elseif($booking->status === 2)
                            <div class="alert alert-success d-flex align-items-center mb-0 border-0 text-white bg-success">
                                <i class="las la-running fs-4 me-2"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold">{{ __('Ongoing') }}</h6>
                                    <small>{{ __('Trip is currently in progress') }}</small>
                                </div>
                            </div>
                        @elseif($booking->status === 3)
                            <div class="alert alert-secondary d-flex align-items-center mb-0 border-0">
                                <i class="las la-check fs-4 me-2"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold">{{ __('Completed') }}</h6>
                                    <small>{{ __('Trip finished successfully') }}</small>
                                </div>
                            </div>
                        @elseif($booking->status === 4)
                            <div class="alert alert-danger d-flex align-items-center mb-0 border-0">
                                <i class="las la-times fs-4 me-2"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold">{{ __('Cancelled') }}</h6>
                                    <small>{{ __('Trip was cancelled') }}</small>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Customer Info --}}
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted fw-bold small mb-3">{{ __('Customer Information') }}</h6>
                        @if ($booking->user)
                            <div class="text-center mb-3">
                                <div class="mx-auto rounded-circle overflow-hidden border mb-2"
                                    style="width: 80px; height: 80px;">
                                    @if ($booking->user->image)
                                        <img src="{{ get_image($booking->user->image, 'user-profile') }}"
                                            class="w-100 h-100 object-fit-cover">
                                    @else
                                        <div
                                            class="w-100 h-100 bg-light d-flex align-items-center justify-content-center fw-bold fs-2 text-primary">
                                            {{ substr($booking->user->firstname ?? 'U', 0, 1) }}
                                        </div>
                                    @endif
                                </div>
                                <h5 class="fw-bold mb-1">{{ $booking->user->fullname }}</h5>
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    @php
                                        $kycStatus = $booking->user->kyc_verified;
                                        $kycObject = $booking->user->kycStringStatus;
                                        $kycLabel = is_object($kycObject)
                                            ? $kycObject->value ?? 'Unverified'
                                            : $kycObject ?? 'Unverified';
                                    @endphp
                                    <span
                                        class="badge {{ $kycStatus == 1 ? 'bg-success' : 'bg-warning text-dark' }} rounded-pill">
                                        {{ $kycLabel }}
                                    </span>
                                </div>
                            </div>

                            <div class="list-group list-group-flush small">
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted"><i class="las la-envelope me-1"></i>
                                        {{ __('Email') }}</span>
                                    <span class="fw-medium text-break">{{ $booking->user->email }}</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted"><i class="las la-phone me-1"></i> {{ __('Phone') }}</span>
                                    <span
                                        class="fw-medium">{{ $booking->user->full_mobile ?? $booking->user->mobile }}</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted"><i class="las la-id-card me-1"></i> {{ __('License') }}</span>
                                    <span class="fw-medium">{{ $booking->user->driving_license ?? 'N/A' }}</span>
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
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted fw-bold small mb-3">{{ __('Car Details') }}</h6>
                        <div class="rounded-3 overflow-hidden mb-3">
                            <img src="{{ get_image($booking->cars->image ?? '', 'site-section') ?? '' }}" alt="car"
                                class="w-100 object-fit-cover" style="height: 180px;">
                        </div>
                        <h5 class="fw-bold mb-1">{{ $booking->cars->car_model }}</h5>
                        <p class="text-muted small mb-2">{{ $booking->cars->car_number }}</p>

                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <span class="badge bg-light text-dark border"><i class="las la-gas-pump text-primary me-1"></i>
                                {{ $booking->cars->fuel_type ?? 'N/A' }}</span>
                            <span class="badge bg-light text-dark border"><i class="las la-users text-primary me-1"></i>
                                {{ $booking->cars->seat ?? 'N/A' }} Seats</span>
                            <span class="badge bg-light text-dark border"><i
                                    class="las la-tachometer-alt text-primary me-1"></i>
                                {{ $booking->cars->top_speed ? $booking->cars->top_speed . ' km/h' : 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Trip Info --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold mb-4">{{ __('Journey Details') }}</h5>

                        <ul class="activity-timeline ms-3">
                            <li class="ps-4 position-relative">
                                <span
                                    class="position-absolute top-0 start-0 translate-middle p-2 bg-success border border-white rounded-circle"></span>
                                <p class="text-muted small mb-1 text-uppercase fw-bold">{{ __('Pick-up Location') }}</p>
                                <h6 class="mb-2">{{ $booking->location }}</h6>
                                <a href="https://maps.google.com/?q={{ urlencode($booking->location) }}" target="_blank"
                                    class="btn btn-sm btn-light border">
                                    <i class="las la-map-marked-alt text-primary me-1"></i> {{ __('View on Map') }}
                                </a>
                            </li>
                        </ul>

                        <hr class="my-5 border-secondary opacity-10">

                        <div class="row g-4">
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted fw-bold small mb-3">{{ __('Pricing Breakdown') }}
                                </h6>
                                <div class="bg-light p-3 rounded-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>{{ __('Trip Cost') }}</span>
                                        <span class="fw-bold">{{ get_amount($booking->amount) }}
                                            {{ $booking->currency }}</span>
                                    </div>
                                    {{-- Add more breakdown if available --}}
                                    <div class="border-top my-2"></div>
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold text-dark">{{ __('Total Paid') }}</span>
                                        <span class="fw-bold text-primary">{{ get_amount($booking->total_amount) }}
                                            {{ $booking->currency }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted fw-bold small mb-3">{{ __('Additional Notes') }}</h6>
                                <p class="text-muted bg-light p-3 rounded-3 mb-0 fst-italic">
                                    {{ $booking->message ?? __('No additional notes provided by the customer.') }}
                                </p>
                            </div>
                        </div>
                    </div>
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
    </style>
@endpush
