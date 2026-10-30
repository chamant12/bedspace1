@extends('layout.app')
@section('content')
@if($missingRates->isNotEmpty())
<div class="alert alert-warning">
    You have not updated today's {{ $missingRates->pluck('code')->join(', ') }} exchange rate.
    <a href="/exchange-rates">Update now</a>
</div>
@endif
<div class="row">
    @foreach([
        'Properties' => $stats['properties'],
        'Active Properties' => $stats['activeProperties'],
        'Room Types' => $stats['roomTypes'],
        'Rooms' => $stats['rooms'],
    ] as $label => $value)
    <div class="col-sm-6 col-lg-3 grid-margin stretch-card">
        <div class="card card-rounded">
            <div class="card-body">
                <p class="statistics-title">{{ $label }}</p>
                <h3 class="rate-percentage">{{ $value }}</h3>
            </div>
        </div>
    </div>
    @endforeach
</div>

@if($refundsDue > 0)
<div class="alert alert-warning">
    {{ $refundsDue }} cancelled booking(s) have a refund due. <a href="/reservations?refund=due">Review</a>
</div>
@endif
@if($payoutDue > 0)
<div class="alert alert-success">
    EpickTours owes you <strong>LKR {{ number_format($payoutDue, 2) }}</strong> for online payments (after commission), to be paid out.
</div>
@endif
@if($commissionDue > 0)
<div class="alert alert-info">
    Commission due to EpickTours on payments collected at your properties: <strong>LKR {{ number_format($commissionDue, 2) }}</strong>.
</div>
@endif

<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card card-rounded">
            <div class="card-body">
                <h4 class="card-title card-title-dash">Pay-at-hotel bookings to settle</h4>
                <p class="card-description">Mark each booking as paid when the guest pays at the property, or as cancelled / no-show.
                    Commission on paid bookings is added to your dues to EpickTours.</p>
                @if($offlineToSettle->isEmpty())
                    <p class="mb-0">Nothing to settle.</p>
                @else
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr><th>Booking</th><th>Guest</th><th>Property</th><th>Check-in</th><th>Status</th><th class="text-end">Outstanding</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                        @foreach($offlineToSettle as $r)
                            <tr class="{{ $r->check_in->toDateString() <= $today ? '' : 'text-muted' }}">
                                <td><a href="/reservations/{{ $r->reference }}">{{ $r->reference }}</a></td>
                                <td>{{ $r->guest_name }}<br/><small>{{ $r->guest_phone }}</small></td>
                                <td>{{ $r->property->property_name }}</td>
                                <td>{{ $r->check_in->format('Y-m-d') }}</td>
                                <td><span class="badge {{ $r->status->badgeClass() }}">{{ $r->status->label() }}</span></td>
                                <td class="text-end">{{ $r->money($r->outstanding()) }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <form method="POST" action="/reservations/{{ $r->reference }}/payments" class="d-flex gap-1"
                                              onsubmit="return confirm('Mark {{ $r->reference }} as paid ({{ $r->money($r->outstanding()) }})?');">
                                            @csrf
                                            <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                                            <input type="hidden" name="amount" value="{{ $r->outstanding() }}">
                                            <select name="method" class="form-control form-control-sm" style="width:auto;">
                                                @foreach($offlineChannels as $channel)
                                                    <option value="{{ $channel->value }}">{{ $channel->label() }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-success">Paid</button>
                                        </form>
                                        @if($r->status === \App\Enums\ReservationStatus::Confirmed)
                                            <form method="POST" action="/reservations/{{ $r->reference }}/status"
                                                  onsubmit="var reason = prompt('Reason for cancelling {{ $r->reference }}?'); if (!reason) return false; this.note.value = reason; return true;">
                                                @csrf
                                                <input type="hidden" name="status" value="cancelled">
                                                <input type="hidden" name="note" value="">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                                            </form>
                                            @if($r->check_in->toDateString() <= $today)
                                                <form method="POST" action="/reservations/{{ $r->reference }}/status"
                                                      onsubmit="return confirm('Mark {{ $r->reference }} as no-show?');">
                                                    @csrf
                                                    <input type="hidden" name="status" value="no_show">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">No-show</button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card card-rounded">
            <div class="card-body">
                <div class="d-sm-flex justify-content-between align-items-start">
                    <h4 class="card-title card-title-dash">My Properties</h4>
                    @if(auth()->user()->isOwner())<a href="/add-property" class="btn btn-primary btn-sm text-white">Add Property</a>@endif
                </div>
                @if($properties->isEmpty())
                    <p class="mt-3">You have not added any properties yet.</p>
                @else
                <div class="table-responsive mt-3">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>City</th>
                                <th>Rooms</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($properties as $property)
                            <tr>
                                <td>{{ $property->property_name }}</td>
                                <td>{{ $property->city?->city_name }}</td>
                                <td>{{ (int) ($roomCounts[$property->id] ?? 0) }}</td>
                                <td>
                                    @if($property->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="/properties/{{ $property->id }}/details" class="btn btn-primary btn-sm" title="Property details"><i class="fa fa-eye"></i></a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@stop
