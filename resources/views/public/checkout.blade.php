@extends('layout.ext-app')
@section('content')
<div class="container bs-page">
    <h2>Complete your booking</h2>
    <div class="row">
        <div class="col-md-7">
            <form method="POST" action="/book" class="bs-card">
                @csrf
                @foreach(['property_id' => $property->id, 'roomType_id' => $roomType->id, 'occupancy_id' => $occupancy->id,
                          'mealType_id' => $mealType->id, 'check_in' => $check_in, 'check_out' => $check_out, 'rooms' => $rooms] as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach

                <h5>Your details</h5>
                <div class="form-group">
                    <label>Full name<span class="text-danger">*</span></label>
                    <input type="text" name="guest_name" class="form-control" required maxlength="255" value="{{ old('guest_name', $account?->name) }}">
                </div>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Email<span class="text-danger">*</span></label>
                        <input type="email" name="guest_email" class="form-control" required maxlength="255" value="{{ old('guest_email', $account?->email) }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Mobile<span class="text-danger">*</span></label>
                        <input type="text" name="guest_phone" class="form-control" required maxlength="50" value="{{ old('guest_phone', $account?->phone) }}">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-8">
                        <label>Address</label>
                        <input type="text" name="guest_address" class="form-control" maxlength="500" value="{{ old('guest_address') }}">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Country</label>
                        <input type="text" name="guest_country" class="form-control" maxlength="100" value="{{ old('guest_country') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label>Special requests</label>
                    <textarea name="special_requests" class="form-control" rows="3" maxlength="1000">{{ old('special_requests') }}</textarea>
                </div>

                <h5 class="mt-3">Payment</h5>
                @foreach($paymentMethods as $method)
                    <label class="d-block">
                        <input type="radio" name="payment_method" value="{{ $method->value }}" @checked($loop->first) required> {{ $method->label() }}
                    </label>
                @endforeach

                @unless($account)
                    <h5 class="mt-3">Account (optional)</h5>
                    <label class="d-block">
                        <input type="checkbox" name="create_account" value="1" id="create_account" @checked(old('create_account'))>
                        Create an account to see your bookings later
                    </label>
                    <div class="form-group" id="password_group" style="{{ old('create_account') ? '' : 'display:none;' }}">
                        <label>Password (at least 8 characters)</label>
                        <input type="password" name="password" class="form-control" minlength="8" autocomplete="new-password">
                    </div>
                    <p class="bs-muted small">Already have an account? <a href="/account/login">Sign in</a></p>
                @endunless

                <h5 class="mt-3">Terms and conditions</h5>
                <div class="border rounded p-3 mb-2" style="max-height:220px; overflow-y:auto; background:#fafafa;">
                    <p class="mb-2"><strong>Cancellation policy.</strong> {{ $cancellationPolicy }}
                        Refunds are made to the original payment method.</p>
                    <p class="mb-2"><strong>Payment.</strong> Online payments are taken when you book. If you choose to pay at the
                        hotel, the full amount is payable at the property.</p>
                    <p class="mb-2"><strong>Rates.</strong> Prices are for the rooms, guests and meal plan shown and include all
                        nights of the stay. Local (LKR) rates may require a Sri Lankan NIC at check-in.</p>
                    <p class="mb-0"><strong>Changes.</strong> To change or cancel a booking, use your booking page or contact the property,
                        quoting your booking reference.</p>
                </div>
                <label class="d-block">
                    <input type="checkbox" name="accept_terms" value="1" required @checked(old('accept_terms'))>
                    I have read and accept the terms and the cancellation policy<span class="text-danger">*</span>
                </label>

                <button type="submit" class="btn btn-primary btn-lg mt-2">Confirm booking</button>
            </form>
        </div>

        <div class="col-md-5">
            <div class="bs-card">
                <h5>{{ $property->property_name }}</h5>
                <p class="mb-1"><strong>{{ $roomType->roomType }}</strong> &times; {{ $rooms }}</p>
                <p class="mb-1">{{ $occupancy->occupancy_name }}, {{ $mealType->mealType }}</p>
                <p class="bs-muted">{{ $check_in }} &rarr; {{ $check_out }} ({{ $quote['nights'] }} night{{ $quote['nights'] > 1 ? 's' : '' }})</p>
                <table class="table table-sm">
                    @foreach($quote['nightly'] as $date => $price)
                        <tr><td>{{ $date }}</td><td class="text-end">{{ number_format($price * $rooms, 2) }}</td></tr>
                    @endforeach
                    @foreach($quote['discounts'] as $discount)
                        <tr class="text-success"><td>{{ $discount['description'] }}</td><td class="text-end">&minus; {{ number_format($discount['amount'], 2) }}</td></tr>
                    @endforeach
                    <tr><th>Total</th><th class="text-end">{{ $quote['currency']->code }} {{ number_format($quote['total'], 2) }}</th></tr>
                </table>
            </div>
        </div>
    </div>
</div>
@stop
@section('styles')
@include('public._styles')
@stop
@section('scripts')
<script>
$(function () {
    $('#create_account').on('change', function () { $('#password_group').toggle(this.checked); });
});
</script>
@stop
