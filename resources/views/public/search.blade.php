@extends('layout.ext-app')
@section('title', 'Find a stay')
@section('content')
<div class="container bs-page">
    <h1 class="site-heading">Find a place to stay</h1>
    <div class="bs-card">
        @include('public._stayForm', ['action' => '/search'])
    </div>

    @if($error)
        <div class="alert alert-danger">{{ $error }}</div>
    @elseif(request()->filled('check_in') || request()->filled('district_id') || request()->filled('area'))
        <p class="bs-muted">{{ count($results) }} {{ count($results) === 1 ? 'property' : 'properties' }} available
            &middot; prices for {{ $stay['rooms'] }} room(s), whole stay, in {{ $visitorCurrency->code }}</p>

        @forelse($results as $result)
            @php($property = $result['property'])
            <div class="bs-card d-flex gap-3 flex-wrap">
                @if($result['photo'])
                    <img src="{{ $result['photo']->thumb_url }}" class="bs-thumb" alt="{{ $property->property_name }}">
                @else
                    <div class="bs-thumb"></div>
                @endif
                <div class="flex-grow-1">
                    <h4 class="mb-1">{{ $property->property_name }}</h4>
                    <div class="bs-muted">{{ $property->city?->city_name }}, {{ $property->city?->district?->district_name }}</div>
                </div>
                <div class="text-end">
                    <div class="bs-muted">from</div>
                    <div class="bs-price">{{ $visitorCurrency->code }} {{ number_format($result['price'], 2) }}</div>
                    <a class="btn btn-primary mt-2"
                       href="/stay/{{ $property->id }}?{{ http_build_query(array_filter(['check_in' => $stay['check_in'], 'check_out' => $stay['check_out'], 'guests' => $stay['guests'], 'rooms' => $stay['rooms']])) }}">
                        See rooms
                    </a>
                </div>
            </div>
        @empty
            <div class="alert alert-info">No properties are available for these dates. Try other dates or another area.</div>
        @endforelse
    @endif
</div>
@stop
@section('styles')
@include('public._styles')
@stop
@section('scripts')
@include('public._scripts')
@stop
