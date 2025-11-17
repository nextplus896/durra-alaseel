@extends('admin.layouts.master')

@push('css')
    <style>
        .stat-card {
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stat-card h4 {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-card .sub-value {
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
        }

        .channel-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-sms {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-whatsapp {
            background: #e8f5e9;
            color: #388e3c;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-delivered {
            background: #d4edda;
            color: #155724;
        }

        .status-sent {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
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
        ],
        'active' => __('Twilio Usage'),
    ])
@endsection

@section('content')
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __('Twilio SMS & WhatsApp Usage') }}</h6>
        </div>
        <div class="card-body">
            {{-- Current Balance Alert --}}
            <div class="row mb-4">
                <div class="col-xl-12">
                    <div class="stat-card"
                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                        <h4 style="color: rgba(255,255,255,0.8); margin-bottom: 15px;">{{ __('Current Account Balance') }}
                        </h4>
                        <div class="value" style="color: white; font-size: 42px;">
                            {{ $statistics['balance_currency'] }} {{ number_format($statistics['balance'], 2) }}
                        </div>
                        <div class="sub-value" style="color: rgba(255,255,255,0.8); margin-top: 10px;">
                            @if ($statistics['balance'] > 50)
                                <span class="badge"
                                    style="background: rgba(255,255,255,0.3); padding: 8px 12px; border-radius: 20px;">
                                    ✓ Good balance
                                </span>
                            @elseif($statistics['balance'] > 10)
                                <span class="badge"
                                    style="background: rgba(255,200,0,0.3); padding: 8px 12px; border-radius: 20px; color: #fff;">
                                    ⚠ Low balance
                                </span>
                            @else
                                <span class="badge"
                                    style="background: rgba(255,100,100,0.3); padding: 8px 12px; border-radius: 20px; color: #fff;">
                                    ✕ Critical
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Date Filter --}}
            <div class="row mb-4">
                <div class="col-xl-12">
                    <form action="{{ route('admin.twilio.usage.index') }}" method="GET">
                        <div class="row align-items-end">
                            <div class="col-xl-3 col-lg-3 form-group">
                                <label>{{ __('Start Date') }}</label>
                                <input type="date" class="form-control" name="start_date" value="{{ $startDate }}"
                                    required>
                            </div>
                            <div class="col-xl-3 col-lg-3 form-group">
                                <label>{{ __('End Date') }}</label>
                                <input type="date" class="form-control" name="end_date" value="{{ $endDate }}"
                                    required>
                            </div>
                            <div class="col-xl-3 col-lg-3 form-group">
                                <button type="submit" class="btn btn--primary w-100">{{ __('Filter') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Statistics Summary --}}
            <div class="row">
                <div class="col-xl-12 mb-3">
                    <h5>{{ __('SMS Statistics') }}</h5>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                    <div class="stat-card">
                        <h4>{{ __('Total SMS Sent') }}</h4>
                        <div class="value">{{ $statistics['sms']['total'] }}</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                    <div class="stat-card">
                        <h4>{{ __('Delivered') }}</h4>
                        <div class="value text-success">{{ $statistics['sms']['delivered'] }}</div>
                        <div class="sub-value">{{ $statistics['sms']['delivery_rate'] }}% delivery rate</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                    <div class="stat-card">
                        <h4>{{ __('Failed') }}</h4>
                        <div class="value text-danger">{{ $statistics['sms']['failed'] }}</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                    <div class="stat-card">
                        <h4>{{ __('Total Cost (USD)') }}</h4>
                        <div class="value">${{ number_format($statistics['sms']['cost'], 2) }}</div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-xl-12 mb-3">
                    <h5>{{ __('WhatsApp Statistics') }}</h5>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                    <div class="stat-card">
                        <h4>{{ __('Total WhatsApp Sent') }}</h4>
                        <div class="value">{{ $statistics['whatsapp']['total'] }}</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                    <div class="stat-card">
                        <h4>{{ __('Delivered') }}</h4>
                        <div class="value text-success">{{ $statistics['whatsapp']['delivered'] }}</div>
                        <div class="sub-value">{{ $statistics['whatsapp']['delivery_rate'] }}% delivery rate</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                    <div class="stat-card">
                        <h4>{{ __('Failed') }}</h4>
                        <div class="value text-danger">{{ $statistics['whatsapp']['failed'] }}</div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                    <div class="stat-card">
                        <h4>{{ __('Total Cost (USD)') }}</h4>
                        <div class="value">${{ number_format($statistics['whatsapp']['cost'], 2) }}</div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-xl-12">
                    <div class="stat-card bg-primary text-white">
                        <h4 class="text-white">{{ __('Overall Total Cost') }}</h4>
                        <div class="value text-white">${{ number_format($statistics['total_cost'], 2) }}</div>
                    </div>
                </div>
            </div>

            {{-- Recent Messages Table --}}
            <div class="row mt-5">
                <div class="col-xl-12">
                    <h5 class="mb-3">{{ __('Recent Messages') }} ({{ __('Last 50') }})</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('To') }}</th>
                                    <th>{{ __('Channel') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Message SID') }}</th>
                                    <th>{{ __('Cost') }}</th>
                                    <th>{{ __('Error') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($statistics['recent_messages'] as $message)
                                    <tr>
                                        <td>{{ $message->created_at->format('Y-m-d H:i') }}</td>
                                        <td>{{ $message->to }}</td>
                                        <td>
                                            <span class="channel-badge badge-{{ $message->channel }}">
                                                {{ strtoupper($message->channel) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-{{ $message->status ?? 'pending' }}">
                                                {{ strtoupper($message->status ?? 'PENDING') }}
                                            </span>
                                        </td>
                                        <td><small>{{ $message->message_sid }}</small></td>
                                        <td>
                                            @if ($message->price)
                                                ${{ number_format($message->price, 4) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if ($message->error_code)
                                                <small class="text-danger">{{ $message->error_code }}:
                                                    {{ Str::limit($message->error_message, 30) }}</small>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">{{ __('No messages found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
@endpush
