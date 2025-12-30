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
        ],
        'active' => __('Balance Transaction Logs'),
    ])
@endsection

@section('content')
    <div class="table-area">
        <div class="table-wrapper">
            <div class="table-header">
                <h5 class="title">{{ __($page_title) }}</h5>
                <div class="table-btn-area">
                    <div class="btn-group">
                        <a href="{{ setRoute('admin.balance.transactions.index') }}"
                            class="btn btn--base {{ request()->routeIs('admin.balance.transactions.index') ? 'active' : '' }}">{{ __('All') }}</a>
                        <a href="{{ setRoute('admin.balance.transactions.recharges') }}"
                            class="btn btn--base {{ request()->routeIs('admin.balance.transactions.recharges') ? 'active' : '' }}">{{ __('Recharges') }}</a>
                        <a href="{{ setRoute('admin.balance.transactions.deductions') }}"
                            class="btn btn--base {{ request()->routeIs('admin.balance.transactions.deductions') ? 'active' : '' }}">{{ __('Deductions') }}</a>
                        <a href="{{ setRoute('admin.balance.transactions.refunds') }}"
                            class="btn btn--base {{ request()->routeIs('admin.balance.transactions.refunds') ? 'active' : '' }}">{{ __('Refunds') }}</a>
                    </div>
                </div>
            </div>
            <div class="table-header justify-content-end">
                <form action="{{ setRoute('admin.balance.transactions.search') }}" method="POST" class="d-flex gap-2">
                    @csrf
                    <input type="text" name="search" class="form--control"
                        placeholder="{{ __('Search by TRX ID or user...') }}" value="{{ request()->search ?? '' }}">
                    <button type="submit" class="btn btn--base"><i class="las la-search"></i></button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>{{ __('TRX ID') }}</th>
                            <th>{{ __('User') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Balance Before') }}</th>
                            <th>{{ __('Balance After') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions ?? [] as $transaction)
                            <tr>
                                <td><span class="text-primary fw-bold">{{ $transaction->trx_id }}</span></td>
                                <td>
                                    @if ($transaction->user)
                                        <span>{{ $transaction->user->fullname }}</span>
                                        <br>
                                        <small class="text-muted">{{ $transaction->user->email }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td><span
                                        class="{{ $transaction->string_type->class }}">{{ $transaction->string_type->value }}</span>
                                </td>
                                <td>
                                    @if ($transaction->type == 'recharge' || $transaction->type == 'refund')
                                        <span
                                            class="text-success">+{{ get_default_currency_symbol() }}{{ number_format($transaction->amount, 2) }}</span>
                                    @else
                                        <span
                                            class="text-danger">-{{ get_default_currency_symbol() }}{{ number_format($transaction->amount, 2) }}</span>
                                    @endif
                                </td>
                                <td>{{ get_default_currency_symbol() }}{{ number_format($transaction->balance_before, 2) }}
                                </td>
                                <td>{{ get_default_currency_symbol() }}{{ number_format($transaction->balance_after, 2) }}
                                </td>
                                <td><span
                                        class="{{ $transaction->string_status->class }}">{{ $transaction->string_status->value }}</span>
                                </td>
                                <td>{{ $transaction->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    <a href="{{ setRoute('admin.balance.transactions.details', $transaction->id) }}"
                                        class="btn btn--base"><i class="las la-eye"></i></a>
                                </td>
                            </tr>
                        @empty
                            @include('admin.components.alerts.empty', ['colspan' => 9])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ get_paginate($transactions) }}
    </div>
@endsection
