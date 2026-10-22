@extends('layout.app')
@section('content')
<div class="col-md-8 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">New booking</h4>
            <p class="card-description">For walk-in, phone or email bookings. Rates, availability and promotions are applied exactly as for online bookings; payment is at the property.</p>
            @if($properties->isEmpty())
                <p>You have no active properties.</p>
            @else
            <form method="POST" action="/reservations">
                @csrf
                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Property<span class="text-danger">*</span></label>
                        <select name="property_id" class="form-control" required>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}" @selected(old('property_id') == $property->id)>{{ $property->property_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Room type<span class="text-danger">*</span></label>
                        <select name="roomType_id" class="form-control" required>
                            @foreach($roomTypes as $roomType)
                                <option value="{{ $roomType->id }}" @selected(old('roomType_id') == $roomType->id)>{{ $roomType->roomType }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Guests per room<span class="text-danger">*</span></label>
                        <select name="occupancy_id" class="form-control" required>
                            @foreach($occupancies as $occupancy)
                                <option value="{{ $occupancy->id }}" @selected(old('occupancy_id') == $occupancy->id)>{{ $occupancy->occupancy_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Meal plan<span class="text-danger">*</span></label>
                        <select name="mealType_id" class="form-control" required>
                            @foreach($mealTypes as $mealType)
                                <option value="{{ $mealType->id }}" @selected(old('mealType_id') == $mealType->id)>{{ $mealType->mealType }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Rooms<span class="text-danger">*</span></label>
                        <input type="number" name="rooms" class="form-control" min="1" max="{{ \App\Services\BookingQuotes::MAX_ROOMS }}" value="{{ old('rooms', 1) }}" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Check-in<span class="text-danger">*</span></label>
                        <input type="date" name="check_in" class="form-control" min="{{ $today }}" value="{{ old('check_in', $today) }}" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Check-out<span class="text-danger">*</span></label>
                        <input type="date" name="check_out" class="form-control" min="{{ $today }}" value="{{ old('check_out') }}" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Rate<span class="text-danger">*</span></label>
                        <select name="currency" class="form-control" required>
                            @foreach($currencies as $currency)
                                <option value="{{ $currency->code }}" @selected(old('currency') === $currency->code)>{{ $currency->code }} ({{ $currency->code === 'LKR' ? 'local' : 'foreign' }} rate)</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <h5 class="mt-2">Guest</h5>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Full name<span class="text-danger">*</span></label>
                        <input type="text" name="guest_name" class="form-control" required maxlength="255" value="{{ old('guest_name') }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Email<span class="text-danger">*</span></label>
                        <input type="email" name="guest_email" class="form-control" required maxlength="255" value="{{ old('guest_email') }}">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Mobile<span class="text-danger">*</span></label>
                        <input type="text" name="guest_phone" class="form-control" required maxlength="50" value="{{ old('guest_phone') }}">
                    </div>
                    <div class="form-group col-md-5">
                        <label>Address</label>
                        <input type="text" name="guest_address" class="form-control" maxlength="500" value="{{ old('guest_address') }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Country</label>
                        <input type="text" name="guest_country" class="form-control" maxlength="100" value="{{ old('guest_country') }}">
                    </div>
                    <div class="form-group col-md-12">
                        <label>Special requests</label>
                        <textarea name="special_requests" class="form-control" rows="2" maxlength="1000">{{ old('special_requests') }}</textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Create booking</button>
                <a href="/reservations" class="btn btn-light">Cancel</a>
            </form>
            @endif
        </div>
    </div>
</div>
@stop
