@extends('layout.app')
@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <h4 class="card-title">Reservations</h4>
                @if(auth()->user()->isPartner())
                    <a href="/reservations/new" class="btn btn-primary btn-sm text-white">New booking</a>
                @endif
            </div>

            <form method="GET" action="/reservations" class="row g-2 align-items-end mb-3">
                <div class="col-md-3">
                    <label>Search</label>
                    <input type="text" name="q" class="form-control" placeholder="Reference, name, email, phone" value="{{ request('q') }}">
                </div>
                <div class="col-md-2">
                    <label>Property</label>
                    <select name="property_id" class="form-control">
                        <option value="">All</option>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}" @selected(request('property_id') == $property->id)>{{ $property->property_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">All</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Check-in from</label>
                    <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                </div>
                <div class="col-md-2">
                    <label>to</label>
                    <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-success">Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Guest</th>
                            <th>Property</th>
                            <th>Check-in</th>
                            <th>Nights</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reservations as $r)
                        <tr>
                            <td><a href="/reservations/{{ $r->reference }}">{{ $r->reference }}</a></td>
                            <td>{{ $r->guest_name }}<br/><small class="text-muted">{{ $r->guest_phone }}</small></td>
                            <td>{{ $r->property->property_name }}</td>
                            <td>{{ $r->check_in->format('Y-m-d') }}</td>
                            <td>{{ $r->nights }}</td>
                            <td>{{ $r->money($r->total) }}</td>
                            <td><span class="badge {{ $r->status->badgeClass() }}">{{ $r->status->label() }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="7">No reservations found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $reservations->links() }}</div>
        </div>
    </div>
</div>
@stop
