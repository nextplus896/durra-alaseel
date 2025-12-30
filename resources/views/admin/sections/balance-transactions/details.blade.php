@extends('admin.layouts.master')

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
                'name' => __('Balance Transactions'),
                'url' => setRoute('admin.balance.transactions.index'),
            ],
        ],
        'active' => __('Details'),
    ])
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-6">
            <div class="custom-card">
                <div class="card-header">
                    <h6 class="title">{{ __('Transaction Details') }}</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('Transaction ID') }}</span>
                            <strong class="text-primary">{{ $transaction->trx_id }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('Type') }}</span>
                            <span
                                class="{{ $transaction->string_type->class }}">{{ $transaction->string_type->value }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('Amount') }}</span>
                            @if ($transaction->type == 'recharge' || $transaction->type == 'refund')
                                <strong
                                    class="text-success">+{{ get_default_currency_symbol() }}{{ number_format($transaction->amount, 2) }}</strong>
                            @else
                                <strong
                                    class="text-danger">-{{ get_default_currency_symbol() }}{{ number_format($transaction->amount, 2) }}</strong>
                            @endif
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('Balance Before') }}</span>
                            <span>{{ get_default_currency_symbol() }}{{ number_format($transaction->balance_before, 2) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('Balance After') }}</span>
                            <span>{{ get_default_currency_symbol() }}{{ number_format($transaction->balance_after, 2) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('Payment Method') }}</span>
                            <span>{{ ucfirst($transaction->payment_method ?? '-') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('Status') }}</span>
                            <span
                                class="{{ $transaction->string_status->class }}">{{ $transaction->string_status->value }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('Date') }}</span>
                            <span>{{ $transaction->created_at->format('d M Y H:i:s') }}</span>
                        </li>
                        @if ($transaction->description)
                            <li class="list-group-item">
                                <span class="d-block mb-2">{{ __('Description') }}</span>
                                <p class="text-muted mb-0">{{ $transaction->description }}</p>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="custom-card">
                <div class="card-header">
                    <h6 class="title">{{ __('User Information') }}</h6>
                </div>
                <div class="card-body">
                    @if ($transaction->user)
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ __('Name') }}</span>
                                <strong>{{ $transaction->user->fullname }}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ __('Username') }}</span>
                                <span>{{ $transaction->user->username }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ __('Email') }}</span>
                                <span>{{ $transaction->user->email }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ __('Phone') }}</span>
                                <span>{{ $transaction->user->full_mobile ?? '-' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ __('Current Balance') }}</span>
                                <strong
                                    class="text-primary">{{ get_default_currency_symbol() }}{{ number_format($transaction->user->balance, 2) }}</strong>
                            </li>
                        </ul>
                        <div class="mt-3">
                            <a href="{{ setRoute('admin.users.details', $transaction->user->username) }}"
                                class="btn btn--base w-100">{{ __('View User Details') }}</a>
                        </div>
                    @else
                        <div class="alert alert-warning">{{ __('User information not available') }}</div>
                    @endif
                </div>
            </div>

            @if ($transaction->booking)
                <div class="custom-card mt-4">
                    <div class="card-header">
                        <h6 class="title">{{ __('Related Booking') }}</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ __('Trip ID') }}</span>
                                <strong>{{ $transaction->booking->trip_id }}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ __('Pickup Date') }}</span>
                                <span>{{ $transaction->booking->pickup_date }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ __('Amount') }}</span>
                                <span>{{ get_default_currency_symbol() }}{{ number_format($transaction->booking->total_amount, 2) }}</span>
                            </li>
                        </ul>
                        <div class="mt-3">
                            <a href="{{ setRoute('admin.booking.details', $transaction->booking->trip_id) }}"
                                class="btn btn--base w-100">{{ __('View Booking Details') }}</a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
