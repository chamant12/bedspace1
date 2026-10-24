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
        @else
        <form method="POST" action="/admin/payouts/{{ $property->id }}/pay"
              onsubmit="return confirm('Record that the selected amounts have been paid to the hotel?');">
            @csrf
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all" checked></th>
                            <th>Paid by guest</th><th>Booking</th><th>Guest</th>
                            <th class="text-end">Payment</th><th class="text-end">Payment (LKR)</th><th class="text-end">Commission (LKR)</th><th class="text-end">To hotel (LKR)</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($payments as $payment)
                        @php($payable = \App\Services\HotelPayouts::payable($payment))
                        <tr>
                            <td><input type="checkbox" name="payment_ids[]" value="{{ $payment->id }}" class="pay-check" data-amount="{{ $payable }}" checked></td>
                            <td>{{ $payment->paid_at->timezone(config('bedspace.timezone'))->format('Y-m-d') }}</td>
                            <td><a href="/reservations/{{ $payment->reservation->reference }}">{{ $payment->reservation->reference }}</a>
                                <br/><small class="text-muted">{{ $payment->reservation->status->label() }}</small></td>
                            <td>{{ $payment->reservation->guest_name }}</td>
                            <td class="text-end">{{ $payment->currency->code }} {{ number_format($payment->amount, 2) }}</td>
                            <td class="text-end">{{ number_format($payment->amount_lkr, 2) }}</td>
                            <td class="text-end">{{ number_format($payment->commission_lkr, 2) }}</td>
                            <td class="text-end"><strong>{{ number_format($payable, 2) }}</strong></td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr><th colspan="7">Selected total</th><th class="text-end" id="selected-total">{{ number_format($payments->sum(fn ($p) => \App\Services\HotelPayouts::payable($p)), 2) }}</th></tr>
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
                    <button type="submit" class="btn btn-success">Record payout</button>
                </div>
            </div>
        </form>
        @endif
    </div></div>
</div>

<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <h4 class="card-title">Payout history</h4>
        <table class="table table-sm">
            <thead><tr><th>Date</th><th class="text-end">Amount (LKR)</th><th>Reference</th><th>Note</th><th>Recorded by</th></tr></thead>
            <tbody>
            @forelse($history as $payout)
                <tr>
                    <td>{{ $payout->paid_at->timezone(config('bedspace.timezone'))->format('Y-m-d') }}</td>
                    <td class="text-end">{{ number_format($payout->amount_lkr, 2) }}</td>
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
<script>
$(function () {
    function updateTotal() {
        var total = 0;
        $('.pay-check:checked').each(function () { total += parseFloat($(this).data('amount')); });
        $('#selected-total').text(total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    }
    $('#select-all').on('change', function () { $('.pay-check').prop('checked', this.checked); updateTotal(); });
    $(document).on('change', '.pay-check', updateTotal);
});
</script>
@stop
