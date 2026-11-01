<?php
$default = get_default_language_code() ?? 'en';
$default_lng = 'en';
?>
@extends('vendor-end.layouts.master')
@section('content')
    <div class="ptb-20 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="branch-filter-box d-flex align-items-center">
            <form action="{{ setRoute('vendor.car.index') }}" method="GET" class="d-flex align-items-center flex-wrap gap-2" id="branchFilterForm">
                <label class="mb-0 fw-bold me-2"><i class="las la-filter"></i> {{ __('Branch') }}:</label>
                <div style="min-width: 220px;">
                    <select class="form-control form--control" name="branch_id" onchange="document.getElementById('branchFilterForm').submit();">
                        <option value="">{{ __('All Branches') }}</option>
                        @foreach ($branches ?? [] as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if (request()->filled('branch_id'))
                    <a href="{{ setRoute('vendor.car.index') }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Clear Filter') }}">
                        <i class="las la-times"></i>
                    </a>
                @endif
            </form>
        </div>
        <div class="selon-add-btn">
            <a href="{{ setRoute('vendor.car.create') }}" class="btn--base"><i class="las la-plus"></i> {{ __('Add car') }}</a>
        </div>
    </div>
    <div class="my-car-details">
        <div class="row justify-content-center mb-20-none">
            @forelse ($cars ?? [] as $car)
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 mb-20">
                    <div class="car-item">
                        <div class="car-img">
                            <img src="{{ $car->image_url }}" alt="img">
                            @if ($car->approval === 0)
                                <span class="booking-status bg-danger">{{ __('Not Approved') }}</span>
                            @else
                                @if (App\Models\CarBooking::where('car_id', $car->id)->where('status', 2)->count() > 0)
                                    <span class="booking-status">{{ __('Booked') }}</span>
                                @endif
                            @endif
                            <div class="car-status">
                                <div class="switch-field">
                                    @include('vendor-end.components.form.switcher', [
                                        'name' => 'currency_status',
                                        'value' => $car->status,
                                        'options' => [__('Enable') => 1, __('Disable') => 0],
                                        'onload' => true,
                                        'data_target' => $car->id,
                                        'permission' => 'vendor.car.status.update',
                                    ])
                                </div>
                            </div>
                        </div>
                        <div class="car-details"
                            style="{{ $car->status ? 'pointerEvents: auto; opacity: 1;' : 'pointerEvents: none; opacity:0.3' }}">
                            <h2 class="title"
                                style="font-size: 1.5rem; font-weight: 700; margin-bottom: 12px; color: #333;">
                                {{ $car->car_title->$default->car_title ?? ($car->type->name ?? '') . ' ' . ($car->carModel->name ?? '') }}
                            </h2>
                            <div class="car-info-grid"
                                style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 15px;">
                                <div class="info-item">
                                    <small
                                        style="color: #888; font-size: 0.75rem; display: block;">{{ __('Vehicle Type') }}</small>
                                    <span style="font-weight: 500; color: #555;">{{ $car->type->name ?? '-' }}</span>
                                </div>
                                <div class="info-item">
                                    <small
                                        style="color: #888; font-size: 0.75rem; display: block;">{{ __('Model') }}</small>
                                    <span style="font-weight: 500; color: #555;">{{ $car->carModel->name ?? '-' }}</span>
                                </div>
                                <div class="info-item">
                                    <small
                                        style="color: #888; font-size: 0.75rem; display: block;">{{ __('Year') }}</small>
                                    <span style="font-weight: 500; color: #555;">{{ $car->year ?? '-' }}</span>
                                </div>
                                <div class="info-item">
                                    <small
                                        style="color: #888; font-size: 0.75rem; display: block;">{{ __('Seats') }}</small>
                                    <span style="font-weight: 500; color: #555;">{{ $car->seat ?? '-' }}</span>
                                </div>
                                <div class="info-item" style="grid-column: span 2;">
                                    <small
                                        style="color: #888; font-size: 0.75rem; display: block;"><i class="las la-map-marker-alt"></i> {{ __('Branch') }}</small>
                                    <span style="font-weight: 600; color: #2d3748;">{{ $car->branch->name ?? __('No Branch Assigned') }}</span>
                                </div>
                            </div>
                            <div class="price-tag"
                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 10px 15px; border-radius: 8px; text-align: center; margin-bottom: 15px;">
                                <small style="display: block; opacity: 0.9;">{{ __('Price Per Day') }}</small>
                                <strong style="font-size: 1.25rem;">{{ get_amount($car->fees) }}
                                    {{ get_default_currency_code() }}</strong>
                            </div>
                            <div class="control-btn pt-2 mb-10-none justify-content-sm-between d-flex">
                                <button type="button" class="delate-btn delete-modal-button mb-10" data-bs-toggle="modal"
                                    data-bs-target="#delateModal"
                                    data-item="{{ $car->id }}">{{ __('Delete') }}</button>
                                <a href="{{ setRoute('vendor.car.edit', $car->id) }}"
                                    class="edit-btn mb-10">{{ __('Edit') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="dashboard-list-item-wrapper text-center">
                    <h4>{{ __('No car added yet') }}!</h4>
                </div>
            @endforelse
        </div>
    </div>
    {{ $cars->links() }}
@endsection

@push('script')
    <script>
        $('.delete-modal-button').click(function() {
            var oldData = JSON.parse($(this).attr("data-item"));
            var actionRoute = "{{ setRoute('vendor.car.delete') }}";
            var target = oldData;
            var message = `Are you sure to <strong>delete</strong> this car?`;
            console.log(oldData);
            openDeleteModal(actionRoute, target, message);
        });
    </script>
    <script>
        $(document).ready(function() {
            // Switcher
            switcherAjax("{{ setRoute('vendor.car.status.update') }}");
        })
    </script>
@endpush
