@extends('layout.app')
@section('content')

<div class="container mt-4">

    <!-- Sortable container -->
    <div class="row sortable-gallery">

        <!-- Example item -->
        <div class="col-lg-4 mb-4 image-item" data-id="101">
            <div class="image-wrapper">
                <button class="view-btn">&#128065;</button>
                <button class="delete-btn">&times;</button>
                <img src="https://mdbcdn.b-cdn.net/img/Photos/Horizontal/Nature/4-col/img%20(73).webp"
                     data-full="https://mdbcdn.b-cdn.net/img/Photos/Horizontal/Nature/12-col/img%20(73).webp"
                     class="w-100 rounded">
            </div>
        </div>

        <div class="col-lg-4 mb-4 image-item" data-id="102">
            <div class="image-wrapper">
                <button class="view-btn">&#128065;</button>
                <button class="delete-btn">&times;</button>
                <img src="https://mdbcdn.b-cdn.net/img/Photos/Vertical/mountain1.webp"
                     data-full="https://mdbcdn.b-cdn.net/img/Photos/Vertical/mountain1.webp"
                     class="w-100 rounded">
            </div>
        </div>

        <div class="col-lg-4 mb-4 image-item" data-id="103">
            <div class="image-wrapper">
                <button class="view-btn">&#128065;</button>
                <button class="delete-btn">&times;</button>
                <img src="https://mdbcdn.b-cdn.net/img/Photos/Vertical/mountain2.webp"
                     data-full="https://mdbcdn.b-cdn.net/img/Photos/Vertical/mountain2.webp"
                     class="w-100 rounded">
            </div>
        </div>

    </div>
</div>

<!-- Lightbox -->
<div id="lightbox" class="lightbox">
    <img id="lightbox-img">
</div>




@stop
@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
$(function () {

    // ✅ ENABLE SORTABLE
    $('.sortable-gallery').sortable({
        placeholder: "sortable-placeholder",
        update: function () {
            updateOrder();
        }
    });

    // ✅ UPDATE ORDER FUNCTION
    function updateOrder() {
        let order = [];

        $('.image-item').each(function (index) {
            order.push({
                id: $(this).data('id'),
                sort_order: index + 1
            });
        });

        console.log(order);

        // 🔥 AJAX CALL TO LARAVEL
        $.ajax({
            url: '/api/update-photo-order',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                photos: order
            },
            success: function (res) {
                console.log('Order updated');
            }
        });
    }

    // ✅ VIEW IMAGE
    $(document).on('click', '.view-btn', function () {
        let src = $(this).siblings('img').data('full');
        $('#lightbox-img').attr('src', src);
        $('#lightbox').fadeIn();
    });

    $('#lightbox').click(function () {
        $(this).fadeOut();
    });

    // ✅ DELETE IMAGE
    $(document).on('click', '.delete-btn', function () {
        let el = $(this).closest('.image-item');
        let id = el.data('id');

        if (confirm('Delete this image?')) {

            el.fadeOut(300, function () {
                $(this).remove();
                updateOrder(); // re-sync order after delete
            });

            $.ajax({
                url: '/api/delete-photo/' + id,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                }
            });
        }
    });

});
let selectedFiles = [];

// CLICK → open file picker
$('#upload-area').on('click', function () {
    $('#file-input').click();
});

// FILE SELECT
$('#file-input').on('change', function (e) {
    handleFiles(e.target.files);
});

// DRAG EVENTS
$('#upload-area').on('dragover', function (e) {
    e.preventDefault();
    $(this).addClass('dragover');
});

$('#upload-area').on('dragleave', function () {
    $(this).removeClass('dragover');
});

$('#upload-area').on('drop', function (e) {
    e.preventDefault();
    $(this).removeClass('dragover');

    handleFiles(e.originalEvent.dataTransfer.files);
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

// UPLOAD
$('#upload-btn').on('click', function () {

    if (selectedFiles.length === 0) {
        alert('No files selected');
        return;
    }

    let formData = new FormData();

    selectedFiles.forEach((file, index) => {
        formData.append('photos[]', file);
    });

    formData.append('upload_type', '{{ $roomType ? "roomType" : "property" }}');
    formData.append('type_id', '{{ $roomType ? $roomType->id : $property->id }}');

    $.ajax({
        url: '/api/upload-photos',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function () {
            alert('Upload successful');
            location.reload(); // refresh gallery
        },
        error: function (err) {
            console.error(err);
        }
    });

});
</script>
@stop
@section('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.image-wrapper {
    position: relative;
    cursor: move;
}

.delete-btn, .view-btn {
    position: absolute;
    top: 8px;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    color: #fff;
    cursor: pointer;
}

.delete-btn { right: 8px; background: red; }
.view-btn { left: 8px; background: black; }

.sortable-placeholder {
    border: 2px dashed #ccc;
    height: 200px;
    margin-bottom: 1rem;
}

/* Lightbox */
.lightbox {
    display: none;
    position: fixed;
    z-index: 9999;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.9);
}

.lightbox img {
    max-width: 90%;
    max-height: 80%;
    margin: auto;
    display: block;
    margin-top: 5%;
}

.upload-area {
    border: 2px dashed #ccc;
    padding: 40px;
    text-align: center;
    cursor: pointer;
    border-radius: 8px;
    transition: 0.3s;
}

.upload-area.dragover {
    background: #f8f9fa;
    border-color: #007bff;
}

.preview-img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 6px;
}
</style>
@stop