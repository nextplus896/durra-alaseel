@php
    $vendorUser = auth()->guard('vendor')->user();
    $avatarImg = $vendorUser->userImage ?? asset('backend/images/default/profile-default.webp');
    $defaultAvatar = asset('backend/images/default/profile-default.webp');
@endphp
<nav class="navbar-wrapper">
    <div class="dashboard-title-part">
        {{-- Left / Start side (Breadcrumbs & Sidebar Toggle) --}}
        <div class="left">
            <button class="sidebar-menu-bar btn-header-toggle" type="button" title="{{ __('Toggle Menu') }}">
                <i class="fas fa-bars"></i>
            </button>
            <div class="dashboard-path">
                <span class="main-path">
                    <a href="{{ setRoute('vendor.dashboard.index') }}">{{ __('Dashboard') }}</a>
                </span>
                @if(isset($page_title) && !empty($page_title))
                    <i class="las la-angle-left path-arrow rtl-arrow"></i>
                    <i class="las la-angle-right path-arrow ltr-arrow"></i>
                    <span class="active-path">{{ $page_title }}</span>
                @endif
            </div>
        </div>

        {{-- Right / End side (Notifications, Language, Profile) --}}
        <div class="right">
            {{-- Notification Dropdown --}}
            <div class="header-notification-wrapper">
                <button class="notification-icon" type="button" title="{{ __('Notification') }}">
                    <i class="las la-bell"></i>
                    <span class="bling-area" style="display:none;"></span>
                </button>
                <div class="notification-wrapper">
                    <div class="notification-header">
                        <h5 class="title">{{ __('Notification') }}</h5>
                        <button class="notifications-clear-all-btn d-none"
                            style="background:none;border:none;color:#4338ca;cursor:pointer;font-size:12px;font-weight:700;">{{ __('Clear All') }}</button>
                    </div>
                    <ul class="notification-list">
                        @forelse (get_vendor_notifications() ?? [] as $item)
                            <li>
                                <div class="thumb">
                                    <img src="{{ $item->message->image ?? $avatarImg }}"
                                        alt="user" onerror="this.onerror=null;this.src='{{ $defaultAvatar }}';">
                                </div>
                                <div class="content">
                                    <div class="title-area">
                                        <h6 class="title">{{ $item->message->title }}</h6>
                                        <span class="time">{{ $item->created_at->diffForHumans() }}</span>
                                    </div>
                                    <span class="sub-title">{{ $item->message->message ?? '' }}</span>
                                </div>
                            </li>
                        @empty
                            <h6 class="not-found">{{ __('No notification yet') }}</h6>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- Language Selector Dropdown --}}
            <div class="header-language-wrapper">
                <button class="language-icon" type="button" title="{{ __('Language') }}">
                    <i class="las la-globe"></i>
                </button>
                <div class="language-wrapper">
                    <div class="language-header">
                        <h5 class="title">{{ __('Select Language') }}</h5>
                    </div>
                    <ul class="language-list">
                        @php
                            $current_locale = session('local', get_default_language_code());
                        @endphp
                        @foreach ($languages ?? [] as $language)
                            <li>
                                <a href="javascript:void(0)"
                                    class="language-link {{ $current_locale == $language->code ? 'active' : '' }}"
                                    data-code="{{ $language->code }}" data-dir="{{ $language->dir }}">
                                    <span class="language-name">{{ $language->name }}</span>
                                    <span class="language-code">{{ strtoupper($language->code) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- User Profile Pill (Avatar + Name) --}}
            <div class="header-user-wrapper">
                <a href="{{ setRoute('vendor.profile.index') }}" class="header-user-pill" title="{{ __('Profile') }}">
                    <div class="header-user-info d-none d-md-flex flex-column text-start">
                        <span class="header-user-name">{{ $vendorUser->fullname ?? $vendorUser->username ?? __('Vendor') }}</span>
                        <small class="header-user-role">{{ __('Merchant Account') }}</small>
                    </div>
                    <div class="header-user-thumb">
                        <img src="{{ $avatarImg }}" alt="profile" onerror="this.onerror=null;this.src='{{ $defaultAvatar }}';">
                    </div>
                </a>
            </div>
        </div>
    </div>
</nav>

<style>
    /* Top Navbar Clean, Professional Layout */
    .navbar-wrapper {
        padding-top: 16px;
        padding-bottom: 8px;
        padding-inline-start: 270px;
        padding-inline-end: 20px;
        margin: 0 !important;
        transition: all 0.5s ease;
    }
    .navbar-wrapper.active {
        padding-inline-start: 85px !important;
        padding-inline-end: 10px !important;
    }
    @media only screen and (max-width: 1199px) {
        .navbar-wrapper {
            padding-inline-start: 15px !important;
            padding-inline-end: 15px !important;
            padding-top: 14px;
            padding-bottom: 8px;
        }
        .navbar-wrapper.active {
            padding-inline-start: 20px !important;
            padding-inline-end: 15px !important;
        }
    }

    .dashboard-title-part {
        display: flex;
        flex-wrap: nowrap;
        justify-content: space-between;
        align-items: center;
        background: #ffffff;
        border-radius: 14px;
        padding: 10px 20px;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.03);
        border: 1px solid #edf2f7;
        margin: 0;
    }

    /* Left side: Toggle button and Breadcrumb */
    .dashboard-title-part .left {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }
    .btn-header-toggle {
        width: 38px;
        height: 38px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        color: #334155;
        font-size: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }
    .btn-header-toggle:hover {
        background: var(--primary-color, #4338ca);
        border-color: var(--primary-color, #4338ca);
        color: #ffffff;
    }
    .dashboard-path {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.92rem;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .dashboard-path .main-path a {
        color: #64748b;
        text-decoration: none;
        transition: color 0.2s ease;
    }
    .dashboard-path .main-path a:hover {
        color: var(--primary-color, #4338ca);
    }
    .dashboard-path .active-path {
        color: #0f172a;
        font-weight: 800;
    }
    
    /* Arrow direction handling */
    body.rtl .dashboard-path .ltr-arrow {
        display: none !important;
    }
    body.rtl .dashboard-path .rtl-arrow {
        display: inline-block !important;
        font-size: 13px;
        color: #94a3b8;
    }
    body:not(.rtl) .dashboard-path .rtl-arrow {
        display: none !important;
    }
    body:not(.rtl) .dashboard-path .ltr-arrow {
        display: inline-block !important;
        font-size: 13px;
        color: #94a3b8;
    }

    /* Right side: Actions & User */
    .dashboard-title-part .right {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0;
    }

    /* Icon Buttons (Notification & Language) */
    .header-notification-wrapper,
    .header-language-wrapper {
        position: relative;
    }
    .header-notification-wrapper .notification-icon,
    .header-language-wrapper .language-icon {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        font-size: 19px;
        color: #475569;
        cursor: pointer;
        padding: 0;
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        transition: all 0.2s ease;
    }
    .header-notification-wrapper .notification-icon:hover,
    .header-language-wrapper .language-icon:hover {
        background: #ffffff;
        color: var(--primary-color, #4338ca);
        border-color: #cbd5e1;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .header-notification-wrapper .bling-area {
        position: absolute;
        top: 6px;
        inset-inline-end: 6px;
        width: 8px;
        height: 8px;
        background: #ef4444;
        border-radius: 50%;
        border: 2px solid #ffffff;
    }

    /* User Profile Pill */
    .header-user-wrapper {
        display: flex;
        align-items: center;
    }
    .header-user-pill {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 4px 6px 4px 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 30px;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    body.rtl .header-user-pill {
        padding: 4px 10px 4px 6px;
    }
    .header-user-pill:hover {
        background: #ffffff;
        border-color: #cbd5e1;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    .header-user-name {
        font-size: 0.82rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
    }
    .header-user-role {
        font-size: 0.7rem;
        font-weight: 600;
        color: #64748b;
        line-height: 1;
    }
    .header-user-thumb {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        overflow: hidden;
        border: 1.5px solid #cbd5e1;
        background: #e2e8f0;
        flex-shrink: 0;
    }
    .header-user-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Notification & Language Dropdown Windows */
    .header-notification-wrapper .notification-wrapper,
    .header-language-wrapper .language-wrapper {
        position: fixed;
        width: 320px;
        max-height: 420px;
        overflow-y: auto;
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-8px);
        transition: all 0.25s ease;
        z-index: 9999;
        border: 1px solid #edf2f7;
    }
    .header-notification-wrapper.active .notification-wrapper,
    .header-language-wrapper.active .language-wrapper {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .notification-header,
    .language-header {
        padding: 14px 18px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .notification-header .title,
    .language-header .title {
        margin: 0;
        font-size: 0.9rem;
        font-weight: 800;
        color: #0f172a;
    }

    .language-list {
        list-style: none;
        padding: 6px 0;
        margin: 0;
    }
    .language-list li a {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 18px;
        color: #334155;
        font-size: 0.88rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .language-list li a:hover,
    .language-list li a.active {
        background: #f8fafc;
        color: var(--primary-color, #4338ca);
    }
    .language-list li a .language-code {
        font-size: 0.7rem;
        font-weight: 800;
        padding: 2px 6px;
        background: #e2e8f0;
        border-radius: 6px;
    }
    .language-list li a.active .language-code {
        background: var(--primary-color, #4338ca);
        color: #ffffff;
    }

    .notification-list {
        list-style: none;
        padding: 6px 0;
        margin: 0;
    }
    .notification-list li {
        padding: 12px 18px;
        display: flex;
        gap: 10px;
        border-bottom: 1px solid #f8fafc;
        transition: background 0.2s ease;
    }
    .notification-list li:hover {
        background: #f8fafc;
    }
    .notification-list .thumb img {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
    }
    .notification-list .content {
        flex: 1;
        min-width: 0;
    }
    .notification-list .title-area {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 3px;
    }
    .notification-list .title {
        margin: 0;
        font-size: 0.84rem;
        font-weight: 700;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .notification-list .time {
        font-size: 0.7rem;
        color: #94a3b8;
    }
    .notification-list .sub-title {
        font-size: 0.78rem;
        color: #64748b;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .notification-list .not-found {
        text-align: center;
        padding: 24px 16px;
        color: #94a3b8;
        font-size: 0.88rem;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Language dropdown toggle
        const languageWrapper = document.querySelector('.header-language-wrapper');
        const languageIcon = document.querySelector('.language-icon');
        const languageDropdown = document.querySelector('.language-wrapper');

        // Position language dropdown correctly
        function positionLanguageDropdown() {
            if (languageWrapper && languageWrapper.classList.contains('active')) {
                const rect = languageIcon.getBoundingClientRect();
                const isRTL = document.dir === 'rtl' || document.documentElement.dir === 'rtl' || document.body.classList.contains('rtl');
                languageDropdown.style.position = 'fixed';
                languageDropdown.style.top = (rect.bottom + 10) + 'px';

                if (isRTL) {
                    languageDropdown.style.left = rect.left + 'px';
                    languageDropdown.style.right = 'auto';
                } else {
                    languageDropdown.style.right = (window.innerWidth - rect.right) + 'px';
                    languageDropdown.style.left = 'auto';
                }
            }
        }

        if (languageIcon) {
            languageIcon.addEventListener('click', function(e) {
                e.stopPropagation();
                languageWrapper.classList.toggle('active');
                positionLanguageDropdown();

                // Close notification if open
                document.querySelector('.header-notification-wrapper')?.classList.remove('active');
            });
        }

        window.addEventListener('scroll', positionLanguageDropdown);
        window.addEventListener('resize', positionLanguageDropdown);

        // Language switch
        const languageLinks = document.querySelectorAll('.language-link');
        languageLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();

                const code = this.dataset.code;
                const dir = this.dataset.dir;

                fetch('{{ setRoute('vendor.profile.language.switch') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector(
                                'meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            code: code,
                            dir: dir
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            });
        });

        // Close language dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!languageWrapper?.contains(e.target)) {
                languageWrapper?.classList.remove('active');
            }
        });
    });

    // Notification dropdown toggle
    const notifWrapper = document.querySelector('.header-notification-wrapper');
    const notifIcon = document.querySelector('.notification-icon');
    const notifDropdown = document.querySelector('.notification-wrapper');

    function positionNotifDropdown() {
        if (notifWrapper && notifWrapper.classList.contains('active')) {
            const rect = notifIcon.getBoundingClientRect();
            const isRTL = document.dir === 'rtl' || document.documentElement.dir === 'rtl' || document.body.classList.contains('rtl');
            notifDropdown.style.position = 'fixed';
            notifDropdown.style.top = (rect.bottom + 10) + 'px';
            if (isRTL) {
                notifDropdown.style.left = rect.left + 'px';
                notifDropdown.style.right = 'auto';
            } else {
                notifDropdown.style.right = (window.innerWidth - rect.right) + 'px';
                notifDropdown.style.left = 'auto';
            }
        }
    }

    if (notifIcon) {
        notifIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            notifWrapper.classList.toggle('active');
            positionNotifDropdown();
            document.querySelector('.header-language-wrapper')?.classList.remove('active');
        });
    }

    window.addEventListener('scroll', positionNotifDropdown);
    window.addEventListener('resize', positionNotifDropdown);

    document.addEventListener('click', function(e) {
        if (!notifWrapper?.contains(e.target)) {
            notifWrapper?.classList.remove('active');
        }
    });
</script>
