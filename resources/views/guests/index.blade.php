@extends('layout.app')
@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <h4 class="card-title">Guests</h4>
        <p class="card-description">Bookings by the same guest are linked by email, phone or name and address, with or without a guest account.</p>
        <form method="GET" class="row g-2 align-items-end mb-3">
            <div class="col-md-5">
                <input type="text" name="q" class="form-control" placeholder="Name, email or phone" value="{{ request('q') }}">
            </div>
            <div class="col-md-2"><button type="submit" class="btn btn-success">Search</button></div>
        </form>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Guest</th><th>Email</th><th>Phone</th><th>Country</th><th class="text-end">Bookings</th><th>Last check-in</th><th></th></tr></thead>
                <tbody>
                @forelse($guests as $guest)
                    <tr>
                        <td>{{ $guest->name }} @if($guest->bookings_count > 1)<span class="badge badge-info">Returning</span>@endif</td>
                        <td>{{ $guest->email }}</td>
                        <td>{{ $guest->phone }}</td>
                        <td>{{ $guest->country }}</td>
                        <td class="text-end">{{ $guest->bookings_count }}</td>
                        <td>{{ $guest->last_check_in ? \Carbon\Carbon::parse($guest->last_check_in)->format('Y-m-d') : '' }}</td>
                        <td><a href="/guests/{{ $guest->id }}" class="btn btn-sm btn-outline-primary">History</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7">No guests found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $guests->links() }}</div>
    </div></div>
</div>
@stop
