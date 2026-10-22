@extends('layout.ext-app')
@section('content')
<div class="container bs-page">
    <div class="d-flex justify-content-between align-items-center">
        <h2>My bookings</h2>
        <form method="POST" action="/logout">@csrf<button class="btn btn-light">Sign out</button></form>
    </div>
    @forelse($reservations as $r)
        <div class="bs-card d-flex justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="mb-1">{{ $r->property->property_name }}</h5>
                <div class="bs-muted">{{ $r->check_in->format('d M Y') }} &rarr; {{ $r->check_out->format('d M Y') }} &middot; {{ $r->reference }}</div>
            </div>
            <div class="text-end">
                <span class="badge badge-primary">{{ $r->status->label() }}</span>
                <div class="bs-price">{{ $r->money($r->total) }}</div>
                <a href="/booking/{{ $r->reference }}" class="btn btn-sm btn-outline-primary">Details</a>
            </div>
        </div>
    @empty
        <p>You have no bookings yet. <a href="/search">Find a place to stay</a></p>
    @endforelse
</div>
@stop
@section('styles')
@include('public._styles')
@stop
