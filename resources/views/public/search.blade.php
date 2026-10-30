@extends('layout.ext-app')
@section('title', 'Find a stay')
@section('content')
<div class="container bs-page">
    <h1 class="site-heading">Find a place to stay</h1>
    <div class="bs-card">
        @include('public._stayForm', ['action' => '/search'])
    </div>

    @php($stayQuery = http_build_query(array_filter(['check_in' => $stay['check_in'], 'check_out' => $stay['check_out'], 'guests' => $stay['guests'], 'rooms' => $stay['rooms']])))

    @if(!$error && $location && $featured->isNotEmpty())
        <h2 class="site-heading mt-4 mb-3">Featured stays in {{ $location }}</h2>
        <div class="row">
            @foreach($featured as $item)
                @php($property = $item['property'])
                <div class="col-md-4 mb-4">
                    <a href="/stay/{{ $property->id }}?{{ $stayQuery }}" class="bs-featured">
                        <div class="bs-featured-photos bs-featured-photos-{{ $item['samples']->count() }}">
                            @foreach($item['samples'] as $photo)
                                <img src="{{ $photo->thumb_url }}" alt="{{ $property->property_name }}" loading="lazy">
                            @endforeach
                        </div>
                        <div class="bs-featured-body">
                            <h4 class="mb-1">{{ $property->property_name }}</h4>
                            <div class="bs-muted">{{ $property->city?->city_name }}</div>
                            <div class="mt-1">from <span class="bs-price">{{ $visitorCurrency->code }} {{ number_format($item['price'], 2) }}</span></div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

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
                       href="/stay/{{ $property->id }}?{{ $stayQuery }}">
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
