@extends('layout.app')
@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">{{ $guest->name }}</h4>
            <a href="/guests" class="btn btn-light btn-sm">Back</a>
        </div>
        <p class="mb-1">{{ $guest->email }} &middot; {{ $guest->phone }}</p>
        @if($guest->address)<p class="mb-1">{{ $guest->address }}@if($guest->country), {{ $guest->country }}@endif</p>@endif
        <p class="text-muted">{{ $guest->user_id ? 'Has a guest account' : 'No guest account' }}</p>

        <table class="table table-striped">
            <thead><tr><th>Reference</th><th>Property</th><th>Check-in</th><th>Nights</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($reservations as $r)
                <tr>
                    <td><a href="/reservations/{{ $r->reference }}">{{ $r->reference }}</a></td>
                    <td>{{ $r->property->property_name }}</td>
                    <td>{{ $r->check_in->format('Y-m-d') }}</td>
                    <td>{{ $r->nights }}</td>
                    <td>{{ $r->money($r->total) }}</td>
                    <td><span class="badge {{ $r->status->badgeClass() }}">{{ $r->status->label() }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div></div>
</div>
@stop
