@extends('vendor-end.layouts.master')

@section('content')
    <div class="branch-settings-area">
        <div class="section-header ptb-20">
            <h4 class="title">{{ __('Branch Delivery Settings') }}</h4>
            <p class="text-muted">{{ __('Configure delivery options and vendor pricing for each branch') }}</p>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success')[0] ?? session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error')[0] ?? session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ setRoute('vendor.branch.settings.store') }}" method="POST">
            @csrf

            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Branch') }}</th>
                            <th>{{ __('Address') }}</th>
                            <th>{{ __('Enable Delivery') }}</th>
                            <th>{{ __('Delivery Price') }} ({{ get_default_currency_code() }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($branchSettings ?? [] as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $item->branch->name ?? __('Unknown Branch') }}</strong>
                                </td>
                                <td>
                                    @if ($item->branch->address ?? false)
                                        <small class="text-muted">{{ $item->branch->address }}</small>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <input type="hidden" name="settings[{{ $index }}][branch_id]"
                                        value="{{ $item->branch->id }}">
                                    <div class="form-check form-switch d-flex align-items-center" style="gap:.5rem;">
                                        <label class="form-check-label mb-0" for="delivery_available_{{ $item->branch->id }}">
                                            <span class="delivery-status">{{ $item->delivery_available ? __('Delivery On') : __('Delivery Off') }}</span>
                                        </label>

                                        <input class="form-check-input delivery-toggle ms-2" type="checkbox"
                                            id="delivery_available_{{ $item->branch->id }}"
                                            name="settings[{{ $index }}][delivery_available]" value="1"
                                            {{ $item->delivery_available ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td style="min-width:160px;">
                                    <input type="text" pattern="^\d+(?:\.\d{1,2})?$" inputmode="decimal"
                                        class="form-control" id="delivery_price_{{ $item->branch->id }}"
                                        name="settings[{{ $index }}][delivery_price]"
                                        value="{{ (float) $item->delivery_price == floor((float) $item->delivery_price) ? intval((float) $item->delivery_price) : number_format((float) $item->delivery_price, 2, '.', '') }}"
                                        min="0" step="0.01" placeholder="{{ __('Enter delivery price') }}">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="las la-store-slash" style="font-size: 3rem; color: #ccc;"></i>
                                    <div class="mt-3">
                                        <h5>{{ __('No Branches Found') }}</h5>
                                        <p class="text-muted">
                                            {{ __('There are no active branches available at the moment.') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (count($branchSettings ?? []) > 0)
                <div class="row mt-4">
                    <div class="col-12 text-end">
                        <button type="submit" class="btn--base btn-lg">
                            <i class="las la-save me-2"></i>
                            {{ __('Save All Settings') }}
                        </button>
                    </div>
                </div>
            @endif
        </form>
    </div>
@endsection

@push('css')
    <style>
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }

        .section-header {
            margin-bottom: 20px;
        }

        .section-header .title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            $(document).ready(function() {
                // Update the status text when toggle changes (table row)
                $('.delivery-toggle').on('change', function() {
                    var row = $(this).closest('tr');
                    var status = row.find('.delivery-status');

                    if ($(this).is(':checked')) {
                        status.text('{{ __('Delivery On') }}');
                    } else {
                        status.text('{{ __('Delivery Off') }}');
                    }
                });
            });
        })(jQuery);
    </script>
@endpush
