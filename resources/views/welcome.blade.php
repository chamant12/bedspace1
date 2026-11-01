@extends('layout.ext-app')
@php($s = $page?->sections ?? collect())
@section('title', $page?->title)
@section('meta_description', $page?->meta_description)
@section('content')

{{-- Banner --}}
@if($hero = $s->get('hero'))
<section class="site-hero" style="background-image: url('{{ $hero->imageUrl() }}');">
    <div class="container">
        <h1>{{ $hero->heading }}</h1>
        @if($hero->subheading)<p>{{ $hero->subheading }}</p>@endif
    </div>
</section>
@endif

{{-- Search (booking engine) --}}
<section class="site-search" id="search">
    <div class="container">
        <form id="hotels-tab" class="reservation-hotel" method="GET" action="/search">
            <div class="row">
                <div class="col-lg-3 col-md-6 form-group">
                    <label for="search-area">Where</label>
                    @include('public._whereInput', ['id' => 'search-area', 'value' => ''])
                </div>
                <div class="col-lg-2 col-md-3 col-6 form-group">
                    <label for="bs-check-in">Check-in</label>
                    <input type="text" id="bs-check-in" name="check_in" class="form-control" value="{{ $stay['check_in'] }}" autocomplete="off" readonly required>
                </div>
                <div class="col-lg-2 col-md-3 col-6 form-group">
                    <label for="bs-check-out">Check-out</label>
                    <input type="text" id="bs-check-out" name="check_out" class="form-control" value="{{ $stay['check_out'] }}" autocomplete="off" readonly required>
                </div>
                <div class="col-lg-2 col-md-4 col-6 form-group">
                    <label for="search-guests">Guests per room</label>
                    <select name="guests" id="search-guests" class="form-control">
                        <option value="">Any</option>
                        @foreach($occupancies as $occupancy)
                            <option value="{{ $occupancy->occupancy }}">{{ $occupancy->occupancy }} ({{ $occupancy->occupancy_name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-1 col-md-4 col-6 form-group">
                    <label for="search-rooms">Rooms</label>
                    <select name="rooms" id="search-rooms" class="form-control">
                        @for($i = 1; $i <= \App\Services\BookingQuotes::MAX_ROOMS; $i++)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 form-group d-flex align-items-end">
                    <button type="submit" class="site-btn accent w-100">Search stays</button>
                </div>
            </div>
        </form>
    </div>
</section>

{{-- Introduction --}}
@if($about = $s->get('about'))
<section class="site-section" id="about">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                @if($about->imageUrl())<img src="{{ $about->imageUrl() }}" class="site-split-img" alt="{{ $about->heading }}">@endif
            </div>
            <div class="col-lg-6">
                @if($about->subheading)<div class="site-eyebrow">{{ $about->subheading }}</div>@endif
                <h2 class="site-heading">{{ $about->heading }}</h2>
                <div class="site-prose">{{ \App\Cms\Cms::markdown($about->body) }}</div>
                @if($about->button_text)<a href="{{ $about->button_url }}" class="site-btn outline">{{ $about->button_text }}</a>@endif
            </div>
        </div>
    </div>
</section>
@endif

{{-- Destinations --}}
@if($destinations = $s->get('destinations'))
<section class="site-section alt" id="destinations">
    <div class="container">
        <div class="site-section-head">
            @if($destinations->subheading)<div class="site-eyebrow">{{ $destinations->subheading }}</div>@endif
            <h2>{{ $destinations->heading }}</h2>
            @if($destinations->body)<div class="site-prose">{{ \App\Cms\Cms::markdown($destinations->body) }}</div>@endif
        </div>
        <div class="site-grid">
            @foreach($destinations->activeItems() as $item)
                <a href="{{ $item['url'] ?? '/search' }}" class="site-destination"
                   style="background-image: url('{{ \App\Cms\Cms::imageUrl($item['image_path'] ?? null, $item['image_disk'] ?? null) }}');">
                    <div>
                        <h3>{{ $item['title'] }}</h3>
                        @if(!empty($item['text']))<p>{{ $item['text'] }}</p>@endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Featured stays (live from the booking engine) --}}
@if(($featured = $s->get('featured')) && $featuredStays->isNotEmpty())
<section class="site-section" id="stays">
    <div class="container">
        <div class="site-section-head">
            @if($featured->subheading)<div class="site-eyebrow">{{ $featured->subheading }}</div>@endif
            <h2>{{ $featured->heading }}</h2>
            @if($featured->body)<p>{{ $featured->body }}</p>@endif
        </div>
        <div class="site-grid">
            @foreach($featuredStays as $stayItem)
                <div class="site-card">
                    <a href="{{ $stayItem['url'] }}">
                        <div class="site-card-img" style="background-image: url('{{ $stayItem['photo'] }}');"></div>
                        <div class="site-card-body">
                            <h3>{{ $stayItem['property']->property_name }}</h3>
                            <p>{{ $stayItem['property']->city?->city_name }}, {{ $stayItem['property']->city?->district?->district_name }}</p>
                            <div class="site-card-meta">
                                @if($stayItem['price'] !== null)
                                    from <span class="site-price">{{ $visitorCurrency->code ?? 'LKR' }} {{ number_format($stayItem['price'], 0) }}</span> / night
                                @else
                                    See rooms and prices
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
        @if($featured->button_text)
            <div class="text-center mt-4"><a href="{{ $featured->button_url }}" class="site-btn">{{ $featured->button_text }}</a></div>
        @endif
    </div>
</section>
@endif

{{-- Why book with us --}}
@if($why = $s->get('why'))
<section class="site-section alt" id="why">
    <div class="container">
        <div class="site-section-head">
            @if($why->subheading)<div class="site-eyebrow">{{ $why->subheading }}</div>@endif
            <h2>{{ $why->heading }}</h2>
        </div>
        <div class="row">
            @foreach($why->activeItems() as $index => $item)
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="site-feature">
                        <div class="site-feature-icon"><i class="fas {{ ['fa-home', 'fa-tags', 'fa-calendar-check', 'fa-credit-card'][$index % 4] }}"></i></div>
                        <h3>{{ $item['title'] }}</h3>
                        <p>{{ $item['text'] ?? '' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Travel journal --}}
@if(($blog = $s->get('blog')) && $posts->isNotEmpty())
<section class="site-section" id="journal">
    <div class="container">
        <div class="site-section-head">
            @if($blog->subheading)<div class="site-eyebrow">{{ $blog->subheading }}</div>@endif
            <h2>{{ $blog->heading }}</h2>
        </div>
        <div class="site-grid">
            @foreach($posts as $post)
                @include('blog._card', ['post' => $post])
            @endforeach
        </div>
        @if($blog->button_text)
            <div class="text-center mt-4"><a href="{{ $blog->button_url }}" class="site-btn outline">{{ $blog->button_text }}</a></div>
        @endif
    </div>
</section>
@endif

{{-- Property owners --}}
@if($owners = $s->get('owners'))
<section class="site-section pt-0">
    <div class="container">
        <div class="site-cta d-md-flex align-items-center justify-content-between">
            <div class="mr-md-4 mb-3 mb-md-0">
                @if($owners->subheading)<div class="site-eyebrow">{{ $owners->subheading }}</div>@endif
                <h2>{{ $owners->heading }}</h2>
                <p class="mb-0">{{ $owners->body }}</p>
            </div>
            @if($owners->button_text)<a href="{{ $owners->button_url }}" class="site-btn accent">{{ $owners->button_text }}</a>@endif
        </div>
    </div>
</section>
@endif
@stop

@section('scripts')
<script>
// Search dates: no past dates, check-out always after check-in
$(function () {
    var checkIn = $('#bs-check-in'), checkOut = $('#bs-check-out');
    var nextDay = function (date) { var d = new Date(date.getTime()); d.setDate(d.getDate() + 1); return d; };

    checkIn.datepicker({ dateFormat: 'yy-mm-dd', minDate: 0, onSelect: function () {
        var inDate = checkIn.datepicker('getDate');
        checkOut.datepicker('option', 'minDate', nextDay(inDate));
        var outDate = checkOut.datepicker('getDate');
        if (!outDate || outDate <= inDate) {
            checkOut.datepicker('setDate', nextDay(inDate));
        }
    }});
    checkOut.datepicker({ dateFormat: 'yy-mm-dd', minDate: 1 });
});
</script>
@stop
