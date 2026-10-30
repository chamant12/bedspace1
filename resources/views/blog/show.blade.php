@extends('layout.ext-app')
@section('title', $post->title)
@section('meta_description', $post->meta_description ?: $post->excerpt)
@section('content')
@include('site._pageHead', [
    'heading' => $post->title,
    'subheading' => collect([$post->category, $post->published_at?->format('j F Y'), $post->readingMinutes().' min read'])->filter()->join(' · '),
])
<div class="site-page-body">
    <div class="container" style="max-width: 860px;">
        @if($post->coverUrl())<img src="{{ $post->coverUrl() }}" class="site-article-cover" alt="{{ $post->title }}">@endif
        @if($post->excerpt)<p class="lead">{{ $post->excerpt }}</p>@endif
        <article class="site-prose">{{ \App\Cms\Cms::markdown($post->body) }}</article>
        <p class="text-muted small mt-4">By {{ $post->author_name ?: 'EpickTours' }}</p>
        <div class="site-cta mt-4 d-md-flex align-items-center justify-content-between">
            <div class="mr-md-4 mb-3 mb-md-0">
                <h2 style="font-size:24px;">Ready to see it for yourself?</h2>
                <p class="mb-0">Find hotels, villas and guesthouses across Sri Lanka.</p>
            </div>
            <a href="/search" class="site-btn accent">Find a stay</a>
        </div>
    </div>
    @if($related->isNotEmpty())
        <div class="container mt-5">
            <h2 class="site-heading" style="font-size:24px;">More from the travel journal</h2>
            <div class="site-grid">
                @foreach($related as $relatedPost)
                    @include('blog._card', ['post' => $relatedPost])
                @endforeach
            </div>
        </div>
    @endif
</div>
@stop
