<nav class="navbar-wrapper">
    <div class="dashboard-title-part">
        <div class="left">
            <div class="icon">
                <button class="sidebar-menu-bar">
                    <i class="fas fa-exchange-alt"></i>
                </button>
            </div>
            <div class="dashboard-path">
                <span class="main-path"><a href="{{ setRoute('vendor.profile.index') }}">{{ __('Dashboard') }}</a></span>
                <i class="las la-angle-right"></i>
                <span class="active-path">{{ $page_title ?? '' }}</span>
            </div>
        </div>
        <div class="right">
            <div class="header-notification-wrapper">
                <button class="notification-icon">
                    <i class="las la-bell"></i>
                    <span class="bling-area" style="display:none;"></span>
                </button>
                <div class="notification-wrapper">
                    <div class="notification-header">
                        <h5 class="title">{{ __('Notification') }}</h5>
                        <button class="notifications-clear-all-btn d-none"
                            style="background:none;border:none;color:#667eea;cursor:pointer;font-size:12px;">{{ __('Clear All') }}</button>
                    </div>
                    <ul class="notification-list">
                        @forelse (get_vendor_notifications() ?? [] as $item)
                            <li>
                                <div class="thumb">
                                    <img src="{{ $item->message->image ?? auth()->guard('vendor')->user()->userImage }}"
                                        alt="user">
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
            <div class="header-language-wrapper">
                <button class="language-icon">
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
            <div class="header-user-wrapper">
                <div class="header-user-thumb">
                    <a href="{{ setRoute('vendor.profile.index') }}"><img
                            src="{{ auth()->user()->userImage ?? asset('frontend/assets/images/client/client-3.webp') }}"
                            alt="client"></a>
                </div>
            </div>
        </div>
    </div>
</nav>


<style>
    .header-language-wrapper {
        position: relative;
        margin-inline-end: 15px;
    }

    .header-language-wrapper .language-icon {
        background: transparent;
        border: none;
        font-size: 20px;
        color: #6c757d;
        cursor: pointer;
        padding: 8px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        width: 40px;
        height: 40px;
    }

    .header-language-wrapper .language-icon:hover {
        color: #212529;
        background: rgba(0, 0, 0, 0.05);
    }

    .header-language-wrapper .language-wrapper {
        position: fixed;
        width: 280px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-8px);
        transition: all 0.3s ease;
        z-index: 9999;
        border: 1px solid #e9ecef;
    }

    .header-language-wrapper.active .language-wrapper {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .language-wrapper .language-header {
        padding: 16px 20px;
        border-bottom: 1px solid #e9ecef;
    }

    .language-wrapper .language-header .title {
        margin: 0;
        font-size: 14px;
        font-weight: 600;
        color: #212529;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .language-wrapper .language-list {
        list-style: none;
        padding: 8px 0;
        margin: 0;
        max-height: 320px;
        overflow-y: auto;
    }

    .language-wrapper .language-list li {
        padding: 0;
    }

    .language-wrapper .language-list li a {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 20px;
        color: #6c757d;
        text-decoration: none;
        transition: all 0.2s ease;
        font-size: 14px;
    }

    .language-list li a:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding-inline-start: 24px;

        .language-wrapper .language-list li a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .language-wrapper .language-list li a .language-name {
            font-weight: 500;
            flex: 1;
        }

        .language-wrapper .language-list li a .language-code {
            font-size: 11px;
            padding: 4px 10px;
            background: #e9ecef;
            border-radius: 6px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-inline-start: 8px;
        }

        .language-wrapper .language-list li a:hover .language-code {
            background: #dee2e6;
        }

        .language-wrapper .language-list li a.active .language-code {
            background: rgba(255, 255, 255, 0.25);
            color: #fff;
        }

        /* Scrollbar styling */
        .language-wrapper .language-list::-webkit-scrollbar {
            width: 6px;
        }

        .language-wrapper .language-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .language-wrapper .language-list::-webkit-scrollbar-thumb {
            background: #dee2e6;
            border-radius: 3px;
        }

        .language-wrapper .language-list::-webkit-scrollbar-thumb:hover {
            background: #adb5bd;
        }

        .header-notification-wrapper {
            position: relative;
        }

        .header-notification-wrapper .notification-icon {
            background: transparent;
            border: none;
            font-size: 20px;
            color: #6c757d;
            cursor: pointer;
            padding: 8px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            transition: all 0.3s ease;
        }

        .header-notification-wrapper .notification-icon:hover {
            color: #212529;
            background: rgba(0, 0, 0, 0.05);
        }

        .header-notification-wrapper .bling-area {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 10px;
            height: 10px;
            background: #e74c3c;
            border-radius: 50%;
            border: 2px solid #fff;
        }

        .header-notification-wrapper .notification-wrapper {
            position: fixed;
            width: 340px;
            max-height: 420px;
            overflow-y: auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: all 0.3s ease;
            z-index: 9999;
            border: 1px solid #e9ecef;
        }

        .header-notification-wrapper.active .notification-wrapper {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .header-notification-wrapper .notification-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-notification-wrapper .notification-list {
            list-style: none;
            padding: 8px 0;
            margin: 0;
        }

        .header-notification-wrapper .notification-list li {
            padding: 12px 20px;
            display: flex;
            gap: 12px;
            transition: background 0.2s ease;
            cursor: pointer;
        }

        .header-notification-wrapper .notification-list li:hover {
            background: rgba(0, 0, 0, 0.03);
        }

        .header-notification-wrapper .notification-list .thumb img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .header-notification-wrapper .notification-list .content {
            flex: 1;
            min-width: 0;
        }

        .header-notification-wrapper .notification-list .title-area {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }

        .header-notification-wrapper .notification-list .title {
            margin: 0;
            font-size: 13px;
            font-weight: 600;
            color: #212529;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .header-notification-wrapper .notification-list .time {
            font-size: 11px;
            color: #adb5bd;
            white-space: nowrap;
            margin-inline-start: 8px;
        }

        .header-notification-wrapper .notification-list .sub-title {
            font-size: 12px;
            color: #6c757d;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .header-notification-wrapper .notification-list .not-found {
            text-align: center;
            padding: 30px 20px;
            color: #adb5bd;
            font-size: 14px;
        }

        .header-notification-wrapper .notification-wrapper::-webkit-scrollbar {
            width: 6px;
        }

        .header-notification-wrapper .notification-wrapper::-webkit-scrollbar-track {
            background: transparent;
        }

        .header-notification-wrapper .notification-wrapper::-webkit-scrollbar-thumb {
            background: #dee2e6;
            border-radius: 3px;
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
            if (languageWrapper.classList.contains('active')) {
                const rect = languageIcon.getBoundingClientRect();
                const isRTL = document.dir === 'rtl' || document.documentElement.dir === 'rtl';
                languageDropdown.style.position = 'fixed';
                languageDropdown.style.top = (rect.bottom + 10) + 'px';

                // Use logical positioning based on text direction
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

        // Reposition on scroll and resize
        window.addEventListener('scroll', positionLanguageDropdown);
        window.addEventListener('resize', positionLanguageDropdown);

        // Language switch
        const languageLinks = document.querySelectorAll('.language-link');
        languageLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();

                const code = this.dataset.code;
                const dir = this.dataset.dir;

                // Send AJAX request
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
                            // Reload page to apply language changes
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
        if (notifWrapper.classList.contains('active')) {
            const rect = notifIcon.getBoundingClientRect();
            const isRTL = document.dir === 'rtl' || document.documentElement.dir === 'rtl';
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
