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
        'active' => __('Cancellation Policy'),
    ])
@endsection

@section('content')
    {{-- ──────────────────────────────────────────────────────────────
         Policy Configuration Form
    ─────────────────────────────────────────────────────────────── --}}
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __('Cancellation Policy Settings') }}</h6>
        </div>
        <div class="card-body">
            <form action="{{ setRoute('admin.cancellation.policy.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row mb-10-none">

                    {{-- Cancellation Window --}}
                    <div class="col-xl-6 col-lg-6 form-group">
                        <label>{{ __('Cancellation Window') }} ({{ __('Hours') }})<span>*</span></label>
                        <input type="number" name="cancellation_window_hours" class="form--control"
                            placeholder="{{ __('e.g. 4') }}"
                            value="{{ old('cancellation_window_hours', $policy->cancellation_window_hours ?? 4) }}"
                            min="0" step="1" required>
                        <small class="form-text text-muted">
                            {{ __('Customer cancels ≥ this many hours before pickup → only service fee is charged.') }}
                        </small>
                    </div>

                    {{-- Status toggle --}}
                    <div class="col-xl-6 col-lg-6 form-group">
                        <label>{{ __('Status') }}</label>
                        <div class="d-flex align-items-center gap-3 mt-2">
                            @include('admin.components.form.switcher', [
                                'name' => 'is_active',
                                'value' => $policy->is_active,
                                'options' => [__('Enable') => 1, __('Disable') => 0],
                                'onload' => true,
                                'data_target' => $policy->id,
                            ])
                            <span class="ms-2">
                                @if ($policy->is_active)
                                    <span class="badge badge--success">{{ __('Policy is Active') }}</span>
                                @else
                                    <span class="badge badge--danger">{{ __('Policy is Disabled') }}</span>
                                @endif
                            </span>
                        </div>
                    </div>

                    {{-- Rental Deduction Type --}}
                    <div class="col-xl-6 col-lg-6 form-group">
                        <label>{{ __('Rental Deduction Type') }}<span>*</span></label>
                        <select name="deduction_type" id="deduction_type" class="form--control" required>
                            @foreach ($deduction_type_labels as $value => $label)
                                <option value="{{ $value }}"
                                    {{ old('deduction_type', $policy->deduction_type) === $value ? 'selected' : '' }}>
                                    {{ __($label) }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            {{ __('Applied only when the customer cancels inside the window.') }}
                        </small>
                    </div>

                    {{-- Rental Deduction Value --}}
                    <div class="col-xl-6 col-lg-6 form-group" id="deduction_value_group">
                        <label>{{ __('Rental Deduction Value') }}<span id="deduction_value_required">*</span></label>
                        <input type="number" name="deduction_value" id="deduction_value" class="form--control"
                            placeholder="{{ __('e.g. 1') }}"
                            value="{{ old('deduction_value', $policy->deduction_value ?? 0) }}" min="0"
                            step="0.01">
                        <small class="form-text text-muted" id="deduction_value_hint">
                            {{ __('Number of rental days / fixed SAR / percentage (%)') }}
                        </small>
                    </div>

                    {{-- Service Fee Type --}}
                    <div class="col-xl-6 col-lg-6 form-group">
                        <label>{{ __('Service Fee Type') }}<span>*</span></label>
                        <select name="service_fee_type" id="service_fee_type" class="form--control" required>
                            @foreach ($fee_type_labels as $value => $label)
                                <option value="{{ $value }}"
                                    {{ old('service_fee_type', $policy->service_fee_type) === $value ? 'selected' : '' }}>
                                    {{ __($label) }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            {{ __('Always charged regardless of when the customer cancels.') }}
                        </small>
                    </div>

                    {{-- Service Fee Value --}}
                    <div class="col-xl-6 col-lg-6 form-group" id="service_fee_value_group">
                        <label>{{ __('Service Fee Value') }}<span id="service_fee_value_required">*</span></label>
                        <input type="number" name="service_fee_value" id="service_fee_value" class="form--control"
                            placeholder="{{ __('e.g. 10') }}"
                            value="{{ old('service_fee_value', $policy->service_fee_value ?? 0) }}" min="0"
                            step="0.01">
                        <small class="form-text text-muted" id="service_fee_value_hint">
                            {{ __('Fixed SAR amount or percentage (%)') }}
                        </small>
                    </div>

                    {{-- Info Banner --}}
                    <div class="col-xl-12 col-lg-12 form-group">
                        <div class="alert alert-info">
                            <i class="las la-info-circle"></i>
                            {{ __('This is a global policy applied to all bookings. Refund = Rental Amount − Deduction − Service Fee. Refund is never negative.') }}
                        </div>
                    </div>

                    {{-- Last edited by --}}
                    @if ($policy && $policy->last_edit_by)
                        <div class="col-xl-12 col-lg-12 form-group">
                            <label>{{ __('Last Updated By') }}</label>
                            <p class="form-text">
                                {{ optional(\App\Models\Admin\Admin::find($policy->last_edit_by))->full_name ?? '-' }}
                                {{ __('at') }}
                                {{ $policy->updated_at->format('d M Y H:i') }}
                            </p>
                        </div>
                    @endif

                    {{-- Submit button --}}
                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.button.form-btn', [
                            'class' => 'w-100 btn-loading',
                            'permission' => 'admin.cancellation.policy.update',
                            'text' => __('Update Cancellation Policy'),
                        ])
                    </div>

                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $(document).ready(function() {

            // ── Status switcher AJAX ──────────────────────────────────────
            switcherAjax("{{ setRoute('admin.cancellation.policy.status.update') }}");

            // ── Deduction type: hide/show value field when "none" ────────
            function toggleValueField(typeSelectId, valueGroupId, requiredSpanId) {
                const type = $(typeSelectId).val();
                if (type === 'none') {
                    $(valueGroupId).hide();
                    $(requiredSpanId).hide();
                } else {
                    $(valueGroupId).show();
                    $(requiredSpanId).show();
                }
            }

            function initToggle() {
                toggleValueField('#deduction_type', '#deduction_value_group', '#deduction_value_required');
                toggleValueField('#service_fee_type', '#service_fee_value_group', '#service_fee_value_required');
            }

            initToggle();
            $('#deduction_type').on('change', function() {
                toggleValueField('#deduction_type', '#deduction_value_group', '#deduction_value_required');
            });
            $('#service_fee_type').on('change', function() {
                toggleValueField('#service_fee_type', '#service_fee_value_group',
                    '#service_fee_value_required');
            });

            // ── Refund preview calculator ────────────────────────────────
            const currency = '{{ get_default_currency_symbol() }}';
            const windowHours = parseInt('{{ $policy->cancellation_window_hours ?? 4 }}');
            const deductionType = '{{ $policy->deduction_type ?? 'day' }}';
            const deductionValue = parseFloat('{{ $policy->deduction_value ?? 1 }}');
            const serviceFeeType = '{{ $policy->service_fee_type ?? 'percentage' }}';
            const serviceFeeValue = parseFloat('{{ $policy->service_fee_value ?? 10 }}');

            function calcDeduction(rental, days, insideWindow) {
                if (!insideWindow) return 0;
                switch (deductionType) {
                    case 'none':
                        return 0;
                    case 'fixed':
                        return Math.min(deductionValue, rental);
                    case 'percentage':
                        return rental * (deductionValue / 100);
                    case 'day':
                        const dailyRate = days > 0 ? rental / days : 0;
                        return Math.min(dailyRate * deductionValue, rental);
                    default:
                        return 0;
                }
            }

            function calcFee(rental) {
                switch (serviceFeeType) {
                    case 'none':
                        return 0;
                    case 'fixed':
                        return serviceFeeValue;
                    case 'percentage':
                        return rental * (serviceFeeValue / 100);
                    default:
                        return 0;
                }
            }

            $('#btn-calculate').on('click', function() {
                const rental = parseFloat($('#preview-rental').val()) || 0;
                const days = parseInt($('#preview-days').val()) || 1;
                const insideWindow = $('#preview-scenario').val() === 'inside';

                const deduction = calcDeduction(rental, days, insideWindow);
                const fee = calcFee(rental);
                const totalDeducted = deduction + fee;
                const refund = Math.max(0, rental - totalDeducted);

                const fmt = v => currency + v.toFixed(2);
                $('#result-deduction').val(fmt(deduction));
                $('#result-fee').val(fmt(fee));
                $('#result-total-deducted').val(fmt(totalDeducted));
                $('#result-refund').val(fmt(refund));
                $('#preview-result').css('display', 'flex').show();
            });

        });
    </script>
@endpush
