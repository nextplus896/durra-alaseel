@extends('admin.layouts.master')

@push('css')
    <style>
        .day-group {
            margin-bottom: 20px;
        }

        .day-group .day-header {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .slot-row {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 8px 15px;
            border-bottom: 1px solid #eee;
        }

        .slot-row:last-child {
            border-bottom: none;
        }

        .slot-time {
            font-size: 15px;
            font-weight: 500;
            min-width: 160px;
        }

        .slot-status {
            min-width: 100px;
        }

        .no-slots {
            color: #999;
            font-style: italic;
            padding: 8px 15px;
        }

        .add-slot-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
    </style>
@endpush

@section('page-title')
    @include('admin.components.page-title', ['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb', [
        'breadcrumbs' => [
            [
                'name' => __('Dashboard'),
                'url' => setRoute('admin.dashboard'),
            ],
            [
                'name' => __('Branch Management'),
                'url' => setRoute('admin.branch.index'),
            ],
        ],
        'active' => __('Working Hours'),
    ])
@endsection

@section('content')
    {{-- Add Time Slot Form --}}
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __('Add Time Slot') }}</h6>
        </div>
        <div class="card-body">
            <div class="add-slot-form">
                <form action="{{ setRoute('admin.branch.working.hours.store', $branch->id) }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-xl-4 col-lg-4 form-group">
                            <label>{{ __('Day') }}<span>*</span></label>
                            <select name="day_of_week" class="form--control" required>
                                @foreach ($saudiOrder as $dayNum)
                                    <option value="{{ $dayNum }}"
                                        {{ old('day_of_week') == $dayNum ? 'selected' : '' }}>
                                        {{ $dayNames[$dayNum] }} ({{ $dayNamesEn[$dayNum] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-3 col-lg-3 form-group">
                            <label>{{ __('Open Time') }}<span>*</span></label>
                            <input type="time" name="open_time" class="form--control" value="{{ old('open_time') }}"
                                required>
                        </div>
                        <div class="col-xl-3 col-lg-3 form-group">
                            <label>{{ __('Close Time') }}<span>*</span></label>
                            <input type="time" name="close_time" class="form--control" value="{{ old('close_time') }}"
                                required>
                        </div>
                        <div class="col-xl-2 col-lg-2 form-group d-flex align-items-end">
                            <button type="submit" class="btn btn--base w-100">
                                <i class="las la-plus"></i> {{ __('Add') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="custom-card mt-3">
        <div class="card-header">
            <h6 class="title">{{ __('Working Hours') }} - {{ $branch->name }}</h6>
        </div>
        <div class="card-body">
            {{-- Current Working Hours --}}
            @foreach ($saudiOrder as $dayNum)
                @php
                    $daySlots = $branch->workingHours->where('day_of_week', $dayNum)->sortBy('open_time');
                @endphp
                <div class="day-group">
                    <div class="day-header">
                        <span>{{ $dayNames[$dayNum] }} ({{ $dayNamesEn[$dayNum] }})</span>
                        <span
                            class="badge {{ $daySlots->where('is_enabled', true)->count() > 0 ? 'badge--success' : 'badge--danger' }}">
                            {{ $daySlots->where('is_enabled', true)->count() > 0 ? __('Open') : __('Closed') }}
                        </span>
                    </div>
                    @forelse ($daySlots as $slot)
                        <div class="slot-row">
                            <div class="slot-time">
                                <i class="las la-clock"></i>
                                {{ \Carbon\Carbon::parse($slot->open_time)->format('h:i A') }}
                                —
                                {{ \Carbon\Carbon::parse($slot->close_time)->format('h:i A') }}
                            </div>
                            <div class="slot-status">
                                @if ($slot->is_enabled)
                                    <span class="badge badge--success">{{ __('Enabled') }}</span>
                                @else
                                    <span class="badge badge--warning">{{ __('Disabled') }}</span>
                                @endif
                            </div>
                            <div class="slot-actions d-flex gap-2">
                                <button class="btn btn--sm btn--base toggle-slot-btn" data-id="{{ $slot->id }}"
                                    data-enabled="{{ $slot->is_enabled ? '1' : '0' }}">
                                    <i class="las {{ $slot->is_enabled ? 'la-toggle-on' : 'la-toggle-off' }}"></i>
                                </button>
                                <form action="{{ setRoute('admin.branch.working.hours.delete', $slot->id) }}"
                                    method="POST" class="d-inline"
                                    onsubmit="return confirm('{{ __('Are you sure you want to delete this time slot?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn--sm btn--danger">
                                        <i class="las la-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="no-slots">{{ __('No time slots — branch is closed this day') }}</div>
                    @endforelse
                </div>
            @endforeach
        </div>
    </div>
@endsection

@push('script')
    <script>
        $(document).ready(function() {
            $('.toggle-slot-btn').click(function() {
                var btn = $(this);
                var slotId = btn.data('id');
                var url = "{{ setRoute('admin.branch.working.hours.toggle', ':id') }}".replace(':id',
                    slotId);

                $.ajax({
                    url: url,
                    type: 'PUT',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.type === 'success') {
                            location.reload();
                        }
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON?.message?.error?.[0] ||
                            '{{ __('Something went wrong!') }}';
                        throwMessage('error', [msg]);
                    }
                });
            });
        });
    </script>
@endpush
