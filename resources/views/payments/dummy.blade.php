@extends('layout.ext-app')
@section('content')
@php($r = $payment->reservation)
<div class="container bs-page" style="max-width:560px;">
    <div class="alert alert-warning"><strong>Test payment page.</strong> No real card is charged. This will be replaced by the Sampath Bank payment gateway.</div>
    <div class="bs-card">
        <h4>Pay {{ $payment->currency->code }} {{ number_format($payment->amount, 2) }}</h4>
        <p class="bs-muted mb-3">Booking {{ $r->reference }} &middot; {{ $r->property->property_name }}</p>

        @if($payment->status !== \App\Models\Payment::PENDING)
            <p>This payment has already been processed ({{ $payment->status }}).</p>
        @else
            <form method="POST" action="{{ $resultUrl }}">
                @csrf
                <div class="form-group">
                    <label>Card number (test)</label>
                    <input type="text" class="form-control" value="4111 1111 1111 1111" readonly>
                </div>
                <div class="row">
                    <div class="form-group col-6"><label>Expiry</label><input type="text" class="form-control" value="12/30" readonly></div>
                    <div class="form-group col-6"><label>CVV</label><input type="text" class="form-control" value="123" readonly></div>
                </div>
                <button type="submit" name="result" value="approve" class="btn btn-success">Approve payment</button>
                <button type="submit" name="result" value="decline" class="btn btn-outline-danger">Decline</button>
            </form>
        @endif
    </div>
</div>
@stop
@section('styles')
@include('public._styles')
@stop
