@extends('layout.app')
@section('content')
<div class="row">
    @forelse($totals as $currency => $amount)
    <div class="col-sm-4 grid-margin stretch-card">
        <div class="card card-rounded"><div class="card-body">
            <p class="statistics-title">Owed to hotels for online payments ({{ $currency }})</p>
            <h3 class="rate-percentage">{{ number_format($amount, 2) }}</h3>
        </div></div>
    </div>
    @empty
    <div class="col-sm-4 grid-margin stretch-card">
        <div class="card card-rounded"><div class="card-body">
            <p class="statistics-title">Owed to hotels for online payments</p>
            <h3 class="rate-percentage">0.00</h3>
        </div></div>
    </div>
    @endforelse
</div>

<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <h4 class="card-title">Hotel payouts</h4>
        <p class="card-description">Online payments collected by the platform, less commission, not yet paid out. Bookings with a refund due are not included.
            Hotels are paid in the currency the guest paid in; LKR and USD are paid out separately.</p>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Hotel</th><th>Owner</th><th>Currency</th><th class="text-end">Payments</th><th class="text-end">Payable</th><th></th></tr></thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->property->property_name }}</td>
                        <td>{{ $row->property->propertyOwner?->name }}<br/><small class="text-muted">{{ $row->property->propertyOwner?->phone }}</small></td>
                        <td>{{ $row->currency }}</td>
                        <td class="text-end">{{ $row->payments }}</td>
                        <td class="text-end"><strong>{{ $row->currency }} {{ number_format($row->payable, 2) }}</strong></td>
                        <td><a href="/admin/payouts/{{ $row->property->id }}" class="btn btn-sm btn-primary">Details / pay out</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6">Nothing is owed to hotels.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div></div>
</div>

<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <h4 class="card-title">Recent payouts</h4>
        <table class="table table-sm">
            <thead><tr><th>Date</th><th>Hotel</th><th class="text-end">Amount</th><th>Reference</th><th>Recorded by</th></tr></thead>
            <tbody>
            @forelse($recent as $payout)
                <tr>
                    <td>{{ $payout->paid_at->timezone(config('bedspace.timezone'))->format('Y-m-d') }}</td>
                    <td>{{ $payout->property->property_name }}</td>
                    <td class="text-end">{{ $payout->money() }}</td>
                    <td>{{ $payout->reference }}</td>
                    <td>{{ $payout->recordedBy?->name }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No payouts yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@stop
