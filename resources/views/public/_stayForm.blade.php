{{-- Stay search form (dates, guests per room, rooms). $action, $stay; optional $districts --}}
<form method="GET" action="{{ $action }}" class="row g-2 align-items-end bs-stay-form">
    @isset($districts)
    <div class="col-md-3">
        <label>Where</label>
        <select name="district_id" class="form-control">
            <option value="">Anywhere in Sri Lanka</option>
            @foreach($districts as $district)
                <option value="{{ $district->id }}" @selected((string) $stay['district_id'] === (string) $district->id)>{{ $district->district_name }}</option>
            @endforeach
        </select>
    </div>
    @endisset
    <div class="col-md-2">
        <label>Check-in</label>
        <input type="text" name="check_in" class="form-control bs-date" value="{{ $stay['check_in'] }}" required autocomplete="off">
    </div>
    <div class="col-md-2">
        <label>Check-out</label>
        <input type="text" name="check_out" class="form-control bs-date" value="{{ $stay['check_out'] }}" required autocomplete="off">
    </div>
    <div class="col-md-2">
        <label>Guests per room</label>
        <select name="guests" class="form-control">
            <option value="">Any</option>
            @foreach($occupancies as $occupancy)
                <option value="{{ $occupancy->occupancy }}" @selected((int) $stay['guests'] === (int) $occupancy->occupancy)>{{ $occupancy->occupancy }} ({{ $occupancy->occupancy_name }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-1">
        <label>Rooms</label>
        <input type="number" name="rooms" class="form-control" min="1" max="{{ \App\Services\BookingQuotes::MAX_ROOMS }}" value="{{ $stay['rooms'] }}">
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">Search</button>
    </div>
</form>
