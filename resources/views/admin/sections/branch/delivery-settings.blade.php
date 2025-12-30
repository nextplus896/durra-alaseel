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
                'name' => __('Branch Management'),
                'url' => setRoute('admin.branch.index'),
            ],
        ],
        'active' => __('Delivery Settings'),
    ])
@endsection

@section('content')
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __('Delivery Settings') }} - {{ $branch->name }}</h6>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <p><strong>{{ __('Branch Address') }}:</strong> {{ $branch->address ?? '-' }}</p>
                </div>
                <div class="col-md-3">
                    <p><strong>{{ __('Latitude') }}:</strong> {{ $branch->latitude }}</p>
                </div>
                <div class="col-md-3">
                    <p><strong>{{ __('Longitude') }}:</strong> {{ $branch->longitude }}</p>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="las la-info-circle"></i>
                {{ __('Delivery prices and availability are set by vendors. Admin can view but not modify these settings.') }}
            </div>

            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>{{ __('Vendor') }}</th>
                            <th>{{ __('Delivery Price') }}</th>
                            <th>{{ __('Delivery Available') }}</th>
                            <th>{{ __('Last Updated') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($branch->deliverySettings ?? [] as $setting)
                            <tr>
                                <td>
                                    <span class="text-primary">{{ $setting->vendor->firstname ?? '' }}
                                        {{ $setting->vendor->lastname ?? '' }}</span>
                                    <br>
                                    <small class="text-muted">{{ $setting->vendor->email ?? '' }}</small>
                                </td>
                                <td>{{ get_default_currency_symbol() }}{{ number_format($setting->delivery_price, 2) }}
                                </td>
                                <td>
                                    @if ($setting->delivery_available)
                                        <span class="badge badge--success">{{ __('Available') }}</span>
                                    @else
                                        <span class="badge badge--danger">{{ __('Not Available') }}</span>
                                    @endif
                                </td>
                                <td>{{ $setting->updated_at->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            @include('admin.components.alerts.empty', [
                                'colspan' => 4,
                                'message' => __('No vendor delivery settings for this branch yet.'),
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
