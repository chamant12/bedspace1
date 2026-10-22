@extends('layout.ext-app')
@section('content')
<div class="container bs-page">
    <h2 class="mb-1">{{ $property->property_name }}</h2>
    <p class="bs-muted">{{ $property->address }} &middot; {{ $property->city?->city_name }}, {{ $property->city?->district?->district_name }}
        @if(preg_match('#^https?://#i', (string) $property->location)) &middot; <a href="{{ $property->location }}" target="_blank" rel="noopener">Map</a> @endif
    </p>

    @if($photos->isNotEmpty())
        <div class="row g-2 mb-3 bs-gallery">
            @foreach($photos->take(6) as $photo)
                <div class="col-6 col-md-4"><a href="{{ $photo->url }}" target="_blank"><img src="{{ $photo->thumb_url }}" alt=""></a></div>
            @endforeach
        </div>
    @endif

    @if($amenities->isNotEmpty())
        <div class="bs-card">
            <h5>Amenities</h5>
            <div>{{ $amenities->pluck('amenity')->join(' · ') }}</div>
        </div>
    @endif

    <div class="bs-card">
        @include('public._stayForm', ['action' => '/stay/'.$property->id])
    </div>

    @if($error)
        <div class="alert alert-danger">{{ $error }}</div>
    @elseif(empty($offers))
        <div class="alert alert-info">No rooms are available for these dates. Please try other dates.</div>
    @else
        <p class="bs-muted">Prices for {{ $stay['rooms'] }} room(s), {{ $stay['check_in'] }} to {{ $stay['check_out'] }}, in {{ $visitorCurrency->code }}</p>
        @foreach($offers as $offer)
            <div class="bs-card">
                <h4 class="mb-0">{{ $offer['roomType']->roomType }}</h4>
                <p class="bs-muted">Up to {{ $offer['roomType']->max_occupancy }} guests &middot; {{ $offer['available'] }} room(s) left</p>
                <table class="table mb-0">
                    <thead><tr><th>Guests</th><th>Meals</th><th class="text-end">Price</th><th></th></tr></thead>
                    <tbody>
                    @foreach($offer['options'] as $option)
                        @php($quote = $option['quote'])
                        <tr>
                            <td>{{ $option['occupancy']->occupancy_name }} ({{ $option['occupancy']->occupancy }})</td>
                            <td>{{ $option['mealType']->mealType }}</td>
                            <td class="text-end">
                                @if($quote['discount_total'] > 0)
                                    <s class="bs-muted">{{ number_format($quote['subtotal'], 2) }}</s><br/>
                                    <small class="text-success">{{ collect($quote['discounts'])->pluck('description')->join(', ') }}</small><br/>
                                @endif
                                <span class="bs-price">{{ $visitorCurrency->code }} {{ number_format($quote['total'], 2) }}</span>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-primary" href="/book?{{ http_build_query([
                                    'property_id' => $property->id,
                                    'roomType_id' => $offer['roomType']->id,
                                    'occupancy_id' => $option['occupancy']->id,
                                    'mealType_id' => $option['mealType']->id,
                                    'check_in' => $stay['check_in'],
                                    'check_out' => $stay['check_out'],
                                    'rooms' => $stay['rooms'],
                                ]) }}">Book</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif
</div>
@stop
@section('styles')
@include('public._styles')
@stop
@section('scripts')
@include('public._scripts')
@stop
