@extends('layout.app')
@section('content')
<div class="col-lg-12 grid-margin">
    <div class="card"><div class="card-body">
        <h4 class="card-title">Amenities</h4>
        <p class="card-description">Choose a property or a room type and tick its amenities. Room amenities are grouped the way Expedia shows them
            (bedroom, bathroom, entertainment, food and drink...). Room type amenities are shared by every property of yours that offers that room type.</p>

        @forelse($properties as $property)
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <div>
                        <h5 class="mb-0">{{ $property->property_name }}
                            @unless($property->is_active)<span class="badge badge-secondary">Inactive</span>@endunless
                        </h5>
                        <small class="text-muted">{{ $property->city?->city_name }}{{ $property->city?->district ? ', '.$property->city->district->district_name : '' }}</small>
                    </div>
                    <a href="/amenities/property/{{ $property->id }}" class="btn btn-sm btn-primary text-white">
                        Property amenities <span class="badge bg-light text-dark ms-1">{{ $counts['property'][$property->id] ?? 0 }}</span>
                    </a>
                </div>
                @php($rows = $roomTypesByProperty->get($property->id, collect()))
                @if($rows->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($rows as $row)
                            <a href="/amenities/property/{{ $property->id }}/room-type/{{ $row->roomType_id }}" class="btn btn-sm btn-outline-primary">
                                {{ $row->roomType->roomType }} <span class="badge bg-light text-dark ms-1">{{ $counts['roomType'][$row->roomType_id] ?? 0 }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="mb-0 text-muted small">No room types are set up at this property yet.</p>
                @endif
            </div>
        @empty
            <p class="mb-0">You have no properties yet.</p>
        @endforelse
    </div></div>
</div>
@stop
