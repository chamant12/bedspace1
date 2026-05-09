@extends('layout.app')

@section('content')

<div class="col-md-12 grid-margin stretch-card">
<div class="card">
<div class="card-body">

<h4 class="card-title">
    Manage {{ ($roomType) ? "Room" : "Property" }} Photos
</h4>

<div class="mb-3">
    <strong>Property:</strong> {{ $property->property_name }}
</div>

@if($roomType)
<div class="mb-3">
    <strong>Room Type:</strong> {{ $roomType->roomType }}
</div>
@endif

<div class="container mt-4">

    <!-- SORTABLE GALLERY -->
    <div class="row sortable-gallery">

        
        <div class="col-lg-4 mb-4 image-item" data-id="1">
            <div class="image-wrapper">
<button  class="view-btn">&#128065;</button>

                <!-- DELETE -->
                <button  class="delete-btn">&times;</button>
                <img 
                    src="https://mdbcdn.b-cdn.net/img/Photos/Horizontal/Nature/4-col/img%20(73).webp"
                    data-full="https://mdbcdn.b-cdn.net/img/Photos/Horizontal/Nature/4-col/img%20(73).webp"
                    class="w-100 rounded"
                >
            </div>
        </div>
        <div class="col-lg-4 mb-4 image-item" data-id="2">
            <div class="image-wrapper">
<button  class="view-btn">&#128065;</button>

                <!-- DELETE -->
                <button  class="delete-btn">&times;</button>
                <img 
                    src="https://mdbcdn.b-cdn.net/img/Photos/Vertical/mountain1.webp"
                    data-full="https://mdbcdn.b-cdn.net/img/Photos/Vertical/mountain1.webp"
                    class="w-100 rounded"
                >
            </div>
        </div>
        <div class="col-lg-4 mb-4 image-item" data-id="3">
            <div class="image-wrapper">
<button  class="view-btn">&#128065;</button>

                <!-- DELETE -->
                <button  class="delete-btn">&times;</button>
                <img 
                    src="https://mdbcdn.b-cdn.net/img/Photos/Vertical/mountain2.webp"
                    data-full="https://mdbcdn.b-cdn.net/img/Photos/Vertical/mountain2.webp"
                    class="w-100 rounded"
                >
            </div>
        </div>
        <div class="col-lg-4 mb-4 image-item" data-id="4">
            <div class="image-wrapper">
<button  class="view-btn">&#128065;</button>

                <!-- DELETE -->
                <button  class="delete-btn">&times;</button>
                <img 
                    src="https://mdbcdn.b-cdn.net/img/Photos/Horizontal/Nature/4-col/img%20(73).webp"
                    data-full="https://mdbcdn.b-cdn.net/img/Photos/Horizontal/Nature/4-col/img%20(73).webp"
                    class="w-100 rounded"
                >
            </div>
        </div>
        <div class="col-lg-4 mb-4 image-item" data-id="5">
            <div class="image-wrapper">
<button  class="view-btn">&#128065;</button>

                <!-- DELETE -->
                <button  class="delete-btn">&times;</button>
                <img 
                    src="https://mdbcdn.b-cdn.net/img/Photos/Horizontal/Nature/4-col/img%20(18).webp"
                    data-full="https://mdbcdn.b-cdn.net/img/Photos/Horizontal/Nature/4-col/img%20(18).webp"
                    class="w-100 rounded"
                >
            </div>
        </div>
        <div class="col-lg-4 mb-4 image-item" data-id="6">
            <div class="image-wrapper">
<button  class="view-btn">&#128065;</button>

                <!-- DELETE -->
                <button  class="delete-btn">&times;</button>
                <img 
                    src="https://mdbcdn.b-cdn.net/img/Photos/Vertical/mountain3.webp"
                    data-full="https://mdbcdn.b-cdn.net/img/Photos/Vertical/mountain3.webp"
                    class="w-100 rounded"
                >
            </div>
        </div>
        

    </div>
</div>

</div>
</div>
</div>
<div class="card mt-4">
    <div class="card-body">

        <h5>Upload Photos</h5>

        <div id="upload-area" class="upload-area">
            <p>Drag & Drop images here OR click to select</p>
            <input type="file" id="file-input" multiple accept="image/*" hidden>
        </div>

        <div id="preview-container" class="row mt-3"></div>

        <button id="upload-btn" class="btn btn-primary mt-3" type="button">
            Upload Selected Photos
        </button>

    </div>
</div>
<!-- LIGHTBOX -->
<div id="lightbox" class="lightbox">
    <span class="lightbox-close">&times;</span>
    <img id="lightbox-img">
</div>
<div id="upload-overlay" class="upload-overlay">
    <div class="upload-box">
        <div class="progress">
            <div id="upload-progress-bar" class="progress-bar" style="width: 0%">0%</div>
        </div>
        <p class="mt-2">Uploading images...</p>
    </div>
</div>
@endsection

@section('scripts')

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<script>
$(function () {

    // =========================
    // CSRF FIX
    // =========================
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    });

    // =========================
    // SORT (KEEP SIMPLE — WORKING)
    // =========================
    $('.sortable-gallery').sortable({
        placeholder: "sortable-placeholder",

        // 🔥 ONLY this fix (safe)
        cancel: ".view-btn, .delete-btn",

        update: updateOrder
    });

    function updateOrder() {
        let order = [];

        $('.image-item').each(function (index) {
            order.push({
                id: $(this).data('id'),
                sort_order: index + 1
            });
        });

        console.log(order);

        $.post('/api/update-photo-order', { photos: order });
    }

    // =========================
    // VIEW BUTTON (FIXED)
    // =========================
   $(document).on('click', '.view-btn', function (e) {
    e.preventDefault();
    e.stopPropagation();

    let src = $(this).closest('.image-wrapper').find('img').data('full');

    if (!src) {
        console.warn('Image not found');
        return;
    }

    $('#lightbox-img').attr('src', src);
    $('#lightbox').fadeIn(200);
});

    // =========================
    // CLOSE LIGHTBOX
    // =========================
    $(document).on('click', '#lightbox, .lightbox-close', function () {
        $('#lightbox').fadeOut(200);
    });

    // =========================
    // DELETE BUTTON (FIXED)
    // =========================
    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();
        e.stopPropagation(); // 🔥 IMPORTANT

        let el = $(this).closest('.image-item');
        let id = el.data('id');

        if (!id) {
            console.error('Missing ID');
            return;
        }

        if (!confirm('Delete this image?')) return;

        el.fadeOut(200, function () {
            $(this).remove();
            updateOrder();
        });

        $.ajax({
            url: '/api/delete-photo/' + id,
            method: 'DELETE',
            success: function () {
                console.log('Deleted:', id);
            },
            error: function (err) {
                console.error(err);
            }
        });
    });

});
// =========================
// UPLOAD DRAG & DROP
// =========================

let selectedFiles = [];

// CLICK → open file picker
$('#upload-area').on('click', function () {
    $('#file-input').trigger('click');
});

// FILE SELECT
$('#file-input').on('change', function (e) {
    handleFiles(e.target.files);
});

// DRAG OVER
$('#upload-area').on('dragover', function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).addClass('dragover');
});

// DRAG LEAVE
$('#upload-area').on('dragleave', function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).removeClass('dragover');
});

// DROP
$('#upload-area').on('drop', function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).removeClass('dragover');

    let files = e.originalEvent.dataTransfer.files;
    handleFiles(files);
});

// HANDLE FILES
function handleFiles(files) {
    for (let file of files) {

        if (!file.type.startsWith('image/')) continue;

        selectedFiles.push(file);

        let reader = new FileReader();

        reader.onload = function (e) {
            $('#preview-container').append(`
                <div class="col-md-3 mb-3">
                    <img src="${e.target.result}" class="preview-img">
                </div>
            `);
        };

        reader.readAsDataURL(file);
    }
}

// =========================
// UPLOAD BUTTON
// =========================
$('#upload-btn').on('click', function () {

    if (selectedFiles.length === 0) {
        alert('No files selected');
        return;
    }

    let formData = new FormData();

    selectedFiles.forEach(file => {
        formData.append('photos[]', file);
    });

    formData.append('upload_type', '{{ $roomType ? "roomType" : "property" }}');
    formData.append('type_id', '{{ $roomType ? $roomType->id : $property->id }}');

    // 🔥 SHOW OVERLAY
    $('#upload-overlay').css('display', 'flex');

    $.ajax({
        url: '/api/upload-photos',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,

        // 🔥 PROGRESS TRACKING
        xhr: function () {
            let xhr = new window.XMLHttpRequest();

            xhr.upload.addEventListener("progress", function (e) {
                if (e.lengthComputable) {
                    let percent = Math.round((e.loaded / e.total) * 100);

                    $('#upload-progress-bar')
                        .css('width', percent + '%')
                        .text(percent + '%');
                }
            });

            return xhr;
        },

        success: function () {

            // ensure 100%
            $('#upload-progress-bar')
                .css('width', '100%')
                .text('100%');

            setTimeout(() => {
                $('#upload-overlay').fadeOut(300);
                location.reload();
            }, 500);
        },

        error: function (err) {
            console.error(err);

            alert('Upload failed');

            $('#upload-overlay').fadeOut(300);
        }
    });

});
</script>

@endsection

@section('styles')

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

.image-wrapper {
    position: relative;
    cursor: move;
}

/* BUTTONS */
.delete-btn, .view-btn {
    position: absolute;
    top: 8px;
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 50%;
    color: #fff;
    cursor: pointer;
    z-index: 10;
}

.delete-btn {
    right: 8px;
    background: rgba(220,53,69,0.9);
}

.view-btn {
    left: 8px;
    background: rgba(0,0,0,0.7);
}

/* SORT PLACEHOLDER */
.sortable-placeholder {
    border: 2px dashed #ccc;
    height: 200px;
    margin-bottom: 1rem;
}

/* LIGHTBOX */
/* LIGHTBOX OVERLAY */
.lightbox {
    display: none;
    position: fixed;
    z-index: 9999;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
}

/* IMAGE */
.lightbox img {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    max-width: 90%;
    max-height: 90%;
    border-radius: 6px;
}

/* CLOSE BUTTON */
.lightbox-close {
    position: absolute;
    top: 20px;
    right: 30px;
    color: #fff;
    font-size: 35px;
    font-weight: bold;
    cursor: pointer;
    z-index: 10000;
}

/* OPTIONAL: HOVER EFFECT */
.lightbox-close:hover {
    color: #ccc;
}
.upload-area {
    border: 2px dashed #aaa;
    padding: 50px;
    text-align: center;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #fafafa;
}

.upload-area:hover {
    border-color: #007bff;
    background: #f1f8ff;
}

.upload-area.dragover {
    border-color: #28a745;
    background: #e9f7ef;
}

.upload-area p {
    margin: 0;
    font-weight: 500;
    color: #555;
}

.preview-img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 6px;
}
.upload-overlay {
    display: none;
    position: fixed;
    z-index: 10000;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;

    backdrop-filter: blur(6px);
    background: rgba(0,0,0,0.4);

    justify-content: center;
    align-items: center;
}

.upload-box {
    background: #fff;
    padding: 25px 40px;
    border-radius: 10px;
    text-align: center;
    width: 300px;
}

.progress {
    width: 100%;
    height: 20px;
    background: #eee;
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: #28a745;
    width: 0%;
    color: #fff;
    text-align: center;
    line-height: 20px;
    font-size: 12px;
}
</style>

@endsection