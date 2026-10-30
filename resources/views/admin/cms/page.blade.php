@extends('layout.app')
@php($S = \App\Cms\SectionFields::class)
@section('content')
<div class="col-lg-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h3 class="mb-0">{{ $page->title }}
            @if($page->is_published)<span class="badge badge-success">Published</span>@else<span class="badge badge-secondary">Unpublished</span>@endif
        </h3>
        <div class="d-flex gap-2">
            <a href="{{ \App\Cms\Cms::pageUrl($page->slug) }}" target="_blank" class="btn btn-outline-primary btn-sm">View page</a>
            <a href="/admin/cms" class="btn btn-light btn-sm">Back</a>
        </div>
    </div>

    <div class="card grid-margin"><div class="card-body">
        <h4 class="card-title">Page details</h4>
        <form method="POST" action="/admin/cms/pages/{{ $page->id }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-4">
                <label>Title (browser tab and search results)</label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $page->title) }}" required maxlength="255">
            </div>
            <div class="col-md-5">
                <label>Description for search engines</label>
                <input type="text" name="meta_description" class="form-control" value="{{ old('meta_description', $page->meta_description) }}" maxlength="300">
            </div>
            <div class="col-md-2">
                <label><input type="checkbox" name="show_in_footer" value="1" @checked($page->show_in_footer)> Link in footer</label>
            </div>
            <div class="col-md-1"><button type="submit" class="btn btn-primary btn-sm text-white">Save</button></div>
        </form>
    </div></div>

    @foreach($page->sections as $section)
        @php($sectionFields = $fields[$section->id])
        @php($itemFields = $S::itemFields($sectionFields))
        <div class="card grid-margin" id="section-{{ $section->id }}"><div class="card-body">
            <h4 class="card-title">{{ $section->label }}
                @unless($section->is_active)<span class="badge badge-secondary">Hidden</span>@endunless
            </h4>
            <form method="POST" action="/admin/cms/sections/{{ $section->id }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    @if($S::uses($sectionFields, 'heading'))
                        <div class="form-group col-md-6">
                            <label>Heading</label>
                            <input type="text" name="heading" class="form-control" value="{{ $section->heading }}" maxlength="255">
                        </div>
                    @endif
                    @if($S::uses($sectionFields, 'subheading'))
                        <div class="form-group col-md-6">
                            <label>{{ $section->section_key === 'hero' ? 'Text under the heading' : 'Small heading / subtitle' }}</label>
                            <input type="text" name="subheading" class="form-control" value="{{ $section->subheading }}" maxlength="500">
                        </div>
                    @endif
                    @if($S::uses($sectionFields, 'body'))
                        <div class="form-group col-md-12">
                            <label>Text <small class="text-muted">(Markdown)</small></label>
                            <textarea name="body" class="form-control" rows="{{ strlen($section->body) > 1500 ? 22 : 7 }}" style="font-family: monospace; font-size: 13px;">{{ $section->body }}</textarea>
                        </div>
                    @endif
                    @if($S::uses($sectionFields, 'image'))
                        <div class="form-group col-md-6">
                            <label>Image</label>
                            @if($section->imageUrl())<div class="mb-2"><img src="{{ $section->imageUrl() }}" style="max-height:110px; border-radius:6px;" alt=""></div>@endif
                            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <small class="text-muted">Large photos are resized and optimised automatically.</small>
                        </div>
                    @endif
                    @if($S::uses($sectionFields, 'button'))
                        <div class="form-group col-md-3">
                            <label>Button text</label>
                            <input type="text" name="button_text" class="form-control" value="{{ $section->button_text }}" maxlength="100">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Button link</label>
                            <input type="text" name="button_url" class="form-control" value="{{ $section->button_url }}" maxlength="500" placeholder="/search">
                        </div>
                    @endif
                </div>

                @if($itemFields)
                    <h5 class="mt-2">Cards</h5>
                    <p class="text-muted small mb-2">Fill in a title to add a card; clear the title to remove one. Untick "Show" to hide a card for now.</p>
                    @php($items = $section->items ?? [])
                    @foreach(array_merge($items, [[], []]) as $index => $item)
                        <div class="border rounded p-2 mb-2 row g-2 align-items-center mx-0">
                            <div class="col-md-3"><input type="text" name="items[{{ $index }}][title]" class="form-control form-control-sm" placeholder="Title" value="{{ $item['title'] ?? '' }}" maxlength="255"></div>
                            @if(in_array('text', $itemFields))
                                <div class="col-md-4"><input type="text" name="items[{{ $index }}][text]" class="form-control form-control-sm" placeholder="Short text" value="{{ $item['text'] ?? '' }}" maxlength="1000"></div>
                            @endif
                            @if(in_array('url', $itemFields))
                                <div class="col-md-2"><input type="text" name="items[{{ $index }}][url]" class="form-control form-control-sm" placeholder="Link, e.g. /search?area=Kandy" value="{{ $item['url'] ?? '' }}" maxlength="500"></div>
                            @endif
                            @if(in_array('image', $itemFields))
                                <div class="col-md-2 d-flex align-items-center gap-1">
                                    @if(!empty($item['image_path']))<img src="{{ \App\Cms\Cms::imageUrl($item['image_path'], $item['image_disk'] ?? null) }}" style="height:32px; width:44px; object-fit:cover; border-radius:4px;" alt="">@endif
                                    <input type="file" name="items[{{ $index }}][image]" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                                </div>
                            @endif
                            <div class="col-md-1"><label class="small mb-0"><input type="checkbox" name="items[{{ $index }}][is_active]" value="1" @checked(($item['is_active'] ?? true))> Show</label></div>
                        </div>
                    @endforeach
                @endif

                <div class="d-flex align-items-center gap-3 mt-2">
                    <label class="mb-0"><input type="checkbox" name="is_active" value="1" @checked($section->is_active)> Show this section on the page</label>
                    <button type="submit" class="btn btn-primary btn-sm text-white">Save {{ strtolower($section->label) }}</button>
                </div>
            </form>
        </div></div>
    @endforeach
</div>
@stop
