@extends('vendor-end.layouts.master')

@push('css')
    <style>
        .status-badge-pending {
            background-color: #fff3cd !important;
            color: #7d4e00 !important;
            font-size: 1rem !important;
        }

        .status-badge-ongoing {
            background-color: #d1e7dd !important;
            color: #0b4d1e !important;
            font-size: 1rem !important;
        }

        .status-badge-completed {
            background-color: #cfe2ff !important;
            color: #09447a !important;
            font-size: 1rem !important;
        }

        .status-badge-rejected {
            background-color: #f8d7da !important;
            color: #7d0d0d !important;
            font-size: 1rem !important;
        }

        .status-badge-unknown {
            background-color: #e2e3e5 !important;
            color: #2b2f33 !important;
            font-size: 1rem !important;
        }
    </style>
@endpush

@section('content')
    <div class="booking-request pt-40">
        {{-- Header & Toolbar --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 border-bottom pb-4">
            <h3 class="title mb-3 mb-md-0">{{ __('Booking Request') }}</h3>

            <form action="" method="GET" class="d-flex flex-wrap gap-2">
                <div class="input-group" style="width: 280px;">
                    <span class="input-group-text bg-white border-end-0"><i class="las la-search text-muted"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="form-control border-start-0 ps-0" placeholder="{{ __('Search by ID, Name...') }}">
                </div>

                <select name="status" class="form-select w-auto" onchange="this.form.submit()">
                    <option value="">{{ __('All Status') }}</option>
                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                    <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>{{ __('Ongoing') }}</option>
                </select>
            </form>
        </div>

        <div class="table-area">
            <div class="table-responsive">
                <table class="custom-table table">
                    <thead>
                        <tr>
                            <th>{{ __('Booking ID') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Car Type') }}</th>
                            <th>{{ __('Car Model') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($car_bookings ?? [] as $item)
                            <tr>
                                <td data-label="{{ __('Booking ID') }}">
                                    <span class="text-primary fw-bold">{{ $item->trip_id ?? $item->id }}</span>
                                </td>
                                <td data-label="{{ __('Date') }}">
                                    <span class="text-muted small"
                                        title="{{ $item->created_at->timezone($display_timezone)->format('d M Y h:i A') }}">
                                        {{ $item->created_at->diffForHumans() }}
                                    </span>
                                </td>
                                <td data-label="{{ __('Customer') }}">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle overflow-hidden border flex-shrink-0"
                                            style="width: 35px; height: 35px;">
                                            @if ($item->user && $item->user->image)
                                                <img src="{{ get_image($item->user->image, 'user-profile') }}"
                                                    class="w-100 h-100 object-fit-cover">
                                            @else
                                                <div
                                                    class="w-100 h-100 bg-light d-flex align-items-center justify-content-center fw-bold text-muted small">
                                                    {{ substr($item->user->firstname ?? 'U', 0, 1) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <span
                                                class="d-block fw-medium small text-dark">{{ $item->user->fullname ?? 'Guest' }}</span>
                                            <span class="d-block small text-muted"
                                                style="font-size: 11px;">{{ $item->user->email ?? $item->email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="{{ __('Car Type') }}">
                                    <span class="fw-medium small text-dark">
                                        {{ $item->cars->type->name ?? __('N/A') }}
                                    </span>
                                </td>
                                <td data-label="{{ __('Car Model') }}">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-1 overflow-hidden border flex-shrink-0"
                                            style="width: 40px; height: 30px;">
                                            <img src="{{ get_image($item->cars->image ?? null, 'car-models') }}"
                                                class="w-100 h-100 object-fit-cover">
                                        </div>
                                        <span class="fw-medium small text-dark">
                                            {{ $item->cars->carModel->name ?? ($item->cars->car_model ?? __('N/A')) }}
                                        </span>
                                    </div>
                                </td>
                                <td data-label="{{ __('Status') }}">
                                    @php
                                        $statusClass = '';
                                        $statusText = '';
                                        if ($item->status == 1) {
                                            $statusClass = 'badge status-badge-pending';
                                            $statusText = __('Pending');
                                        } elseif ($item->status == 2) {
                                            $statusClass = 'badge status-badge-ongoing';
                                            $statusText = __('Ongoing');
                                        } elseif ($item->status == 3) {
                                            $statusClass = 'badge status-badge-completed';
                                            $statusText = __('Completed');
                                        } elseif ($item->status == 4) {
                                            $statusClass = 'badge status-badge-rejected';
                                            $statusText = __('Rejected');
                                        } else {
                                            $statusClass = 'badge status-badge-unknown';
                                            $statusText = __('Unknown');
                                        }
                                    @endphp
                                    <span class="{{ $statusClass }} rounded-pill px-3 py-1">{{ $statusText }}</span>
                                </td>
                                <td data-label="{{ __('Action') }}">
                                    <a href="{{ route('vendor.booking.details', $item->id) }}"
                                        class="btn btn--base btn-sm btn-icon" title="{{ __('View Details') }}">
                                        <i class="las la-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="100%" class="text-center">{{ __('No booking requests found!') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $car_bookings->links() }}
            </div>
        </div>
    </div>
@endsection
