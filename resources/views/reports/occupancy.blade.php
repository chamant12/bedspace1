@extends('layout.app')
@section('content')
@php($fmt = fn ($v) => number_format($v, 2))
@php($available = $rows->sum('available_nights'))
@php($sold = $rows->sum('sold_nights'))
@php($revenue = $rows->sum('revenue_lkr'))
<div class="row">
    @foreach([
        'Occupancy' => ($available > 0 ? round(min(100, $sold / $available * 100), 1) : 0).' %',
        'Room-nights sold' => number_format($sold),
        'Room revenue (LKR)' => $fmt($revenue),
        'RevPAR (LKR)' => $fmt($available > 0 ? $revenue / $available : 0),
    ] as $label => $value)
    <div class="col-sm-6 col-lg-3 grid-margin stretch-card">
        <div class="card card-rounded"><div class="card-body">
            <p class="statistics-title">{{ $label }}</p>
            <h3 class="rate-percentage">{{ $value }}</h3>
        </div></div>
    </div>
    @endforeach
</div>

<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <h4 class="card-title">Occupancy report</h4>
        @include('reports._monthPicker', ['note' => 'Available room-nights count only dates open in the rates calendar (total rooms less closed rooms). Revenue is from confirmed and stayed bookings for nights in '.\Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y').', before promotion discounts, in LKR at each booking\'s rate.'])
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Property</th><th>Room type</th><th class="text-end">Rooms</th><th class="text-end">Available</th><th class="text-end">Sold</th>
                        <th class="text-end">Occupancy</th><th class="text-end">Revenue (LKR)</th><th class="text-end">ADR</th><th class="text-end">RevPAR</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->property_name }}</td>
                        <td>{{ $row->room_type }}</td>
                        <td class="text-end">{{ $row->rooms }}</td>
                        <td class="text-end">{{ number_format($row->available_nights) }}</td>
                        <td class="text-end">{{ number_format($row->sold_nights) }}</td>
                        <td class="text-end"><strong>{{ $row->occupancy }} %</strong></td>
                        <td class="text-end">{{ $fmt($row->revenue_lkr) }}</td>
                        <td class="text-end">{{ $fmt($row->adr_lkr) }}</td>
                        <td class="text-end">{{ $fmt($row->revpar_lkr) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9">No rooms set up.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="text-muted mt-2 mb-0">ADR = average room rate per sold room-night. RevPAR = room revenue per available room-night.</p>
    </div></div>
</div>
@stop
