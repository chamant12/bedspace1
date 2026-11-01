@extends('layout.app')
@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Commission due &ndash; {{ $property->property_name }}</h4>
            <a href="/admin/commission-dues" class="btn btn-light btn-sm">Back</a>
        </div>
        <p class="card-description">Owner: {{ $property->propertyOwner?->name }} &middot; {{ $property->propertyOwner?->email }} &middot; {{ $property->propertyOwner?->phone }}</p>

        @if($payments->isEmpty())
            <p>No commission is due from this hotel.</p>
        @endif

        {{-- One settlement per currency: LKR and USD commissions are never combined --}}
        @foreach($payments->groupBy(fn ($payment) => $payment->currency->code)->sortKeys() as $currency => $group)
        <h5 class="mt-3">{{ $currency }} commissions</h5>
        <form method="POST" action="/admin/commission-dues/{{ $property->id }}/settle" class="mb-4"
              onsubmit="return confirm('Record that the hotel has paid the {{ $currency }} commission on the selected payments?');">
            @csrf
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="select-all" checked></th>
                            <th>Paid</th><th>Booking</th><th>Guest</th><th>Method</th>
                            <th class="text-end">Payment ({{ $currency }})</th><th class="text-end">Commission ({{ $currency }})</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($group as $payment)
                        <tr>
                            <td><input type="checkbox" name="payment_ids[]" value="{{ $payment->id }}" class="row-check" data-amount="{{ $payment->commission_amount }}" checked></td>
                            <td>{{ $payment->paid_at->timezone(config('bedspace.timezone'))->format('Y-m-d') }}</td>
                            <td><a href="/reservations/{{ $payment->reservation->reference }}">{{ $payment->reservation->reference }}</a></td>
                            <td>{{ $payment->reservation->guest_name }}</td>
                            <td>{{ $payment->method->label() }}</td>
                            <td class="text-end">{{ number_format($payment->amount, 2) }}</td>
                            <td class="text-end">{{ number_format($payment->commission_amount, 2) }} <small class="text-muted">({{ number_format($payment->commission_rate, 2) }}%)</small></td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr><th colspan="6">Selected total ({{ $currency }})</th><th class="text-end selected-total">{{ number_format($group->sum('commission_amount'), 2) }}</th></tr>
                    </tfoot>
                </table>
            </div>
            <div class="row g-2 align-items-end mt-2">
                <div class="col-md-4">
                    <label>Payment reference (bank slip / transfer)</label>
                    <input type="text" name="reference" class="form-control" maxlength="100">
                </div>
                <div class="col-md-5">
                    <label>Note</label>
                    <input type="text" name="notes" class="form-control" maxlength="500">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-success">Record {{ $currency }} settlement</button>
                </div>
            </div>
        </form>
        @endforeach
    </div></div>
</div>

<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <h4 class="card-title">Settlements</h4>
        <table class="table table-sm">
            <thead><tr><th>Date</th><th class="text-end">Amount</th><th>Reference</th><th>Note</th><th>Recorded by</th></tr></thead>
            <tbody>
            @forelse($settlements as $settlement)
                <tr>
                    <td>{{ $settlement->settled_at->timezone(config('bedspace.timezone'))->format('Y-m-d') }}</td>
                    <td class="text-end">{{ $settlement->money() }}</td>
                    <td>{{ $settlement->reference }}</td>
                    <td>{{ $settlement->notes }}</td>
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
@section('scripts')
@include('admin._selectedTotal')
@stop
