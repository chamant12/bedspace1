<div class="site-card">
    <a href="{{ url('/blog/'.$post->slug) }}">
        <div class="site-card-img" style="background-image: url('{{ $post->coverUrl() }}');"></div>
        <div class="site-card-body">
            @if($post->category)<div class="site-eyebrow" style="font-size:11px;">{{ $post->category }}</div>@endif
            <h3>{{ $post->title }}</h3>
            <p>{{ $post->excerpt }}</p>
            <div class="site-card-meta">{{ $post->published_at?->format('j M Y') }} &middot; {{ $post->readingMinutes() }} min read</div>
        </div>
    </a>
</div>
