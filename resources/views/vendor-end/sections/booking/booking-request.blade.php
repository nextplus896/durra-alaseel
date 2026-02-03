@extends('vendor-end.layouts.master')

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
                            <th>{{ __('Trx ID') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Car Model') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($car_bookings ?? [] as $item)
                            <tr>
                                <td data-label="{{ __('Trx ID') }}">
                                    <span class="text-primary fw-bold">#{{ $item->trx_id ?? $item->id }}</span>
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
                                <td data-label="{{ __('Car Model') }}">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-1 overflow-hidden border flex-shrink-0"
                                            style="width: 40px; height: 30px;">
                                            <img src="{{ get_image($item->cars->image ?? '', 'site-section') ?? '' }}"
                                                class="w-100 h-100 object-fit-cover">
                                        </div>
                                        <span class="fw-medium small text-dark">{{ $item->cars->car_model }}</span>
                                    </div>
                                </td>
                                <td data-label="{{ __('Status') }}">
                                    @php
                                        $statusClass = '';
                                        $statusText = '';
                                        if ($item->status == 1) {
                                            $statusClass = 'badge bg-warning text-dark';
                                            $statusText = __('Pending');
                                        } elseif ($item->status == 2) {
                                            $statusClass = 'badge bg-success';
                                            $statusText = __('Ongoing');
                                        } elseif ($item->status == 3) {
                                            $statusClass = 'badge bg-primary';
                                            $statusText = __('Completed');
                                        } elseif ($item->status == 4) {
                                            $statusClass = 'badge bg-danger';
                                            $statusText = __('Rejected');
                                        } else {
                                            $statusClass = 'badge bg-secondary';
                                            $statusText = __('Unknown');
                                        }
                                    @endphp
                                    <span class="{{ $statusClass }} rounded-pill px-3 py-1">{{ $statusText }}</span>
                                </td>
                                <td data-label="{{ __('Date') }}">
                                    <span class="text-muted small">{{ $item->created_at->format('d M Y') }}</span>
                                    <br>
                                    <span class="text-muted small"
                                        style="font-size: 10px;">{{ $item->created_at->format('h:i A') }}</span>
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
