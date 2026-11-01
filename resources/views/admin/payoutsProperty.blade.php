@extends('layout.app')
@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Payout &ndash; {{ $property->property_name }}</h4>
            <a href="/admin/payouts" class="btn btn-light btn-sm">Back</a>
        </div>
        <p class="card-description">Owner: {{ $property->propertyOwner?->name }} &middot; {{ $property->propertyOwner?->email }} &middot; {{ $property->propertyOwner?->phone }}</p>

        @if($payments->isEmpty())
            <p>Nothing is owed to this hotel.</p>
        @endif

        {{-- One payout per currency: LKR and USD are never combined --}}
        @foreach($payments->groupBy(fn ($payment) => $payment->currency->code)->sortKeys() as $currency => $group)
        <h5 class="mt-3">{{ $currency }} payments</h5>
        <form method="POST" action="/admin/payouts/{{ $property->id }}/pay" class="mb-4"
              onsubmit="return confirm('Record that the selected {{ $currency }} amounts have been paid to the hotel?');">
            @csrf
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="select-all" checked></th>
                            <th>Paid by guest</th><th>Booking</th><th>Guest</th>
                            <th class="text-end">Payment ({{ $currency }})</th><th class="text-end">Commission ({{ $currency }})</th><th class="text-end">To hotel ({{ $currency }})</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($group as $payment)
                        @php($payable = \App\Services\HotelPayouts::payable($payment))
                        <tr>
                            <td><input type="checkbox" name="payment_ids[]" value="{{ $payment->id }}" class="row-check" data-amount="{{ $payable }}" checked></td>
                            <td>{{ $payment->paid_at->timezone(config('bedspace.timezone'))->format('Y-m-d') }}</td>
                            <td><a href="/reservations/{{ $payment->reservation->reference }}">{{ $payment->reservation->reference }}</a>
                                <br/><small class="text-muted">{{ $payment->reservation->status->label() }}</small></td>
                            <td>{{ $payment->reservation->guest_name }}</td>
                            <td class="text-end">{{ number_format($payment->amount, 2) }}</td>
                            <td class="text-end">{{ number_format($payment->commission_amount, 2) }}</td>
                            <td class="text-end"><strong>{{ number_format($payable, 2) }}</strong></td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr><th colspan="6">Selected total ({{ $currency }})</th><th class="text-end selected-total">{{ number_format($group->sum(fn ($p) => \App\Services\HotelPayouts::payable($p)), 2) }}</th></tr>
                    </tfoot>
                </table>
            </div>
            <div class="row g-2 align-items-end mt-2">
                <div class="col-md-4">
                    <label>Transfer reference</label>
                    <input type="text" name="reference" class="form-control" maxlength="100">
                </div>
                <div class="col-md-5">
                    <label>Note</label>
                    <input type="text" name="notes" class="form-control" maxlength="500">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-success">Record {{ $currency }} payout</button>
                </div>
            </div>
        </form>
        @endforeach
    </div></div>
</div>

<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <h4 class="card-title">Payout history</h4>
        <table class="table table-sm">
            <thead><tr><th>Date</th><th class="text-end">Amount</th><th>Reference</th><th>Note</th><th>Recorded by</th></tr></thead>
            <tbody>
            @forelse($history as $payout)
                <tr>
                    <td>{{ $payout->paid_at->timezone(config('bedspace.timezone'))->format('Y-m-d') }}</td>
                    <td class="text-end">{{ $payout->money() }}</td>
                    <td>{{ $payout->reference }}</td>
                    <td>{{ $payout->notes }}</td>
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
@section('scripts')
@include('admin._selectedTotal')
@stop
