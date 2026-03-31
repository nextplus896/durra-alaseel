@isset($booking)
    @if ($booking->status === 1)
        {{-- Booking Reject Modal --}}
        <div id="booking-reject-modal" class="mfp-hide large">
            <div class="modal-data">
                <div class="modal-header px-0">
                    <h5 class="modal-title">{{ __('Reject Booking') }} {{ $booking->trip_id ?? $booking->id }}</h5>
                </div>
                <div class="modal-form-data">
                    <form class="modal-form" method="POST" action="{{ route('vendor.booking.reject', $booking->id) }}">
                        @csrf
                        <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                        <div class="row mb-10-none">
                            <div class="col-xl-12 col-lg-12 form-group">
                                <label class="form-label">{{ __('Select Rejection Reason') }}<span
                                        class="text-danger">*</span></label>
                                <div class="rejection-reasons-list">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input rejection-reason-radio" type="radio" name="reason"
                                            id="reason1" value="Vehicle Unavailable" required>
                                        <label class="form-check-label" for="reason1">
                                            <i class="las la-car-crash text-danger me-2"></i>
                                            <strong>{{ __('Vehicle Unavailable') }}</strong>
                                            <small
                                                class="d-block text-muted ms-4">{{ __('Car is already booked or under maintenance') }}</small>
                                        </label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input rejection-reason-radio" type="radio" name="reason"
                                            id="reason2" value="Driver Documents Invalid" required>
                                        <label class="form-check-label" for="reason2">
                                            <i class="las la-id-card text-warning me-2"></i>
                                            <strong>{{ __('Driver Documents Invalid') }}</strong>
                                            <small
                                                class="d-block text-muted ms-4">{{ __("Customer's license or ID has issues") }}</small>
                                        </label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input rejection-reason-radio" type="radio" name="reason"
                                            id="reason3" value="Payment Issue" required>
                                        <label class="form-check-label" for="reason3">
                                            <i class="las la-credit-card text-danger me-2"></i>
                                            <strong>{{ __('Payment Issue') }}</strong>
                                            <small
                                                class="d-block text-muted ms-4">{{ __('Payment verification failed') }}</small>
                                        </label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input rejection-reason-radio" type="radio" name="reason"
                                            id="reason4" value="Service Area Restriction" required>
                                        <label class="form-check-label" for="reason4">
                                            <i class="las la-map-marked-alt text-info me-2"></i>
                                            <strong>{{ __('Service Area Restriction') }}</strong>
                                            <small
                                                class="d-block text-muted ms-4">{{ __('Location is outside our service area') }}</small>
                                        </label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input rejection-reason-radio" type="radio" name="reason"
                                            id="reason5" value="Rental Duration Issue" required>
                                        <label class="form-check-label" for="reason5">
                                            <i class="las la-calendar-times text-warning me-2"></i>
                                            <strong>{{ __('Rental Duration Issue') }}</strong>
                                            <small
                                                class="d-block text-muted ms-4">{{ __('Requested duration is not available') }}</small>
                                        </label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input rejection-reason-radio" type="radio" name="reason"
                                            id="reason6" value="Policy Violation" required>
                                        <label class="form-check-label" for="reason6">
                                            <i class="las la-exclamation-triangle text-danger me-2"></i>
                                            <strong>{{ __('Policy Violation') }}</strong>
                                            <small
                                                class="d-block text-muted ms-4">{{ __("Customer doesn't meet rental requirements") }}</small>
                                        </label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input rejection-reason-radio" type="radio" name="reason"
                                            id="reason_other" value="other" required>
                                        <label class="form-check-label" for="reason_other">
                                            <i class="las la-edit text-secondary me-2"></i>
                                            <strong>{{ __('Other') }}</strong>
                                            <small
                                                class="d-block text-muted ms-4">{{ __('Specify custom reason') }}</small>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-12 col-lg-12 form-group" id="custom-reason-container" style="display: none;">
                                <label class="form-label">{{ __('Custom Rejection Reason') }}<span
                                        class="text-danger">*</span></label>
                                <textarea class="form--control" name="custom_reason" id="custom_reason" rows="4"
                                    placeholder="{{ __('Please specify the reason for rejection...') }}"></textarea>
                                <small class="text-muted">{{ __('This reason will be sent to the customer') }}</small>
                            </div>

                            <div
                                class="col-xl-12 col-lg-12 form-group d-flex align-items-center justify-content-between mt-4">
                                <button type="button" class="btn btn--secondary modal-close">
                                    <i class="las la-times me-1"></i>{{ __('Cancel') }}
                                </button>
                                <button type="submit" class="btn btn--danger" id="reject-submit-btn">
                                    <i class="las la-ban me-1"></i>{{ __('Reject Booking') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @push('script')
            <script>
                // Open modal when reject button is clicked
                $(".booking-reject-btn").click(function(e) {
                    e.preventDefault();
                    openModalBySelector($("#booking-reject-modal"));
                });

                // Show/hide custom reason textarea based on selection
                $(".rejection-reason-radio").change(function() {
                    if ($(this).val() === 'other') {
                        $("#custom-reason-container").slideDown();
                        $("#custom_reason").prop('required', true);
                    } else {
                        $("#custom-reason-container").slideUp();
                        $("#custom_reason").prop('required', false);
                        $("#custom_reason").val('');
                    }
                });

                // Form validation before submit
                $(".modal-form").submit(function(e) {
                    var selectedReason = $("input[name='reason']:checked").val();

                    if (!selectedReason) {
                        e.preventDefault();
                        alert('{{ __('Please select a rejection reason') }}');
                        return false;
                    }

                    if (selectedReason === 'other') {
                        var customReason = $("#custom_reason").val().trim();
                        if (customReason === '') {
                            e.preventDefault();
                            alert('{{ __('Please provide a custom rejection reason') }}');
                            $("#custom_reason").focus();
                            return false;
                        }
                    }

                    // Confirm before rejecting
                    if (!confirm(
                        '{{ __('Are you sure you want to reject this booking? This action cannot be undone.') }}')) {
                        e.preventDefault();
                        return false;
                    }

                    return true;
                });
            </script>
        @endpush

        @push('css')
            <style>
                .rejection-reasons-list {
                    max-height: 400px;
                    overflow-y: auto;
                    padding-right: 10px;
                }

                .rejection-reasons-list .form-check {
                    padding: 12px 15px;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    transition: all 0.3s ease;
                    cursor: pointer;
                }

                .rejection-reasons-list .form-check:hover {
                    background-color: #f8f9fa;
                    border-color: #dee2e6;
                }

                .rejection-reasons-list .form-check-input:checked~.form-check-label {
                    color: #0d6efd;
                }

                .rejection-reasons-list .form-check-input:checked {
                    background-color: #0d6efd;
                    border-color: #0d6efd;
                }

                .rejection-reasons-list .form-check:has(.form-check-input:checked) {
                    background-color: #e7f3ff;
                    border-color: #0d6efd;
                }

                .rejection-reasons-list .form-check-label {
                    cursor: pointer;
                    width: 100%;
                }

                .rejection-reasons-list .form-check-input {
                    margin-top: 0.4em;
                    cursor: pointer;
                }

                #custom-reason-container {
                    animation: slideDown 0.3s ease;
                }

                @keyframes slideDown {
                    from {
                        opacity: 0;
                        transform: translateY(-10px);
                    }

                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
            </style>
        @endpush
    @endif
@endisset
