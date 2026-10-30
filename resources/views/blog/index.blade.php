@extends('layout.ext-app')
@php($intro = $page?->sections->get('intro'))
@section('title', $page?->title ?? 'Travel journal')
@section('meta_description', $page?->meta_description)
@section('content')
@include('site._pageHead', ['heading' => $intro?->heading ?? 'Travel journal', 'subheading' => $intro?->subheading])
<div class="site-page-body">
    <div class="container">
        @if($categories->count() > 1)
            <div class="mb-4">
                <a href="/blog" class="site-btn {{ $category ? 'outline' : '' }} mr-2 mb-2" style="padding:6px 16px;">All</a>
                @foreach($categories as $name)
                    <a href="/blog?category={{ urlencode($name) }}" class="site-btn {{ $category === $name ? '' : 'outline' }} mr-2 mb-2" style="padding:6px 16px;">{{ $name }}</a>
                @endforeach
            </div>
        @endif
        @if($posts->isEmpty())
            <p>No articles yet. Please check back soon.</p>
        @else
            <div class="site-grid">
                @foreach($posts as $post)
                    @include('blog._card', ['post' => $post])
                @endforeach
            </div>
            <div class="mt-4">{{ $posts->links('pagination::bootstrap-4') }}</div>
        @endif
    </div>
</div>
@stop
