@extends('layout.app')
@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">{{ $post->exists ? 'Edit article' : 'New article' }}</h4>
            <a href="/admin/cms/blog" class="btn btn-light btn-sm">Back</a>
        </div>
        <form method="POST" action="{{ $post->exists ? '/admin/cms/blog/'.$post->id : '/admin/cms/blog' }}" enctype="multipart/form-data">
            @csrf
            @if($post->exists) @method('PUT') @endif
            <div class="row">
                <div class="form-group col-md-8">
                    <label>Title<span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required maxlength="255" value="{{ old('title', $post->title) }}">
                </div>
                <div class="form-group col-md-4">
                    <label>Category</label>
                    <input type="text" name="category" class="form-control" maxlength="100" value="{{ old('category', $post->category) }}" placeholder="Heritage, Culture, Nature & wildlife...">
                </div>
                <div class="form-group col-md-8">
                    <label>Web address</label>
                    <div class="input-group">
                        <span class="input-group-text">/blog/</span>
                        <input type="text" name="slug" class="form-control" maxlength="255" value="{{ old('slug', $post->slug) }}" placeholder="made from the title if left empty">
                    </div>
                </div>
                <div class="form-group col-md-4">
                    <label>Author</label>
                    <input type="text" name="author_name" class="form-control" maxlength="100" value="{{ old('author_name', $post->author_name) }}">
                </div>
                <div class="form-group col-md-12">
                    <label>Summary (shown on cards and at the top of the article)</label>
                    <textarea name="excerpt" class="form-control" rows="2" maxlength="500">{{ old('excerpt', $post->excerpt) }}</textarea>
                </div>
                <div class="form-group col-md-12">
                    <label>Article<span class="text-danger">*</span> <small class="text-muted">(Markdown: ## Heading, **bold**, - list, [link](/search))</small></label>
                    <textarea name="body" class="form-control" rows="24" required style="font-family: monospace; font-size: 13px;">{{ old('body', $post->body) }}</textarea>
                </div>
                <div class="form-group col-md-6">
                    <label>Cover image</label>
                    @if($post->coverUrl())<div class="mb-2"><img src="{{ $post->coverUrl() }}" style="max-height:120px; border-radius:6px;" alt=""></div>@endif
                    <input type="file" name="cover" class="form-control" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="form-group col-md-6">
                    <label>Description for search engines</label>
                    <textarea name="meta_description" class="form-control" rows="2" maxlength="300">{{ old('meta_description', $post->meta_description) }}</textarea>
                </div>
                <div class="form-group col-md-4">
                    <label>Publish date</label>
                    <input type="date" name="published_at" class="form-control" value="{{ old('published_at', $post->published_at?->toDateString()) }}">
                    <small class="text-muted">A future date schedules the article.</small>
                </div>
                <div class="form-group col-md-4 d-flex align-items-center">
                    <label class="mb-0"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $post->is_published))> Published</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary text-white">Save article</button>
            @if($post->exists && $post->is_published)
                <a href="/blog/{{ $post->slug }}" target="_blank" class="btn btn-outline-primary">View on website</a>
            @endif
        </form>
    </div></div>
</div>
@stop
