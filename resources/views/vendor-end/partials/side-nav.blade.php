<div class="sidebar">
    <div class="sidebar-header">
        <a href="{{ setRoute('frontend.index') }}" class="sidebar-logo">
            <img src="{{ get_logo_vendor($basic_settings) }}"
                data-white_img="{{ get_logo_vendor($basic_settings, 'dark') }}"
                data-dark_img="{{ get_logo_vendor($basic_settings) }}" alt="logo">
        </a>
        <button class="sidebar-toggle" type="button">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <ul class="sidebar-menu">
            <li class="sidebar-menu-item">
                <a href="{{ setRoute('vendor.dashboard.index') }}" class="sidebar-link">
                    <i class="las la-palette"></i>
                    <span>{{ __('Dashboard') }}</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="{{ setRoute('vendor.car.index') }}" class="sidebar-link">
                    <i class="las la-car"></i>
                    <span>{{ __('My Car') }}</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="{{ setRoute('vendor.branch.settings.index') }}" class="sidebar-link">
                    <i class="las la-cog"></i>
                    <span>{{ __('Branch Settings') }}</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="{{ setRoute('vendor.withdraw.money.index') }}" class="sidebar-link">
                    <i class="las la-wallet"></i>
                    <span>{{ __('Withdraw Money') }}</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="{{ setRoute('vendor.booking.index') }}" class="sidebar-link">
                    <i class="las la-tasks"></i>
                    <span>{{ __('Booking Request') }}</span>
                    @php
                        $booking_count = booking_count(auth()->guard('vendor')->user()->id);
                    @endphp
                    @if ($booking_count > 0)
                        <span class="sidebar-badge">{{ $booking_count }}</span>
                    @endif
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="{{ setRoute('vendor.history.index') }}" class="sidebar-link">
                    <i class="las la-history"></i>
                    <span>{{ __('History') }}</span>
                </a>
            </li>
            @if ($basic_settings->vendor_kyc_verification)
                <li class="sidebar-menu-item">
                    <a href="{{ setRoute('vendor.kyc.index') }}" class="sidebar-link">
                        <i class="las la-user-check"></i>
                        <span>{{ __('KYC Verification') }}</span>
                    </a>
                </li>
            @endif
            <li class="sidebar-menu-item">
                <a href="{{ setRoute('vendor.security.google.2fa') }}" class="sidebar-link">
                    <i class="las la-shield-alt"></i>
                    <span>{{ __('2FA Security') }}</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="javascript:void(0)" class="sidebar-link logout-btn">
                    <i class="las la-sign-out-alt"></i>
                    <span>{{ __('Logout') }}</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-help bg-overlay-base bg_img"
            data-background="{{ asset('public/frontend/images/element/sidebar-img.webp') }}">
            <div class="sidebar-help-icon">
                <i class="las la-headphones-alt"></i>
            </div>
            <div class="sidebar-help-content">
                <h5>{{ __('Help Center') }}</h5>
                <p>{{ __('Please contact our support') }}</p>
                <a href="{{ setRoute('vendor.support.ticket.index') }}"
                    class="btn--base w-100">{{ __('Get Support') }}</a>
            </div>
        </div>
    </div>
</div>
@push('script')
    <script>
        // Sidebar toggle for mobile
        $('.sidebar-toggle').on('click', function() {
            $('.sidebar').toggleClass('active');
            $('.body-overlay').toggleClass('active');
        });

        // Close sidebar on body overlay click
        $('.body-overlay').on('click', function() {
            $('.sidebar').removeClass('active');
            $(this).removeClass('active');
        });

        // Set active menu item based on current URL
        $(document).ready(function() {
            var currentUrl = window.location.href;
            $('.sidebar-link').each(function() {
                var linkUrl = $(this).attr('href');
                if (currentUrl.indexOf(linkUrl) !== -1 && linkUrl !== 'javascript:void(0)') {
                    $(this).closest('.sidebar-menu-item').addClass('active');
                }
            });
        });

        // Logout confirmation
        $(".logout-btn").click(function() {
            var actionRoute = "{{ setRoute('vendor.logout') }}";
            var target = 1;
            var message = `{{ __('Are you sure to') }} <strong>{{ __('Logout') }}</strong>?`;

            openAlertModal(actionRoute, target, message, "{{ __('Logout') }}", "POST");
        });

        function openAlertModal(URL, target, message, actionBtnText = "{{ __('Remove') }}", method = "DELETE") {
            if (URL == "" || target == "") {
                return false;
            }

            if (message == "") {
                message = "{{ __('Are you sure to delete ?') }}";
            }
            var method = `<input type="hidden" name="_method" value="${method}">`;
            openModalByContent({
                    content: `<div class="card modal-alert border-0">
                        <div class="card-body">
                            <form method="POST" action="${URL}">
                                <input type="hidden" name="_token" value="${laravelCsrf()}">
                                ${method}
                                <div class="head mb-3">
                                    ${message}
                                    <input type="hidden" name="target" value="${target}">
                                </div>
                                <div class="foot d-flex align-items-center justify-content-between">
                                    <button type="button" class="modal-close btn--base btn-for-modal">{{ __('Close') }}</button>
                                    <button type="submit" class="alert-submit-btn btn--danger btn-loading btn-for-modal">${actionBtnText}</button>
                                </div>
                            </form>
                        </div>
                    </div>`,
                },

            );
        }

        function openModalByContent(data = {
            content: "",
            animation: "mfp-move-horizontal",
            size: "medium",
        }) {
            $.magnificPopup.open({
                removalDelay: 500,
                items: {
                    src: `<div class="white-popup mfp-with-anim ${data.size ?? "medium"}">${data.content}</div>`, // can be a HTML string, jQuery object, or CSS selector
                },
                callbacks: {
                    beforeOpen: function() {
                        this.st.mainClass = data.animation ?? "mfp-move-horizontal";
                    },
                    open: function() {
                        var modalCloseBtn = this.contentContainer.find(".modal-close");
                        $(modalCloseBtn).click(function() {
                            $.magnificPopup.close();
                        });
                    },
                },
                midClick: true,
            });
        }

        function laravelCsrf() {
            return $("head meta[name=csrf-token]").attr("content");
        }
    </script>
@endpush
