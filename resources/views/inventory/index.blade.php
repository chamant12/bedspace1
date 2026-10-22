@extends('layout.app')
@section('content')

<div class="col-md-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">
                Inventory &ndash; {{ $property->property_name }}
                @unless($editable)<span class="badge badge-secondary">View only</span>@endunless
            </h4>
            <p class="card-description">
                Available rooms = total &minus; closed &minus; booked. A date that is closed in the rates calendar
                (no complete USD + LKR rates) has no rooms available.
                @if($editable) Closed rooms can be changed for today and future dates only. @endif
            </p>
            @include('rate._selector')

            <div id="inv-errors" class="alert alert-danger" style="display:none;"></div>
            <div id="inv-success" class="alert alert-success" style="display:none;"></div>

            @if($editable)
            {{-- Close the same number of rooms on a range of dates --}}
            <div id="range-panel" class="card border mb-3" style="display:none;">
                <div class="card-body">
                    <h5>Close rooms on a range of dates</h5>
                    <div class="row">
                        <div class="form-group col-md-3">
                            <label>From</label>
                            <input id="range_from" class="form-control" type="text" readonly/>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Until</label>
                            <input id="range_to" class="form-control" type="text" readonly/>
                        </div>
                        <div class="form-group col-md-2">
                            <label>Closed rooms</label>
                            <input id="range_closed" class="form-control" type="number" min="0" step="1" value="0"/>
                        </div>
                        <div class="form-group col-md-4">
                            <label>On these days</label><br/>
                            @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $day => $label)
                                <label class="me-2"><input type="checkbox" class="range-weekday" value="{{ $day }}" checked> {{ $label }}</label>
                            @endforeach
                        </div>
                    </div>
                    <small class="text-muted d-block mb-2">Set 0 to reopen. Past dates are never changed (up to 366 days at a time).</small>
                    <button id="applyRange" type="button" class="btn btn-primary btn-sm">Apply</button>
                    <button id="cancelRange" type="button" class="btn btn-light btn-sm">Cancel</button>
                </div>
            </div>
            @endif

            <div class="table-responsive" id="response-container"></div>

            @if($editable)
            <div class="mt-3 d-flex gap-2" id="save-container" style="display:none !important;">
                <button id="saveClosed" type="button" class="btn btn-primary">Save closed rooms</button>
                <button id="openRange" type="button" class="btn btn-outline-secondary">Close rooms on a range&hellip;</button>
            </div>
            @endif
        </div>
    </div>
</div>
<div id="loader-overlay" class="spinner-overlay" style="display:none;">
  <div class="spinner-border text-primary" role="status">
    <span class="visually-hidden">Loading...</span>
  </div>
</div>
@stop
@section('scripts')
<script>
$(document).ready(function(){
    var PROPERTY_ID = {{ $property->id }};
    var TODAY = '{{ $today }}';
    var GRID_URL = '{{ $editable ? '/api/inventory' : '/api/view-inventory' }}';

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    $('#from_date, #to_date').datepicker({ format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true });
    $('#range_from, #range_to').datepicker({ format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true, startDate: TODAY });

    function showErrors(messages) {
        $('#inv-success').hide();
        $('#inv-errors').html(messages.map(function (m) { return $('<div>').text(m); })).show();
        $('html, body').animate({ scrollTop: $('#inv-errors').offset().top - 100 }, 200);
    }

    function showSuccess(message) {
        $('#inv-errors').hide();
        $('#inv-success').text(message).show();
    }

    function errorMessages(xhr) {
        if (xhr.responseJSON && xhr.responseJSON.errors) {
            return Object.values(xhr.responseJSON.errors).flat();
        }
        return ['Something went wrong. Please try again.'];
    }

    function post(url, data, onSuccess) {
        $('#loader-overlay').show();
        $.ajax({
            url: url,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify($.extend({ property_id: PROPERTY_ID, roomType_id: $('#roomType_id').val() }, data)),
            dataType: 'json',
            success: function (response) {
                $('#loader-overlay').hide();
                onSuccess(response);
            },
            error: function (xhr) {
                $('#loader-overlay').hide();
                showErrors(errorMessages(xhr));
            }
        });
    }

    function loadGrid(onDone) {
        var roomType_id = $('#roomType_id').val();
        var from_date = $('#from_date').val();
        var to_date = $('#to_date').val();

        if (roomType_id == '' || from_date == '' || to_date == '') {
            alert('Please select a room type and a date range to proceed!');
            return;
        }

        $('#loader-overlay').show();
        $.ajax({
            url: GRID_URL + '/' + PROPERTY_ID + '/' + roomType_id + '/' + from_date + '/' + to_date,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                $('#loader-overlay').hide();
                $('#response-container').html(response.html);
                $('#save-container').attr('style', '');
                if (onDone) onDone();
            },
            error: function (xhr) {
                $('#loader-overlay').hide();
                showErrors(errorMessages(xhr));
            }
        });
    }

    $('#searchRates').click(function () {
        $('#inv-errors, #inv-success, #range-panel').hide();
        loadGrid();
    });

    $(document).on('click', '#saveTotal', function () {
        var total = $.trim($('#total_rooms').val());
        if (total === '' || parseInt(total) < 0 || parseInt(total) != total) {
            showErrors(['Total rooms must be a whole number of 0 or more.']);
            return;
        }
        post('/api/inventory/total', { total_rooms: parseInt(total) }, function (response) {
            loadGrid(function () { showSuccess(response.message); });
        });
    });

    $(document).on('click', '#saveClosed', function () {
        var closed = {}, messages = [];
        $('.closed_field').removeClass('is-invalid').each(function () {
            var f = $(this), v = $.trim(f.val()), max = parseInt(f.attr('max'));
            if (v === '' || parseInt(v) != v || parseInt(v) < 0 || parseInt(v) > max) {
                f.addClass('is-invalid');
                messages.push(f.data('date') + ': closed rooms must be between 0 and ' + max + '.');
            }
            closed[f.data('date')] = v;
        });
        if (messages.length) {
            showErrors(messages);
            return;
        }
        if ($.isEmptyObject(closed)) {
            showErrors(['There are no future dates in the selected range to save.']);
            return;
        }
        post('/api/inventory/closed', { closed: closed }, function (response) {
            loadGrid(function () { showSuccess(response.message); });
        });
    });

    $('#openRange').click(function () {
        $('#range-panel').show();
        $('html, body').animate({ scrollTop: $('#range-panel').offset().top - 100 }, 200);
    });

    $('#cancelRange').click(function () { $('#range-panel').hide(); });

    $('#applyRange').click(function () {
        var weekdays = $('.range-weekday:checked').map(function () { return parseInt(this.value); }).get();
        var closedRooms = $.trim($('#range_closed').val());
        if ($('#range_from').val() == '' || $('#range_to').val() == '') {
            alert('Please select the date range.');
            return;
        }
        if (closedRooms === '' || parseInt(closedRooms) != closedRooms || parseInt(closedRooms) < 0) {
            alert('Closed rooms must be a whole number of 0 or more.');
            return;
        }
        if (weekdays.length === 0) {
            alert('Please select at least one day of the week.');
            return;
        }
        post('/api/inventory/close-range', {
            from_date: $('#range_from').val(),
            to_date: $('#range_to').val(),
            closed_rooms: parseInt(closedRooms),
            weekdays: weekdays
        }, function (response) {
            $('#range-panel').hide();
            loadGrid(function () { showSuccess(response.message); });
        });
    });
});
</script>
@stop
@section('styles')
@include('rate._styles')
@stop
