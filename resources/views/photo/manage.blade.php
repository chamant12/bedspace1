@extends('layout.app')
@section('content')
@php($uploadType = $roomType ? 'roomType' : 'property')
@php($typeId = $roomType ? $roomType->id : $property->id)
<div class="col-lg-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h3 class="mb-0">{{ $roomType ? $roomType->roomType.' photos' : 'Property photos' }}
            <small class="text-muted">{{ $property->property_name }}</small></h3>
        <a href="/photos" class="btn btn-sm btn-outline-secondary">All galleries</a>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="/photos/property/{{ $property->id }}" class="btn btn-sm {{ $roomType ? 'btn-outline-primary' : 'btn-primary text-white' }}">Property</a>
        @foreach($roomTypes as $item)
            <a href="/photos/property/{{ $property->id }}/room-type/{{ $item->id }}"
               class="btn btn-sm {{ $roomType && $roomType->id === $item->id ? 'btn-primary text-white' : 'btn-outline-primary' }}">{{ $item->roomType }}</a>
        @endforeach
    </div>

    <div class="card grid-margin"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="card-title mb-0">Gallery ({{ $photos->count() }})</h4>
            <span id="order-status" class="small" role="status" aria-live="polite"></span>
        </div>
        <p class="card-description mt-2 mb-3">Drag photos to change their order (on a phone, press and hold, then drag), or use the arrows.
            The order is saved automatically. The first photo is the cover shown in search results.
            @if($roomType) This gallery is shared by all your properties that offer this room type. @endif</p>

        @if($photos->isEmpty())
            <p class="text-muted mb-0">No photos yet. Upload some below.</p>
        @else
            <div class="row" id="gallery">
                @foreach($photos as $photo)
                    <div class="col-6 col-md-4 col-xl-3 mb-4 photo-item" data-id="{{ $photo->id }}">
                        <div class="photo-card">
                            <span class="cover-badge">Cover</span>
                            <span class="drag-handle" title="Drag to reorder" aria-hidden="true">&#x2630;</span>
                            <img src="{{ $photo->thumb_url }}" data-full="{{ $photo->url }}" alt="Photo {{ $loop->iteration }}" draggable="false">
                            <div class="photo-actions">
                                <button type="button" class="btn btn-sm btn-light move-btn" data-dir="-1" title="Move earlier" aria-label="Move earlier">&larr;</button>
                                <button type="button" class="btn btn-sm btn-light view-btn" title="View" aria-label="View larger">&#128065;</button>
                                <button type="button" class="btn btn-sm btn-light move-btn" data-dir="1" title="Move later" aria-label="Move later">&rarr;</button>
                                <button type="button" class="btn btn-sm btn-danger remove-btn" title="Remove" aria-label="Remove photo">&times;</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div></div>

    <div class="card"><div class="card-body">
        <h4 class="card-title">Upload photos</h4>
        <p class="card-description">JPG, PNG or WebP, up to 10 MB each and 20 at a time. Photos are resized automatically. New photos are added at the end.</p>
        <div id="upload-area" class="upload-area" tabindex="0" role="button">
            <p>Drop images here or click to choose</p>
            <input type="file" id="file-input" multiple accept="image/jpeg,image/png,image/webp" hidden>
        </div>
        <div id="preview-container" class="row mt-3"></div>
        <button id="upload-btn" class="btn btn-primary text-white mt-2" type="button" disabled>Upload selected photos</button>
        <button id="clear-btn" class="btn btn-outline-secondary mt-2" type="button" hidden>Clear selection</button>
    </div></div>
</div>

<div id="lightbox" class="photo-lightbox" hidden>
    <button type="button" class="lightbox-close" aria-label="Close">&times;</button>
    <img id="lightbox-img" alt="">
</div>
<div id="upload-overlay" class="upload-overlay" hidden>
    <div class="upload-box">
        <div class="progress"><div id="upload-progress-bar" class="progress-bar" style="width:0%">0%</div></div>
        <p class="mt-2 mb-0">Uploading photos&hellip;</p>
    </div>
</div>
@stop

@section('styles')
<style>
.photo-card { position: relative; border-radius: 8px; overflow: hidden; background: #f3f3f3; cursor: grab; user-select: none; }
.photo-card img { display: block; width: 100%; height: 170px; object-fit: cover; }
.photo-actions { position: absolute; left: 0; right: 0; bottom: 0; display: flex; justify-content: center; gap: 4px; padding: 6px; background: linear-gradient(transparent, rgba(0,0,0,.55)); }
.photo-actions .btn { padding: 2px 8px; line-height: 1.4; }
.drag-handle { position: absolute; top: 6px; right: 6px; background: rgba(0,0,0,.6); color: #fff; border-radius: 4px; padding: 0 6px; font-size: 14px; }
.cover-badge { display: none; position: absolute; top: 6px; left: 6px; background: #198754; color: #fff; border-radius: 4px; padding: 1px 8px; font-size: 12px; font-weight: 600; }
.photo-item:first-child .cover-badge { display: inline-block; }
.photo-item:first-child .move-btn[data-dir="-1"], .photo-item:last-child .move-btn[data-dir="1"] { visibility: hidden; }
.sortable-ghost .photo-card { opacity: .35; outline: 2px dashed #0d6efd; }
.sortable-chosen .photo-card { cursor: grabbing; }
.saving #gallery { pointer-events: none; opacity: .8; }
.upload-area { border: 2px dashed #aaa; padding: 40px 16px; text-align: center; border-radius: 10px; cursor: pointer; background: #fafafa; }
.upload-area:hover, .upload-area:focus { border-color: #0d6efd; background: #f1f8ff; outline: none; }
.upload-area.dragover { border-color: #198754; background: #e9f7ef; }
.upload-area p { margin: 0; font-weight: 500; color: #555; }
.preview-img { width: 100%; height: 120px; object-fit: cover; border-radius: 6px; }
.photo-lightbox { position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,.9); display: flex; align-items: center; justify-content: center; }
.photo-lightbox[hidden], .upload-overlay[hidden] { display: none; }
.photo-lightbox img { max-width: 92%; max-height: 90%; border-radius: 6px; }
.lightbox-close { position: absolute; top: 12px; right: 20px; background: none; border: 0; color: #fff; font-size: 38px; line-height: 1; }
.upload-overlay { position: fixed; inset: 0; z-index: 10000; background: rgba(0,0,0,.4); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; }
.upload-box { background: #fff; padding: 24px 32px; border-radius: 10px; text-align: center; width: 300px; max-width: 90%; }
.upload-box .progress { height: 20px; }
.upload-box .progress-bar { background: #198754; color: #fff; line-height: 20px; font-size: 12px; }
</style>
@stop

@section('scripts')
<script src="/assets/vendors/sortablejs/Sortable.min.js"></script>
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const gallery = document.getElementById('gallery');
    const status = document.getElementById('order-status');

    function setStatus(text, cls) {
        status.textContent = text;
        status.className = 'small ' + (cls || '');
    }

    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(body || {})
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                if (!res.ok) throw new Error(data.message || 'Request failed (' + res.status + ').');
                return data;
            });
        });
    }

    // ---- Ordering ----
    if (gallery) {
        const currentOrder = function () {
            return Array.from(gallery.querySelectorAll('.photo-item')).map(function (el) { return el.dataset.id; });
        };
        let savedOrder = currentOrder();

        const restore = function (order) {
            order.forEach(function (id) {
                const el = gallery.querySelector('.photo-item[data-id="' + id + '"]');
                if (el) gallery.appendChild(el);
            });
        };

        const saveOrder = function () {
            const order = currentOrder();
            if (order.join() === savedOrder.join()) return;
            setStatus('Saving…', 'text-muted');
            document.body.classList.add('saving');
            postJson('/api/update-photo-order', { order: order.map(Number) })
                .then(function () {
                    savedOrder = order;
                    setStatus('Order saved', 'text-success');
                })
                .catch(function (err) {
                    restore(savedOrder);
                    setStatus(err.message + ' The previous order was restored.', 'text-danger');
                })
                .finally(function () { document.body.classList.remove('saving'); });
        };

        Sortable.create(gallery, {
            draggable: '.photo-item',
            filter: '.photo-actions',
            preventOnFilter: false,
            animation: 150,
            delay: 200,
            delayOnTouchOnly: true,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: saveOrder
        });

        gallery.addEventListener('click', function (e) {
            const item = e.target.closest('.photo-item');
            if (!item) return;

            const move = e.target.closest('.move-btn');
            if (move) {
                if (move.dataset.dir === '-1' && item.previousElementSibling) {
                    gallery.insertBefore(item, item.previousElementSibling);
                } else if (move.dataset.dir === '1' && item.nextElementSibling) {
                    gallery.insertBefore(item.nextElementSibling, item);
                }
                move.focus();
                saveOrder();
                return;
            }

            if (e.target.closest('.view-btn')) {
                document.getElementById('lightbox-img').src = item.querySelector('img').dataset.full;
                document.getElementById('lightbox').hidden = false;
                return;
            }

            if (e.target.closest('.remove-btn')) {
                if (!confirm('Remove this photo from the gallery?')) return;
                setStatus('Removing…', 'text-muted');
                postJson('/api/remove-photo/' + item.dataset.id)
                    .then(function () {
                        item.remove();
                        savedOrder = currentOrder();
                        setStatus('Photo removed', 'text-success');
                    })
                    .catch(function (err) { setStatus(err.message, 'text-danger'); });
            }
        });
    }

    // ---- Lightbox ----
    const lightbox = document.getElementById('lightbox');
    lightbox.addEventListener('click', function () { lightbox.hidden = true; });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') lightbox.hidden = true; });

    // ---- Upload ----
    const area = document.getElementById('upload-area');
    const input = document.getElementById('file-input');
    const previews = document.getElementById('preview-container');
    const uploadBtn = document.getElementById('upload-btn');
    const clearBtn = document.getElementById('clear-btn');
    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
    let selected = [];

    function refreshButtons() {
        uploadBtn.disabled = selected.length === 0;
        uploadBtn.textContent = selected.length ? 'Upload ' + selected.length + ' photo(s)' : 'Upload selected photos';
        clearBtn.hidden = selected.length === 0;
    }

    function addFiles(files) {
        Array.from(files).forEach(function (file) {
            if (!allowed.includes(file.type) || selected.length >= 20) return;
            selected.push(file);
            const col = document.createElement('div');
            col.className = 'col-4 col-md-2 mb-3';
            const img = document.createElement('img');
            img.className = 'preview-img';
            img.src = URL.createObjectURL(file);
            col.appendChild(img);
            previews.appendChild(col);
        });
        refreshButtons();
    }

    area.addEventListener('click', function () { input.click(); });
    area.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });
    input.addEventListener('change', function () { addFiles(input.files); input.value = ''; });
    ['dragenter', 'dragover'].forEach(function (ev) {
        area.addEventListener(ev, function (e) { e.preventDefault(); area.classList.add('dragover'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        area.addEventListener(ev, function (e) { e.preventDefault(); area.classList.remove('dragover'); });
    });
    area.addEventListener('drop', function (e) { addFiles(e.dataTransfer.files); });

    clearBtn.addEventListener('click', function () {
        selected = [];
        previews.innerHTML = '';
        refreshButtons();
    });

    uploadBtn.addEventListener('click', function () {
        if (!selected.length) return;
        const form = new FormData();
        selected.forEach(function (file) { form.append('photos[]', file); });
        form.append('upload_type', @json($uploadType));
        form.append('type_id', @json($typeId));

        const overlay = document.getElementById('upload-overlay');
        const bar = document.getElementById('upload-progress-bar');
        overlay.hidden = false;

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/upload-photos');
        xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.upload.addEventListener('progress', function (e) {
            if (!e.lengthComputable) return;
            const percent = Math.round(e.loaded / e.total * 100);
            bar.style.width = percent + '%';
            bar.textContent = percent + '%';
        });
        xhr.onload = function () {
            if (xhr.status >= 200 && xhr.status < 300) {
                location.reload();
                return;
            }
            overlay.hidden = true;
            let message = 'Upload failed.';
            try { message = JSON.parse(xhr.responseText).message || message; } catch (e) {}
            alert(message);
        };
        xhr.onerror = function () { overlay.hidden = true; alert('Upload failed. Check your connection and try again.'); };
        xhr.send(form);
    });
})();
</script>
@stop
