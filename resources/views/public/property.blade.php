@extends('layout.ext-app')
@section('content')
<div class="container bs-page">
    <h2 class="mb-1">{{ $property->property_name }}</h2>
    <p class="bs-muted">{{ $property->address }} &middot; {{ $property->city?->city_name }}, {{ $property->city?->district?->district_name }}
        @if(preg_match('#^https?://#i', (string) $property->location)) &middot; <a href="{{ $property->location }}" target="_blank" rel="noopener">Map</a> @endif
    </p>

    @if($photos->isNotEmpty())
        {{-- Photo categories (entrance, lobby, pool...): each opens the viewer with only those photos --}}
        @php($photoTags = collect(\App\Models\Photo::tagsFor('property'))->filter(fn ($label, $key) => $photos->contains('tag', $key)))
        @if($photoTags->count() > 1)
            <div class="d-flex flex-wrap gap-2 mb-2">
                @foreach($photoTags as $key => $label)
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-open-gallery="property" data-tag="{{ $key }}">{{ $label }} ({{ $photos->where('tag', $key)->count() }})</button>
                @endforeach
            </div>
        @endif
        <div class="row g-2 mb-3 bs-gallery">
            @foreach($photos as $photo)
                <div class="col-6 col-md-4" @if($loop->index >= 6) hidden @endif>
                    <a href="{{ $photo->url }}" data-gallery="property" data-tag="{{ $photo->tag }}" data-caption="{{ $photo->tag_label ?? $property->property_name }}"><img src="{{ $photo->thumb_url }}" alt="{{ $property->property_name }}{{ $photo->tag_label ? ' – '.$photo->tag_label : ' photo '.$loop->iteration }}" loading="lazy"></a>
                </div>
            @endforeach
            @if($photos->count() > 6)
                <div class="col-12"><a href="{{ $photos[6]->url }}" class="bs-show-all" data-open-gallery="property" data-start="6">View all {{ $photos->count() }} photos</a></div>
            @endif
        </div>
    @endif

    <div class="bs-card">
        <h5>Cancellation policy</h5>
        <div>{{ $cancellationSummary }}</div>
    </div>

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
            @php($gallery = $roomPhotos->get($offer['roomType']->id, collect()))
            <div class="bs-card">
                <div class="row">
                    @if($gallery->isNotEmpty())
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="bs-room-photos">
                                @foreach($gallery as $photo)
                                    <a href="{{ $photo->url }}" data-gallery="room-{{ $offer['roomType']->id }}" data-tag="{{ $photo->tag }}" data-caption="{{ $offer['roomType']->roomType }}{{ $photo->tag_label ? ' · '.$photo->tag_label : '' }}" @unless($loop->first) hidden @endunless>
                                        <img src="{{ $photo->thumb_url }}" class="bs-room-cover" alt="{{ $offer['roomType']->roomType }}" loading="lazy">
                                    </a>
                                @endforeach
                                @if($gallery->count() > 1)
                                    <span class="bs-more">{{ $gallery->count() }} photos</span>
                                    <div class="bs-room-thumbs">
                                        @foreach($gallery->slice(1, 4) as $photo)
                                            <img src="{{ $photo->thumb_url }}" alt="" loading="lazy" role="button" tabindex="0"
                                                 data-open-gallery="room-{{ $offer['roomType']->id }}" data-start="{{ $loop->iteration }}">
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                    <div class="{{ $gallery->isNotEmpty() ? 'col-md-8' : 'col-12' }}">
                        <h4 class="mb-0">{{ $offer['roomType']->roomType }}</h4>
                        <p class="bs-muted">Up to {{ $offer['roomType']->max_occupancy }} guests &middot; {{ $offer['available'] }} room(s) left</p>
                        <p class="mb-0">Choose the number of guests and a meal plan below.</p>
                    </div>
                </div>
                <table class="table mb-0 mt-3">
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

<div class="bs-lightbox" id="bs-lightbox" hidden role="dialog" aria-modal="true" aria-label="Photo viewer">
    <button type="button" class="bs-lb-close" aria-label="Close">&times;</button>
    <button type="button" class="bs-lb-prev" aria-label="Previous photo">&lsaquo;</button>
    <img alt="">
    <button type="button" class="bs-lb-next" aria-label="Next photo">&rsaquo;</button>
    <div class="bs-lb-caption"></div>
</div>
@stop
@section('styles')
@include('public._styles')
@stop
@section('scripts')
@include('public._scripts')
<script>
// Photo viewer: links with the same data-gallery form one gallery, in the owner's order;
// a data-tag on the opener shows only that category (e.g. the pool photos)
(function () {
    var box = document.getElementById('bs-lightbox');
    var img = box.querySelector('img');
    var caption = box.querySelector('.bs-lb-caption');
    var items = [], index = 0;

    function show() {
        var link = items[index];
        img.src = link.href;
        img.alt = link.dataset.caption || '';
        caption.textContent = (link.dataset.caption ? link.dataset.caption + ' · ' : '') + (index + 1) + ' / ' + items.length;
        var single = items.length < 2;
        box.querySelector('.bs-lb-prev').hidden = single;
        box.querySelector('.bs-lb-next').hidden = single;
    }
    function open(name, start, tag) {
        items = Array.prototype.slice.call(document.querySelectorAll('a[data-gallery="' + name + '"]'))
            .filter(function (link) { return !tag || link.dataset.tag === tag; });
        if (!items.length) return;
        index = Math.min(start || 0, items.length - 1);
        show();
        box.hidden = false;
    }
    function step(delta) {
        index = (index + delta + items.length) % items.length;
        show();
    }

    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[data-gallery]');
        var opener = e.target.closest('[data-open-gallery]');
        if (link) {
            e.preventDefault();
            var group = document.querySelectorAll('a[data-gallery="' + link.dataset.gallery + '"]');
            open(link.dataset.gallery, Array.prototype.indexOf.call(group, link));
        } else if (opener) {
            e.preventDefault();
            open(opener.dataset.openGallery, parseInt(opener.dataset.start, 10) || 0, opener.dataset.tag);
        }
    });
    document.addEventListener('keydown', function (e) {
        // Buttons open on Enter/Space by themselves (click); thumbnails with role="button" need this
        var opener = e.target.closest && e.target.closest('img[data-open-gallery]');
        if (opener && (e.key === 'Enter' || e.key === ' ')) {
            e.preventDefault();
            open(opener.dataset.openGallery, parseInt(opener.dataset.start, 10) || 0, opener.dataset.tag);
            return;
        }
        if (box.hidden) return;
        if (e.key === 'Escape') box.hidden = true;
        if (e.key === 'ArrowLeft') step(-1);
        if (e.key === 'ArrowRight') step(1);
    });
    box.querySelector('.bs-lb-close').addEventListener('click', function () { box.hidden = true; });
    box.querySelector('.bs-lb-prev').addEventListener('click', function (e) { e.stopPropagation(); step(-1); });
    box.querySelector('.bs-lb-next').addEventListener('click', function (e) { e.stopPropagation(); step(1); });
    box.addEventListener('click', function (e) { if (e.target === box) box.hidden = true; });

    // Swipe on phones
    var startX = null;
    box.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
    box.addEventListener('touchend', function (e) {
        if (startX === null) return;
        var dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) > 40) step(dx < 0 ? 1 : -1);
        startX = null;
    });
})();
</script>
@stop
