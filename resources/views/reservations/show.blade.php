@extends('layout.app')
@section('content')
@php($r = $reservation)
<div class="col-lg-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Booking {{ $r->reference }} <span class="badge {{ $r->status->badgeClass() }}">{{ $r->status->label() }}</span></h3>
        <a href="/reservations" class="btn btn-light">Back to reservations</a>
    </div>

    <div class="row">
        <div class="col-md-6 grid-margin stretch-card">
            <div class="card"><div class="card-body">
                <h4 class="card-title">Stay</h4>
                <p class="mb-1"><strong>{{ $r->property->property_name }}</strong></p>
                <p class="mb-1">{{ $r->check_in->format('D, d M Y') }} &rarr; {{ $r->check_out->format('D, d M Y') }} ({{ $r->nights }} night{{ $r->nights > 1 ? 's' : '' }})</p>
                <p class="mb-1">Payment: {{ $r->payment_method->label() }} &middot; {{ ucfirst(str_replace('_', ' ', $r->payment_status)) }}</p>
                <p class="mb-1">Source: {{ $r->source === 'owner' ? 'Entered by property' : 'Website' }} &middot; Booked {{ $r->created_at->timezone(config('bedspace.timezone'))->format('Y-m-d H:i') }}</p>
                @if($r->cancelled_at)
                    <p class="mb-1 text-danger">Cancelled {{ $r->cancelled_at->timezone(config('bedspace.timezone'))->format('Y-m-d H:i') }}: {{ $r->cancellation_reason }}</p>
                @endif
            </div></div>
        </div>
        <div class="col-md-6 grid-margin stretch-card">
            <div class="card"><div class="card-body">
                <h4 class="card-title">Guest</h4>
                <p class="mb-1"><strong>{{ $r->guest_name }}</strong>
                    @if($previousStays > 0)<span class="badge badge-info">Returning guest &middot; {{ $previousStays }} other booking(s)</span>@endif
                </p>
                <p class="mb-1">{{ $r->guest_email }} &middot; {{ $r->guest_phone }}</p>
                @if($r->guest_address)<p class="mb-1">{{ $r->guest_address }}@if($r->guest_country), {{ $r->guest_country }}@endif</p>@endif
                @if($r->special_requests)<p class="mb-1"><em>Requests: {{ $r->special_requests }}</em></p>@endif
            </div></div>
        </div>
    </div>

    <div class="card grid-margin"><div class="card-body">
        <h4 class="card-title">Rooms &amp; price</h4>
        <table class="table">
            <thead><tr><th>Room type</th><th>Guests / meals</th><th>Rooms</th><th>Room numbers</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
            @foreach($r->rooms as $line)
                <tr>
                    <td>{{ $line->roomType->roomType }}</td>
                    <td>{{ $line->occupancy->occupancy_name }}, {{ $line->mealType->mealType }}</td>
                    <td>{{ $line->rooms }}</td>
                    <td>{{ $line->activeAssignments->map(fn ($a) => $a->room->room_number)->join(', ') ?: '–' }}</td>
                    <td class="text-end">{{ $r->money($line->subtotal) }}</td>
                </tr>
                @foreach($line->discounts as $discount)
                    <tr class="text-success"><td colspan="4">Discount: {{ $discount->description }}</td><td class="text-end">&minus; {{ $r->money($discount->amount) }}</td></tr>
                @endforeach
            @endforeach
            <tr><th colspan="4">Total</th><th class="text-end">{{ $r->money($r->total) }}</th></tr>
            @if($r->currency->code !== 'LKR')
                <tr><td colspan="4" class="text-muted">In LKR at {{ number_format($r->exchange_rate, 2) }} per {{ $r->currency->code }} (rate when booked)</td><td class="text-end text-muted">LKR {{ number_format($r->total_lkr, 2) }}</td></tr>
            @endif
            </tbody>
        </table>
    </div></div>

    <div class="card grid-margin"><div class="card-body">
        <h4 class="card-title">Payments
            <span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $r->payment_status)) }}</span>
        </h4>
        <p class="mb-2">Paid {{ $r->money($r->amount_paid) }} of {{ $r->money($r->total) }} &middot;
            <strong>Outstanding {{ $r->money($r->outstanding()) }}</strong> &middot;
            Platform commission {{ number_format($r->commission_rate, 2) }}%</p>

        @if($r->payments->isNotEmpty())
        <table class="table table-sm">
            <thead><tr><th>Date</th><th>Method</th><th>Status</th><th class="text-end">Amount</th><th class="text-end">LKR</th><th class="text-end">Commission (LKR)</th><th>Reference</th><th>By</th></tr></thead>
            <tbody>
            @foreach($r->payments as $payment)
                <tr>
                    <td>{{ ($payment->paid_at ?? $payment->created_at)->timezone(config('bedspace.timezone'))->format('Y-m-d H:i') }}</td>
                    <td>{{ $payment->method->label() }}</td>
                    <td>{{ ucfirst($payment->status) }}</td>
                    <td class="text-end">{{ $r->money($payment->amount) }}</td>
                    <td class="text-end">{{ $payment->amount_lkr !== null ? number_format($payment->amount_lkr, 2) : '–' }}</td>
                    <td class="text-end">{{ $payment->commission_lkr !== null ? number_format($payment->commission_lkr, 2) : '–' }}</td>
                    <td>{{ $payment->gateway_reference ?? $payment->reference }}@if($payment->notes)<br/><small class="text-muted">{{ $payment->notes }}</small>@endif</td>
                    <td>{{ $payment->recordedBy?->name ?? ($payment->gateway ? 'Gateway' : '') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif

        @if($canManage && $r->outstanding() > 0 && !in_array($r->status, [\App\Enums\ReservationStatus::Cancelled, \App\Enums\ReservationStatus::Expired, \App\Enums\ReservationStatus::Pending]))
            <form method="POST" action="/reservations/{{ $r->reference }}/payments" class="row g-2 align-items-end mt-2"
                  onsubmit="return confirm('Record this payment received at the property?');">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
                <div class="col-md-2">
                    <label>Amount ({{ $r->currency->code }})</label>
                    <input type="number" name="amount" step="0.01" min="0.01" max="{{ $r->outstanding() }}" class="form-control" value="{{ $r->outstanding() }}" required>
                </div>
                <div class="col-md-2">
                    <label>Method</label>
                    <select name="method" class="form-control" required>
                        @foreach($offlineChannels as $channel)
                            <option value="{{ $channel->value }}">{{ $channel->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Receipt / reference</label>
                    <input type="text" name="reference" class="form-control" maxlength="100">
                </div>
                <div class="col-md-3">
                    <label>Note</label>
                    <input type="text" name="notes" class="form-control" maxlength="500">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success">Record payment</button>
                </div>
            </form>
            <small class="text-muted">The full outstanding amount marks the booking as paid.</small>
        @endif
    </div></div>

    @if($canManage)
    <div class="card grid-margin"><div class="card-body">
        <h4 class="card-title">Actions</h4>

        @if($r->status === \App\Enums\ReservationStatus::Confirmed)
            <form method="POST" action="/reservations/{{ $r->reference }}/check-in" class="mb-4">
                @csrf
                <h5>Check in</h5>
                @if($r->check_in->toDateString() > $today)
                    <p class="text-muted">Check-in opens on {{ $r->check_in->format('Y-m-d') }}.</p>
                @else
                    @foreach($r->rooms as $line)
                        <div class="form-group">
                            <label>{{ $line->roomType->roomType }}: choose {{ $line->rooms }} room number(s)</label>
                            <select name="rooms[{{ $line->id }}][]" class="form-control" multiple size="{{ min(6, max(2, $freeRooms[$line->id]->count())) }}" required>
                                @foreach($freeRooms[$line->id] as $room)
                                    <option value="{{ $room->id }}">{{ $room->room_number }}</option>
                                @endforeach
                            </select>
                            @if($freeRooms[$line->id]->isEmpty())
                                <small class="text-danger">No free room numbers for this room type. Add them on the <a href="/rooms/{{ $r->property_id }}">Room numbers</a> page.</small>
                            @else
                                <small class="text-muted">Hold Ctrl (Cmd on Mac) to select several.</small>
                            @endif
                        </div>
                    @endforeach
                    <button type="submit" class="btn btn-primary">Check in</button>
                @endif
            </form>
        @endif

        <div class="d-flex flex-wrap gap-2">
            @foreach($r->status->allowedTransitions() as $to)
                @continue($to === \App\Enums\ReservationStatus::CheckedIn || $to === \App\Enums\ReservationStatus::Expired)
                <form method="POST" action="/reservations/{{ $r->reference }}/status" class="d-flex gap-2 align-items-start"
                      onsubmit="return confirm('Change this booking to {{ $to->label() }}?');">
                    @csrf
                    <input type="hidden" name="status" value="{{ $to->value }}">
                    @if($to === \App\Enums\ReservationStatus::Cancelled)
                        <input type="text" name="note" class="form-control form-control-sm" placeholder="Reason for cancellation" required maxlength="500" style="width:240px;">
                    @endif
                    <button type="submit" class="btn btn-sm {{ in_array($to, [\App\Enums\ReservationStatus::Cancelled, \App\Enums\ReservationStatus::NoShow]) ? 'btn-danger' : 'btn-success' }}">
                        {{ $to === \App\Enums\ReservationStatus::CheckedOut ? 'Check out' : 'Mark as '.$to->label() }}
                    </button>
                </form>
            @endforeach
        </div>
    </div></div>
    @endif

    <div class="card grid-margin"><div class="card-body">
        <h4 class="card-title">History</h4>
        <table class="table table-sm">
            <thead><tr><th>When</th><th>Change</th><th>By</th><th>Note</th></tr></thead>
            <tbody>
            @foreach($r->statusHistory as $entry)
                <tr>
                    <td>{{ $entry->created_at?->timezone(config('bedspace.timezone'))->format('Y-m-d H:i') }}</td>
                    <td>{{ $entry->from_status ? $entry->from_status->label().' → ' : '' }}{{ $entry->to_status->label() }}</td>
                    <td>{{ $entry->user?->name ?? ($entry->user_id ? '' : 'Guest / system') }}</td>
                    <td>{{ $entry->note }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div></div>
</div>
@stop
