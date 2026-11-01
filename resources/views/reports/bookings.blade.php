@extends('layout.app')
@section('content')
@php($total = $rows->sum('bookings'))
<div class="row">
    @foreach([
        'Bookings made' => number_format($total),
        'Cancellation rate' => ($total > 0 ? round($rows->sum('cancelled') / $total * 100, 1) : 0).' %',
        'Booking value (LKR)' => number_format($rows->sum('value_lkr'), 2),
        'Booking value (USD)' => number_format($rows->sum('value_usd'), 2),
        'Returning guests' => number_format($rows->sum('returning_guests')),
    ] as $label => $value)
    <div class="col-sm-6 col-lg grid-margin stretch-card">
        <div class="card card-rounded"><div class="card-body">
            <p class="statistics-title">{{ $label }}</p>
            <h3 class="rate-percentage">{{ $value }}</h3>
        </div></div>
    </div>
    @endforeach
</div>

<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <h4 class="card-title">Bookings report</h4>
        @include('reports._monthPicker', ['note' => 'Bookings made in '.\Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y').' (Sri Lanka time), whatever their stay dates. Value excludes cancelled and expired bookings; LKR and USD bookings are totalled separately. Returning guests are recognised by email, phone or name and address, with or without an account.'])
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Property</th><th class="text-end">Bookings</th><th class="text-end">Confirmed / stayed</th><th class="text-end">Cancelled</th>
                        <th class="text-end">No-show</th><th class="text-end">Website / hotel</th><th class="text-end">Online / at hotel</th>
                        <th class="text-end">Value (LKR)</th><th class="text-end">Value (USD)</th><th class="text-end">Avg nights</th><th class="text-end">Booked ahead (days)</th><th class="text-end">New / returning</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->property_name }}</td>
                        <td class="text-end">{{ $row->bookings }}</td>
                        <td class="text-end">{{ $row->confirmed }}</td>
                        <td class="text-end">{{ $row->cancelled }} <small class="text-muted">({{ $row->cancellation_rate }}%)</small></td>
                        <td class="text-end">{{ $row->no_show }}</td>
                        <td class="text-end">{{ $row->web }} / {{ $row->walk_in }}</td>
                        <td class="text-end">{{ $row->online_payment }} / {{ $row->pay_at_hotel }}</td>
                        <td class="text-end">{{ number_format($row->value_lkr, 2) }}</td>
                        <td class="text-end">{{ number_format($row->value_usd, 2) }}</td>
                        <td class="text-end">{{ $row->avg_nights }}</td>
                        <td class="text-end">{{ $row->avg_lead_days }}</td>
                        <td class="text-end">{{ $row->new_guests }} / {{ $row->returning_guests }}</td>
                    </tr>
                @empty
                    <tr><td colspan="12">No bookings were made in this month.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div></div>
</div>
@stop
