@extends('layout.ext-app')
@php($s = $page->sections)
@section('title', $page->title)
@section('meta_description', $page->meta_description)
@section('content')
@php($intro = $s->get('intro'))
@include('site._pageHead', ['heading' => $intro?->heading ?? $page->title, 'subheading' => $intro?->subheading])

@if($intro)
<section class="site-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="{{ $intro->imageUrl() ? 'col-lg-6' : 'col-lg-10 mx-auto' }} mb-4 mb-lg-0">
                <div class="site-prose">{{ \App\Cms\Cms::markdown($intro->body) }}</div>
            </div>
            @if($intro->imageUrl())
                <div class="col-lg-6"><img src="{{ $intro->imageUrl() }}" class="site-split-img" alt="{{ $intro->heading }}"></div>
            @endif
        </div>
    </div>
</section>
@endif

@if($mission = $s->get('mission'))
<section class="site-section alt">
    <div class="container" style="max-width: 860px;">
        <h2 class="site-heading text-center">{{ $mission->heading }}</h2>
        <div class="site-prose text-center">{{ \App\Cms\Cms::markdown($mission->body) }}</div>
    </div>
</section>
@endif

@if($travellers = $s->get('travellers'))
<section class="site-section">
    <div class="container">
        <div class="site-section-head"><h2>{{ $travellers->heading }}</h2></div>
        <div class="row">
            @foreach($travellers->activeItems() as $index => $item)
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="site-feature">
                        <div class="site-feature-icon"><i class="fas {{ ['fa-tags', 'fa-calendar-check', 'fa-credit-card', 'fa-comments'][$index % 4] }}"></i></div>
                        <h3>{{ $item['title'] }}</h3>
                        <p>{{ $item['text'] ?? '' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($owners = $s->get('owners'))
<section class="site-section pt-0">
    <div class="container">
        <div class="site-cta d-md-flex align-items-center justify-content-between">
            <div class="mr-md-4 mb-3 mb-md-0">
                <h2>{{ $owners->heading }}</h2>
                <div class="site-prose" style="color:#d7dde3;">{{ \App\Cms\Cms::markdown($owners->body) }}</div>
            </div>
            @if($owners->button_text)<a href="{{ $owners->button_url }}" class="site-btn accent">{{ $owners->button_text }}</a>@endif
        </div>
    </div>
</section>
@endif
@stop
