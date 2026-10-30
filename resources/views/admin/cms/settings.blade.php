@extends('layout.app')
@section('content')
<div class="col-lg-9 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Website settings</h4>
            <a href="/admin/cms" class="btn btn-light btn-sm">Back</a>
        </div>
        <p class="card-description">Shown in the header, footer and contact page. Leave a field empty to hide it.</p>
        <form method="POST" action="/admin/cms/settings" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label>Logo (optional; the built-in EpickTours logo is shown when none is uploaded)</label>
                @if(!empty($settings['logo_path']))
                    <div class="mb-2">
                        <img src="{{ \App\Cms\Cms::imageUrl($settings['logo_path'], $settings['logo_disk'] ?? null) }}" style="max-height:60px;" alt="Logo">
                        <label class="ms-3"><input type="checkbox" name="remove_logo" value="1"> Remove logo</label>
                    </div>
                @endif
                <input type="file" name="logo" class="form-control" accept="image/jpeg,image/png,image/webp">
            </div>
            @foreach($fields as $key => [$label, $type])
                <div class="form-group">
                    <label for="setting-{{ $key }}">{{ $label }}</label>
                    @if($type === 'textarea')
                        <textarea id="setting-{{ $key }}" name="{{ $key }}" class="form-control" rows="3">{{ old($key, $settings[$key] ?? '') }}</textarea>
                    @else
                        <input id="setting-{{ $key }}" type="{{ $type }}" name="{{ $key }}" class="form-control" value="{{ old($key, $settings[$key] ?? '') }}" @if($key === 'site_name') required @endif>
                    @endif
                </div>
            @endforeach
            <button type="submit" class="btn btn-primary text-white">Save settings</button>
        </form>
    </div></div>
</div>
@stop
