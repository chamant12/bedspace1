@extends('layout.app')
@section('content')
<div class="col-lg-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h3 class="mb-0">{{ $roomType ? $roomType->roomType.' amenities' : 'Property amenities' }}
            <small class="text-muted">{{ $property->property_name }}</small></h3>
        <a href="/amenities" class="btn btn-sm btn-outline-secondary">All properties</a>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="/amenities/property/{{ $property->id }}" class="btn btn-sm {{ $roomType ? 'btn-outline-primary' : 'btn-primary text-white' }}">Property</a>
        @foreach($roomTypes as $item)
            <a href="/amenities/property/{{ $property->id }}/room-type/{{ $item->id }}"
               class="btn btn-sm {{ $roomType && $roomType->id === $item->id ? 'btn-primary text-white' : 'btn-outline-primary' }}">{{ $item->roomType }}</a>
        @endforeach
    </div>

    <form method="POST" action="{{ $roomType ? '/amenities/property/'.$property->id.'/room-type/'.$roomType->id : '/amenities/property/'.$property->id }}">
        @csrf
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <p class="card-description mb-0">Tick everything {{ $roomType ? 'this room type has' : 'the property offers' }}, then save.
                    @if($roomType) These amenities apply to {{ $roomType->roomType }} at all your properties. @endif</p>
                <span class="small text-muted"><span id="ticked-count">{{ count($selected) }}</span> ticked</span>
            </div>

            @forelse($groups as $category => $amenities)
                <fieldset class="amenity-group">
                    <legend>{{ $category }}</legend>
                    <div class="row">
                        @foreach($amenities as $amenity)
                            <div class="col-sm-6 col-lg-4">
                                <label class="amenity-option">
                                    <input type="checkbox" name="amenity_ids[]" value="{{ $amenity->id }}" @checked(in_array($amenity->id, $selected))>
                                    {{ $amenity->amenity }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </fieldset>
            @empty
                <p class="mb-0">The administrator has not added any {{ $roomType ? 'room' : 'property' }} amenities yet.</p>
            @endforelse

            @if($groups->isNotEmpty())
                <div class="amenity-save">
                    <button type="submit" class="btn btn-primary text-white">Save amenities</button>
                </div>
            @endif
        </div></div>
    </form>
</div>
@stop

@section('styles')
<style>
.amenity-group { border-top: 1px solid #e9ecef; padding-top: 12px; margin-bottom: 14px; }
.amenity-group legend { font-size: 1rem; font-weight: 600; float: none; width: auto; margin-bottom: 6px; }
.amenity-option { display: flex; gap: 8px; align-items: flex-start; padding: 5px 0; cursor: pointer; font-weight: normal; }
.amenity-option input { margin-top: 3px; }
.amenity-save { position: sticky; bottom: 0; background: #fff; padding: 12px 0 4px; border-top: 1px solid #e9ecef; }
</style>
@stop

@section('scripts')
<script>
document.addEventListener('change', function (e) {
    if (e.target.name === 'amenity_ids[]') {
        document.getElementById('ticked-count').textContent = document.querySelectorAll('input[name="amenity_ids[]"]:checked').length;
    }
});
</script>
@stop
