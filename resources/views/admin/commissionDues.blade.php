@extends('layout.app')
@section('content')
<div class="row">
    @forelse($totals as $currency => $amount)
    <div class="col-sm-4 grid-margin stretch-card">
        <div class="card card-rounded"><div class="card-body">
            <p class="statistics-title">Commission due from hotels ({{ $currency }})</p>
            <h3 class="rate-percentage">{{ number_format($amount, 2) }}</h3>
        </div></div>
    </div>
    @empty
    <div class="col-sm-4 grid-margin stretch-card">
        <div class="card card-rounded"><div class="card-body">
            <p class="statistics-title">Commission due from hotels</p>
            <h3 class="rate-percentage">0.00</h3>
        </div></div>
    </div>
    @endforelse
</div>

<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <h4 class="card-title">Commission dues by hotel</h4>
        <p class="card-description">Commission ({{ number_format(config('bedspace.commission_rate'), 2) }}% for new bookings) on payments the hotels collected themselves (pay at hotel), not yet settled.
            Commission is due in the currency the guest paid in; LKR and USD are settled separately.</p>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Hotel</th><th>Owner</th><th>Currency</th><th class="text-end">Payments</th><th class="text-end">Due</th><th></th></tr></thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->property->property_name }}</td>
                        <td>{{ $row->property->propertyOwner?->name }}<br/><small class="text-muted">{{ $row->property->propertyOwner?->phone }}</small></td>
                        <td>{{ $row->currency }}</td>
                        <td class="text-end">{{ $row->payments }}</td>
                        <td class="text-end"><strong>{{ $row->currency }} {{ number_format($row->due, 2) }}</strong></td>
                        <td><a href="/admin/commission-dues/{{ $row->property->id }}" class="btn btn-sm btn-primary">Details / settle</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6">No commission is due.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div></div>
</div>

<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <h4 class="card-title">Recent settlements</h4>
        <table class="table table-sm">
            <thead><tr><th>Date</th><th>Hotel</th><th class="text-end">Amount</th><th>Reference</th><th>Recorded by</th></tr></thead>
            <tbody>
            @forelse($settlements as $settlement)
                <tr>
                    <td>{{ $settlement->settled_at->timezone(config('bedspace.timezone'))->format('Y-m-d') }}</td>
                    <td>{{ $settlement->property->property_name }}</td>
                    <td class="text-end">{{ $settlement->money() }}</td>
                    <td>{{ $settlement->reference }}</td>
                    <td>{{ $settlement->recordedBy?->name }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No settlements yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@stop
