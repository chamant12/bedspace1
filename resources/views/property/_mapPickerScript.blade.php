{{-- Map for property/_mapPicker: Google Maps when GOOGLE_MAPS_API_KEY is set, otherwise OpenStreetMap (Leaflet) --}}
@php($mapsKey = config('services.google_maps.key'))
<style>
.property-map { height: 360px; border: 1px solid #dee2e6; border-radius: 8px; }
</style>
@unless($mapsKey)
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endunless
<script>
(function () {
    var SRI_LANKA = { lat: 7.8731, lng: 80.7718 };
    var latInput = document.getElementById('latitude');
    var lngInput = document.getElementById('longitude');
    var message = document.getElementById('map-message');
    var provider;

    function say(text, cls) {
        message.textContent = text || '';
        message.className = 'd-block mt-1 small ' + (cls || 'text-muted');
    }
    function inSriLanka(lat, lng) {
        return lat >= 5.5 && lat <= 10.2 && lng >= 79.3 && lng <= 82.2;
    }
    function setInputs(lat, lng) {
        latInput.value = lat === null ? '' : lat.toFixed(7);
        lngInput.value = lng === null ? '' : lng.toFixed(7);
        if (lat !== null && !inSriLanka(lat, lng)) {
            say('This pin is outside Sri Lanka. Please move it to the property.', 'text-danger');
        } else if (lat !== null) {
            say('Pin set. Drag it to adjust.', 'text-success');
        }
    }
    function place(lat, lng, zoom) {
        provider.setMarker(lat, lng);
        provider.center(lat, lng, zoom);
        setInputs(lat, lng);
    }

    // ---- Google Maps ----
    var google_ = {
        init: function (el, lat, lng, zoom) {
            this.map = new google.maps.Map(el, { center: { lat: lat, lng: lng }, zoom: zoom, mapTypeControl: false, streetViewControl: false });
            this.map.addListener('click', function (e) { place(e.latLng.lat(), e.latLng.lng()); });
        },
        setMarker: function (lat, lng) {
            if (!this.marker) {
                this.marker = new google.maps.Marker({ map: this.map, draggable: true });
                this.marker.addListener('dragend', function (e) { setInputs(e.latLng.lat(), e.latLng.lng()); });
            }
            this.marker.setPosition({ lat: lat, lng: lng });
            this.marker.setMap(this.map);
        },
        removeMarker: function () { if (this.marker) this.marker.setMap(null); },
        center: function (lat, lng, zoom) {
            this.map.panTo({ lat: lat, lng: lng });
            if (zoom) this.map.setZoom(zoom);
        },
        search: function (query, done) {
            new google.maps.Geocoder().geocode({ address: query, componentRestrictions: { country: 'LK' } }, function (results, status) {
                status === 'OK' && results.length
                    ? done(results[0].geometry.location.lat(), results[0].geometry.location.lng())
                    : done(null);
            });
        }
    };

    // ---- OpenStreetMap (no API key needed) ----
    var leaflet_ = {
        init: function (el, lat, lng, zoom) {
            this.map = L.map(el).setView([lat, lng], zoom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(this.map);
            this.map.on('click', function (e) { place(e.latlng.lat, e.latlng.lng); });
        },
        setMarker: function (lat, lng) {
            var self = this;
            if (!this.marker) {
                this.marker = L.marker([lat, lng], { draggable: true });
                this.marker.on('dragend', function () { var p = self.marker.getLatLng(); setInputs(p.lat, p.lng); });
            }
            this.marker.setLatLng([lat, lng]);
            if (!this.map.hasLayer(this.marker)) this.marker.addTo(this.map);
        },
        removeMarker: function () { if (this.marker) this.map.removeLayer(this.marker); },
        center: function (lat, lng, zoom) { this.map.setView([lat, lng], zoom || this.map.getZoom()); },
        search: function (query, done) {
            fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=lk&q=' + encodeURIComponent(query), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (d) { d.length ? done(parseFloat(d[0].lat), parseFloat(d[0].lon)) : done(null); })
                .catch(function () { done(null); });
        }
    };

    function start(chosen) {
        provider = chosen;
        var lat = parseFloat(latInput.value), lng = parseFloat(lngInput.value);
        var hasPin = !isNaN(lat) && !isNaN(lng);
        provider.init(document.getElementById('property-map'), hasPin ? lat : SRI_LANKA.lat, hasPin ? lng : SRI_LANKA.lng, hasPin ? 16 : 7);
        if (hasPin) provider.setMarker(lat, lng);
        else say('No pin yet. Search for the place or click the map.');

        function search() {
            var query = document.getElementById('map-search').value.trim();
            if (!query) return;
            say('Searching…');
            provider.search(query, function (foundLat, foundLng) {
                if (foundLat === null) {
                    say('Place not found. Try another name, or click the map.', 'text-danger');
                    return;
                }
                place(foundLat, foundLng, 16);
                say('Found. Drag the pin to the exact entrance if needed.', 'text-success');
            });
        }
        document.getElementById('map-search-btn').addEventListener('click', search);
        document.getElementById('map-search').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); search(); } // do not submit the property form
        });
        document.getElementById('map-locate-btn').addEventListener('click', function () {
            if (!navigator.geolocation) { say('Your browser cannot share its location.', 'text-danger'); return; }
            say('Finding your location…');
            navigator.geolocation.getCurrentPosition(
                function (pos) { place(pos.coords.latitude, pos.coords.longitude, 17); },
                function () { say('Could not get your location. Allow location access, or search instead.', 'text-danger'); },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        });
        document.getElementById('map-clear-btn').addEventListener('click', function () {
            provider.removeMarker();
            setInputs(null, null);
            say('Pin removed. Save the form to keep this change.');
        });
        // Typing coordinates moves the pin
        [latInput, lngInput].forEach(function (input) {
            input.addEventListener('change', function () {
                var la = parseFloat(latInput.value), lo = parseFloat(lngInput.value);
                if (!isNaN(la) && !isNaN(lo)) place(la, lo);
            });
        });
    }

    @if($mapsKey)
    window.initPropertyMap = function () { start(google_); };
    @else
    start(leaflet_);
    @endif
})();
</script>
@if($mapsKey)
<script src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($mapsKey) }}&callback=initPropertyMap&loading=async" async defer></script>
@endif
