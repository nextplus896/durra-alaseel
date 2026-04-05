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
        'active' => __('Tax Settings'),
    ])
@endsection

@section('content')
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __('Tax Settings') }}</h6>
        </div>
        <div class="card-body">
            <form action="{{ setRoute('admin.tax.settings.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row mb-10-none">
                    <div class="col-xl-6 col-lg-6 form-group">
                        <label>{{ __('Tax Name') }}<span>*</span></label>
                        <input type="text" name="name" class="form--control"
                            placeholder="{{ __('e.g., VAT, Sales Tax') }}"
                            value="{{ old('name', $tax_setting->tax_name ?? 'VAT') }}" required>
                    </div>
                    <div class="col-xl-6 col-lg-6 form-group">
                        <label>{{ __('Tax Percentage') }} (%)<span>*</span></label>
                        <input type="number" name="percentage" class="form--control"
                            placeholder="{{ __('Enter tax percentage') }}"
                            value="{{ old('percentage', $tax_setting->tax_percentage ?? 15) }}" step="0.01"
                            min="0" max="100" required>
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group">
                        <div class="alert alert-info">
                            <i class="las la-info-circle"></i>
                            {{ __('This tax percentage will be applied to all financial calculations including booking costs, delivery charges, and balance deductions.') }}
                        </div>
                    </div>
                    @if ($tax_setting)
                        <div class="col-xl-6 col-lg-6 form-group">
                            <label>{{ __('Status') }}</label>
                            <div class="d-flex align-items-center gap-3">
                                @include('admin.components.form.switcher', [
                                    'name' => 'tax_status',
                                    'value' => $tax_setting->tax_status,
                                    'options' => [__('Enable') => 1, __('Disable') => 0],
                                    'onload' => true,
                                    'data_target' => $tax_setting->id,
                                ])
                                <span class="ms-2">
                                    @if ($tax_setting->tax_status)
                                        <span class="badge badge--success">{{ __('Tax is Active') }}</span>
                                    @else
                                        <span class="badge badge--danger">{{ __('Tax is Disabled') }}</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="col-xl-6 col-lg-6 form-group">
                            <label>{{ __('Last Updated By') }}</label>
                            <p class="form-text">
                                {{ optional(\App\Models\Admin\Admin::find($tax_setting->tax_last_edit_by))->full_name ?? '-' }}
                                {{ __('at') }}
                                {{ $tax_setting->updated_at->format('d M Y H:i') }}</p>
                        </div>
                    @endif
                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.button.form-btn', [
                            'class' => 'w-100 btn-loading',
                            'permission' => 'admin.tax.settings.update',
                            'text' => __('Update Tax Settings'),
                        ])
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="custom-card mt-4">
        <div class="card-header">
            <h6 class="title">{{ __('Tax Calculation Preview') }}</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('Sample Amount') }}</label>
                        <input type="number" id="sample-amount" class="form--control" value="100" min="0"
                            step="0.01">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('Tax Amount') }} ({{ $tax_setting->tax_percentage ?? 15 }}%)</label>
                        <input type="text" id="tax-amount" class="form--control" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('Total with Tax') }}</label>
                        <input type="text" id="total-amount" class="form--control" readonly>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $(document).ready(function() {
            // Switcher
            switcherAjax("{{ setRoute('admin.tax.settings.status.update') }}");

            // Tax calculation preview
            const taxPercentage = parseFloat('{{ $tax_setting->tax_percentage ?? 15 }}');

            function calculateTax() {
                const amount = parseFloat($('#sample-amount').val()) || 0;
                const taxAmount = (amount * taxPercentage) / 100;
                const total = amount + taxAmount;

                $('#tax-amount').val('{{ get_default_currency_symbol() }}' + taxAmount.toFixed(2));
                $('#total-amount').val('{{ get_default_currency_symbol() }}' + total.toFixed(2));
            }

            calculateTax();
            $('#sample-amount').on('input', calculateTax);
        });
    </script>
@endpush
