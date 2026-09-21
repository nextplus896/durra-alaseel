@if ($basic_settings->push_notification_config != null && $basic_settings->push_notification_config->method == 'pusher')
    <script src="https://js.pusher.com/beams/1.0/push-notifications-cdn.js"></script>
    <script>
        try {
            var clientInstanceId = "{{ $basic_settings->push_notification_config->instance_id }}";
            var beamsClient = new PusherPushNotifications.Client({
                instanceId: clientInstanceId,
            });

            var generatePublisherId = "{{ make_user_id_for_pusher('vendor', auth()->guard('vendor')->user()->id) }}";

            var beamsTokenProvider = new PusherPushNotifications.TokenProvider({
                url: "{{ setRoute('vendor.pusher.beams.auth') }}",
            });

            beamsClient
                .start()
                .then(function(bc) {
                    return bc.getDeviceId();
                })
                .then(function(deviceId) {
                    return beamsClient.setUserId(generatePublisherId, beamsTokenProvider);
                })
                .catch(function(e) {
                    console.warn("Pusher Beams skipped:", e.message);
                });
        } catch (e) {
            console.warn("Pusher Beams skipped:", e.message);
        }
    </script>
@endif

@if ($basic_settings->broadcast_config != null && $basic_settings->broadcast_config->method == 'pusher')
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>

    <audio id="notification-sound" preload="auto">
        <source src="{{ asset('frontend/assets/audio/notification.wav') }}" type="audio/wav">
    </audio>

    <script>
        var primaryKey = "{{ $basic_settings->broadcast_config->primary_key ?? '' }}";
        var cluster = "{{ $basic_settings->broadcast_config->cluster ?? '' }}";

        var pusher = new Pusher(primaryKey, {
            cluster: cluster,
        });

        var vendorId = "{{ auth()->guard('vendor')->user()->id }}";
        var defaultImg = "{{ asset('backend/images/default/profile-default.webp') }}";

        var channel = pusher.subscribe('vendor.' + vendorId);
        channel.bind('dashbord-push', function(data) {
            var object = data.message;

            document.querySelector(".header-notification-wrapper .bling-area").style.display = "block";
            document.querySelector(".notifications-clear-all-btn").style.display = "inline-block";

            var sound = document.getElementById("notification-sound");
            if (sound) {
                sound.currentTime = 0;
                sound.play().catch(function() {});
            }

            var notFound = document.querySelector(".notification-list .not-found");
            if (notFound) {
                notFound.remove();
            }

            var imgSrc = object.image || defaultImg;

            var li = document.createElement("li");
            li.style.animation = "fadeIn 0.3s ease";

            li.innerHTML = '<div class="thumb"><img src="' + imgSrc +
                '" alt="user"></div><div class="content"><div class="title-area"><h6 class="title">' + object
                .title + '</h6><span class="time">' + object.time + '</span></div><span class="sub-title">' + object
                .message + '</span></div>';

            var list = document.querySelector(".notification-wrapper .notification-list");
            if (list) {
                list.insertBefore(li, list.firstChild);
            }
        });
    </script>

    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endif
