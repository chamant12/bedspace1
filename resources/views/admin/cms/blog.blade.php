@extends('layout.app')
@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <h4 class="card-title">Blog articles</h4>
            <div class="d-flex gap-2">
                <a href="/admin/cms/blog/new" class="btn btn-primary btn-sm text-white">New article</a>
                <a href="/admin/cms" class="btn btn-light btn-sm">Back</a>
            </div>
        </div>
        <table class="table table-striped">
            <thead><tr><th>Title</th><th>Category</th><th>Published</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($posts as $post)
                <tr>
                    <td style="white-space:normal;">{{ $post->title }}</td>
                    <td>{{ $post->category }}</td>
                    <td>{{ $post->published_at?->format('Y-m-d') }}</td>
                    <td>
                        @if($post->is_published && $post->published_at && $post->published_at->isFuture())
                            <span class="badge badge-warning">Scheduled</span>
                        @elseif($post->is_published)
                            <span class="badge badge-success">Published</span>
                        @else
                            <span class="badge badge-secondary">Draft</span>
                        @endif
                    </td>
                    <td class="d-flex gap-2">
                        <a href="/admin/cms/blog/{{ $post->id }}" class="btn btn-sm btn-primary text-white">Edit</a>
                        @if($post->is_published)<a href="/blog/{{ $post->slug }}" target="_blank" class="btn btn-sm btn-outline-primary">View</a>@endif
                        <form method="POST" action="/admin/cms/blog/{{ $post->id }}/toggle">
                            @csrf
                            <button type="submit" class="btn btn-sm {{ $post->is_published ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $post->is_published ? 'Unpublish' : 'Publish' }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">No articles yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@stop
