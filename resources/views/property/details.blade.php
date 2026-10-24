@extends('layout.app')
@section('content')
@php($fmt = fn ($v) => number_format($v, 2))
<div class="col-lg-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h3 class="mb-0">{{ $property->property_name }}
            @if($property->is_active)
                <span class="badge badge-success">Active</span>
            @else
                <span class="badge badge-secondary">Inactive</span>
            @endif
        </h3>
        <div class="d-flex gap-2">
            @if($canEdit)
                <a href="/view-property/{{ $property->id }}" class="btn btn-sm btn-primary">Edit property</a>
                <a href="/add-base-rate/{{ $property->id }}" class="btn btn-sm btn-outline-primary">Rates calendar</a>
                <a href="/inventory/{{ $property->id }}" class="btn btn-sm btn-outline-primary">Inventory</a>
                <a href="/rooms/{{ $property->id }}" class="btn btn-sm btn-outline-primary">Room numbers</a>
            @else
                <a href="/rates/{{ $property->id }}/view" class="btn btn-sm btn-outline-primary">View rates</a>
                <a href="/inventory/{{ $property->id }}/view" class="btn btn-sm btn-outline-primary">View inventory</a>
            @endif
        </div>
    </div>

    @if($photos->isNotEmpty())
    <div class="card grid-margin"><div class="card-body">
        <h4 class="card-title">Photos</h4>
        <div class="row">
            @foreach($photos as $photo)
                <div class="col-6 col-md-3 mb-3">
                    <a href="{{ $photo->url }}" target="_blank"><img src="{{ $photo->thumb_url }}" class="w-100 rounded" style="height:150px;object-fit:cover;" alt=""></a>
                </div>
            @endforeach
        </div>
    </div></div>
    @endif

    <div class="row">
        <div class="col-md-4 grid-margin stretch-card">
            <div class="card"><div class="card-body">
                <h4 class="card-title">Location</h4>
                <p class="mb-1">{{ $property->address }}</p>
                <p class="mb-1">{{ $property->city?->city_name }}, {{ $property->city?->district?->district_name }}</p>
                <p class="mb-1">{{ $property->city?->district?->province?->province_name }}</p>
                @if(preg_match('#^https?://#i', (string) $property->location))
                    <p class="mb-0"><a href="{{ $property->location }}" target="_blank" rel="noopener">Open in maps</a></p>
                @elseif($property->location)
                    <p class="mb-0 text-muted">{{ $property->location }}</p>
                @endif
            </div></div>
        </div>
        <div class="col-md-4 grid-margin stretch-card">
            <div class="card"><div class="card-body">
                <h4 class="card-title">Property owner</h4>
                <p class="mb-1"><strong>{{ $property->propertyOwner?->name }}</strong></p>
                <p class="mb-1">{{ $property->propertyOwner?->email }}</p>
                <p class="mb-0">{{ $property->propertyOwner?->phone }}</p>
            </div></div>
        </div>
        <div class="col-md-4 grid-margin stretch-card">
            <div class="card"><div class="card-body">
                <h4 class="card-title">Contact person</h4>
                @if($property->primaryContact)
                    <p class="mb-1"><strong>{{ $property->primaryContact->name }}</strong></p>
                    <p class="mb-1">{{ $property->primaryContact->mobile }}</p>
                    <p class="mb-1">{{ $property->primaryContact->email ?: 'No email' }}</p>
                    <p class="mb-0 text-muted">{{ $property->primaryContact->user ? 'Has a login account' : 'No login account' }}</p>
                @else
                    <p class="mb-0 text-muted">Not set</p>
                @endif
            </div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 grid-margin stretch-card">
            <div class="card"><div class="card-body">
                <h4 class="card-title">Cancellation policy</h4>
                <p class="mb-1"><strong>{{ $property->effectiveCancellationDays() }} day(s)</strong> before check-in</p>
                <p class="mb-0 text-muted">Cancellations within {{ $property->effectiveCancellationDays() }} day(s) of check-in, and no-shows, are not refunded.
                    @if($property->cancellation_days === null) (Platform default) @endif</p>
            </div></div>
        </div>
        <div class="col-md-8 grid-margin stretch-card">
            <div class="card"><div class="card-body">
                <h4 class="card-title">Property amenities</h4>
                <p class="mb-0">{{ $amenities->isNotEmpty() ? $amenities->join(' · ') : 'None added' }}</p>
            </div></div>
        </div>
    </div>

    <div class="card grid-margin"><div class="card-body">
        <h4 class="card-title">Room types today ({{ \Carbon\Carbon::parse($today)->format('D, d M Y') }})</h4>
        <p class="card-description">Available = total &minus; closed &minus; booked; a room type with no complete USD + LKR rates today is closed.</p>
        @forelse($roomTypes as $item)
            @php($roomType = $item['roomType'])
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <h5 class="mb-1">{{ $roomType->roomType }} <small class="text-muted">up to {{ $roomType->max_occupancy }} guests</small></h5>
                    <div>
                        @if($item['open'])
                            <span class="badge badge-success">Open today</span>
                        @else
                            <span class="badge badge-danger">Closed today (no complete rates)</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-3 my-2">
                    <div>Total <strong>{{ $item['total'] }}</strong></div>
                    <div>Closed <strong>{{ $item['closed'] }}</strong></div>
                    <div>Booked <strong>{{ $item['booked'] }}</strong></div>
                    <div>Available <strong>{{ $item['available'] }}</strong></div>
                    <div class="text-muted">{{ $item['roomNumbers'] }} room number(s)</div>
                </div>
                <p class="mb-2"><strong>Meal plans today:</strong> {{ $item['mealPlans']->isNotEmpty() ? $item['mealPlans']->pluck('mealType')->join(', ') : 'None' }}</p>
                @if($item['amenities']->isNotEmpty())
                    <p class="mb-2"><strong>Room amenities:</strong> {{ $item['amenities']->join(' · ') }}</p>
                @endif
                @if($item['rates'])
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Guests</th><th>Meal plan</th><th class="text-end">USD</th><th class="text-end">LKR</th></tr></thead>
                        <tbody>
                        @foreach($item['rates'] as $rate)
                            <tr>
                                <td>{{ $rate['occupancy']->occupancy_name }}</td>
                                <td>{{ $rate['mealType']->mealType }}</td>
                                <td class="text-end">{{ $fmt($rate['usd']) }}</td>
                                <td class="text-end">{{ $fmt($rate['lkr']) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @empty
            <p class="mb-0">No room types are set up at this property.</p>
        @endforelse
    </div></div>
</div>
@stop
