@extends('layout.app')
@section('content')
<div class="row">
    @foreach([
        ['Website settings', 'Name, contact details, social links, footer', '/admin/cms/settings', 'mdi-cog'],
        ['Blog articles', $publishedCount.' published of '.$postCount, '/admin/cms/blog', 'mdi-newspaper'],
        ['Contact messages', $newMessages.' new', '/admin/cms/messages', 'mdi-email'],
    ] as [$title, $text, $link, $icon])
    <div class="col-md-4 grid-margin stretch-card">
        <a href="{{ $link }}" class="card card-rounded text-decoration-none">
            <div class="card-body">
                <h4 class="card-title"><i class="mdi {{ $icon }} me-1"></i>{{ $title }}</h4>
                <p class="mb-0 text-muted">{{ $text }}</p>
            </div>
        </a>
    </div>
    @endforeach
</div>

<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <h4 class="card-title">Website pages</h4>
            <form method="POST" action="/admin/cms/pages" class="d-flex gap-2">
                @csrf
                <input type="text" name="title" class="form-control form-control-sm" placeholder="New page title" required maxlength="255">
                <button type="submit" class="btn btn-sm btn-primary text-white">Add page</button>
            </form>
        </div>
        <p class="card-description">Edit the text, images and links of each public page. Text uses Markdown: <code>**bold**</code>, <code>## Heading</code>, <code>- list item</code>, <code>[link text](/search)</code>.</p>
        <table class="table table-striped">
            <thead><tr><th>Page</th><th>Address</th><th>Sections</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach($pages as $page)
                <tr>
                    <td>{{ $page->title }}</td>
                    <td><a href="{{ \App\Cms\Cms::pageUrl($page->slug) }}" target="_blank">{{ parse_url(\App\Cms\Cms::pageUrl($page->slug), PHP_URL_PATH) ?: '/' }}</a></td>
                    <td>{{ $page->sections_count }}</td>
                    <td>
                        @if($page->is_published)<span class="badge badge-success">Published</span>@else<span class="badge badge-secondary">Unpublished</span>@endif
                        @if($page->show_in_footer)<span class="badge badge-info">In footer</span>@endif
                    </td>
                    <td class="d-flex gap-2">
                        <a href="/admin/cms/pages/{{ $page->id }}" class="btn btn-sm btn-primary text-white">Edit</a>
                        @if($page->slug !== 'home')
                            <form method="POST" action="/admin/cms/pages/{{ $page->id }}/toggle">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $page->is_published ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $page->is_published ? 'Unpublish' : 'Publish' }}</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div></div>
</div>
@stop
