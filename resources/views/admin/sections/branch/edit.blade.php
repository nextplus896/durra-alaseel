@extends('admin.layouts.master')

@push('css')
    <style>
        #map {
            height: 400px;
            width: 100%;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .map-search-wrapper {
            position: relative;
            margin-bottom: 15px;
        }

        .map-search-wrapper input {
            padding-right: 40px;
        }

        .map-search-wrapper .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
    </style>
@endpush

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
        'active' => __('Edit Branch'),
    ])
@endsection

@section('content')
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __('Edit Branch') }} - {{ $branch->name }}</h6>
        </div>
        <div class="card-body">
            <form action="{{ setRoute('admin.branch.update', $branch->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row mb-10-none">
                    <div class="col-xl-12 col-lg-12 form-group">
                        <label>{{ __('Enable Delivery') }}</label>
                        <div class="d-flex align-items-center mt-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="delivery_enabled"
                                    id="delivery_enabled" value="1"
                                    {{ old('delivery_enabled', $branch->delivery_enabled) ? 'checked' : '' }}>
                                <label class="form-check-label" for="delivery_enabled">
                                    {{ __('Allow delivery to user locations') }}
                                </label>
                            </div>
                        </div>
                        <small
                            class="text-muted d-block mt-1">{{ __('When disabled, users must pick up from the branch.') }}</small>
                    </div>
                    <div class="col-xl-6 col-lg-6 form-group">
                        <label>{{ __('Branch Name') }}<span>*</span></label>
                        <input type="text" name="name" class="form--control"
                            placeholder="{{ __('Enter branch name') }}" value="{{ old('name', $branch->name) }}" required>
                    </div>
                    <div class="col-xl-6 col-lg-6 form-group">
                        <label>{{ __('Service Radius (km)') }}<span>*</span></label>
                        <input type="number" name="service_radius_km" class="form--control"
                            placeholder="{{ __('Enter service radius in km') }}"
                            value="{{ old('service_radius_km', $branch->service_radius_km) }}" step="0.1"
                            min="0.1" max="500" required>
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group">
                        <label>{{ __('Address') }}</label>
                        <textarea name="address" class="form--control" rows="2" placeholder="{{ __('Enter branch address') }}">{{ old('address', $branch->address) }}</textarea>
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group">
                        <label>{{ __('Select Location on Map') }}<span>*</span></label>
                        <div class="map-search-wrapper">
                            <input type="text" id="map-search" class="form--control"
                                placeholder="{{ __('Search for a location...') }}">
                            <i class="las la-search search-icon"></i>
                        </div>
                        <div id="map"></div>
                        <small
                            class="text-muted">{{ __('Click on the map to select location or search for an address') }}</small>
                    </div>
                    <div class="col-xl-6 col-lg-6 form-group">
                        <label>{{ __('Latitude') }}<span>*</span></label>
                        <input type="text" name="latitude" id="latitude" class="form--control"
                            placeholder="{{ __('Latitude') }}" value="{{ old('latitude', $branch->latitude) }}" readonly
                            required>
                    </div>
                    <div class="col-xl-6 col-lg-6 form-group">
                        <label>{{ __('Longitude') }}<span>*</span></label>
                        <input type="text" name="longitude" id="longitude" class="form--control"
                            placeholder="{{ __('Longitude') }}" value="{{ old('longitude', $branch->longitude) }}"
                            readonly required>
                    </div>

                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.button.form-btn', [
                            'class' => 'w-100 btn-loading',
                            'permission' => 'admin.branch.update',
                            'text' => __('Update Branch'),
                        ])
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY', '') }}&libraries=places&callback=initMap"
        async defer></script>
    <script>
        let map;
        let marker;
        let searchBox;

        function initMap() {
            // Existing branch location
            const branchLocation = {
                lat: parseFloat('{{ $branch->latitude }}'),
                lng: parseFloat('{{ $branch->longitude }}')
            };

            map = new google.maps.Map(document.getElementById("map"), {
                center: branchLocation,
                zoom: 14,
            });

            marker = new google.maps.Marker({
                position: branchLocation,
                map: map,
                draggable: true,
            });

            // Draw service radius circle
            new google.maps.Circle({
                strokeColor: "#4361ee",
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: "#4361ee",
                fillOpacity: 0.15,
                map: map,
                center: branchLocation,
                radius: parseFloat('{{ $branch->service_radius_km }}') * 1000, // Convert km to meters
            });

            // Update coordinates when marker is dragged
            marker.addListener('dragend', function(event) {
                updateCoordinates(event.latLng.lat(), event.latLng.lng());
            });

            // Update coordinates when map is clicked
            map.addListener('click', function(event) {
                marker.setPosition(event.latLng);
                updateCoordinates(event.latLng.lat(), event.latLng.lng());
            });

            // Initialize search box
            const input = document.getElementById('map-search');
            searchBox = new google.maps.places.SearchBox(input);

            // Listen for the event when user selects a prediction
            searchBox.addListener('places_changed', function() {
                const places = searchBox.getPlaces();

                if (places.length === 0) {
                    return;
                }

                const place = places[0];

                if (!place.geometry || !place.geometry.location) {
                    console.log("Returned place contains no geometry");
                    return;
                }

                // Set the marker position
                marker.setPosition(place.geometry.location);

                // Center map on the selected place
                map.setCenter(place.geometry.location);
                map.setZoom(15);

                // Update coordinates
                updateCoordinates(
                    place.geometry.location.lat(),
                    place.geometry.location.lng()
                );
            });
        }

        function updateCoordinates(lat, lng) {
            document.getElementById('latitude').value = lat.toFixed(8);
            document.getElementById('longitude').value = lng.toFixed(8);
        }
    </script>
@endpush
