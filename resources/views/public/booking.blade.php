@extends('layout.ext-app')
@section('content')
@php($r = $reservation)
<div class="container bs-page">
    <h2 class="mb-1">Booking {{ $r->reference }}</h2>
    <p><span class="badge badge-primary">{{ $r->status->label() }}</span></p>

    <div class="row">
        <div class="col-md-7">
            <div class="bs-card">
                <h5>{{ $r->property->property_name }}</h5>
                <p class="bs-muted">{{ $r->property->address }}</p>
                <p class="mb-1"><strong>Check-in:</strong> {{ $r->check_in->format('D, d M Y') }}</p>
                <p class="mb-1"><strong>Check-out:</strong> {{ $r->check_out->format('D, d M Y') }}</p>
                <p class="mb-1"><strong>Guest:</strong> {{ $r->guest_name }} &middot; {{ $r->guest_email }} &middot; {{ $r->guest_phone }}</p>
                @if($r->special_requests)<p class="mb-1"><strong>Requests:</strong> {{ $r->special_requests }}</p>@endif
                <p class="mb-1"><strong>Payment:</strong> {{ $r->payment_method->label() }}</p>
                @if($r->property->primaryContact)
                    <p class="bs-muted mt-2">Property contact: {{ $r->property->primaryContact->name }}, {{ $r->property->primaryContact->mobile }}</p>
                @endif
            </div>
        </div>
        <div class="col-md-5">
            <div class="bs-card">
                <h5>Invoice</h5>
                <table class="table table-sm">
                    @foreach($r->rooms as $line)
                        <tr>
                            <td>{{ $line->roomType->roomType }} &times; {{ $line->rooms }}<br/>
                                <small class="bs-muted">{{ $line->occupancy->occupancy_name }}, {{ $line->mealType->mealType }}</small></td>
                            <td class="text-end">{{ number_format($line->subtotal, 2) }}</td>
                        </tr>
                        @foreach($line->discounts as $discount)
                            <tr class="text-success"><td>{{ $discount->description }}</td><td class="text-end">&minus; {{ number_format($discount->amount, 2) }}</td></tr>
                        @endforeach
                    @endforeach
                    <tr><th>Total</th><th class="text-end">{{ $r->money($r->total) }}</th></tr>
                </table>
                @if($r->payment_method === \App\Enums\PaymentMethod::PayAtHotel)
                    <p class="bs-muted small mb-0">Payable at the property.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@stop
@section('styles')
@include('public._styles')
@stop
