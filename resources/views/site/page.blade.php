@extends('layout.ext-app')
@php($body = $page->sections->get('body') ?? $page->sections->first())
@section('title', $page->title)
@section('meta_description', $page->meta_description)
@section('content')
@include('site._pageHead', ['heading' => $body?->heading ?? $page->title, 'subheading' => $body?->subheading])
<div class="site-page-body">
    <div class="container" style="max-width: 860px;">
        @foreach($page->sections as $section)
            <div class="site-prose mb-4">
                @if($section->heading && $section !== $body)<h2>{{ $section->heading }}</h2>@endif
                {{ \App\Cms\Cms::markdown($section->body) }}
            </div>
        @endforeach
        <p class="text-muted small">Last updated {{ $page->updated_at?->format('j F Y') }}</p>
    </div>
</div>
@stop
