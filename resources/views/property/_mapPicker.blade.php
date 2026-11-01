{{-- Pin the property on a map. $latitude, $longitude (nullable). Scripts: property._mapPickerScript --}}
<div class="form-group">
    <label>Location on the map</label>
    <p class="text-muted small mb-2">Search for the place or click the map, then drag the pin to the exact entrance. Guests see this pin on Google Maps.</p>
    <div class="d-flex flex-wrap gap-2 mb-2">
        <input type="text" id="map-search" class="form-control" style="max-width:360px;" placeholder="Search a place, e.g. Ella Rock Hotel, Ella">
        <button type="button" id="map-search-btn" class="btn btn-outline-primary">Find</button>
        <button type="button" id="map-locate-btn" class="btn btn-outline-secondary">Use my current location</button>
        <button type="button" id="map-clear-btn" class="btn btn-outline-danger">Remove pin</button>
    </div>
    <div id="property-map" class="property-map" role="application" aria-label="Map: click to place the property pin"></div>
    <small id="map-message" class="d-block mt-1 text-muted" role="status" aria-live="polite"></small>
    <div class="row mt-2">
        <div class="col-6 col-md-3">
            <label class="small mb-0" for="latitude">Latitude</label>
            <input type="number" step="0.0000001" name="latitude" id="latitude" class="form-control form-control-sm" value="{{ old('latitude', $latitude) }}">
        </div>
        <div class="col-6 col-md-3">
            <label class="small mb-0" for="longitude">Longitude</label>
            <input type="number" step="0.0000001" name="longitude" id="longitude" class="form-control form-control-sm" value="{{ old('longitude', $longitude) }}">
        </div>
    </div>
</div>
