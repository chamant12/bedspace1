@extends('layout.app')
@section('content')

<div class="col-md-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Rates calendar &ndash; {{ $property->property_name }}</h4>
            <p class="card-description">
                For each occupancy and meal plan you offer, enter <strong>both the USD and the LKR price</strong>.
                A date is open for booking only if at least one combination has both prices; otherwise the room type
                is closed on that date. Leave a room type without rates to hide it at this property.
                Past dates cannot be changed.
            </p>
            <div id="rate-errors" class="alert alert-danger" style="display:none;"></div>
            <div id="rate-success" class="alert alert-success" style="display:none;"></div>

            {{-- Same prices for every date in a range, in one submit --}}
            <div class="card border mb-4">
                <div class="card-body">
                    <h5 class="mb-1">Set rates for a date range</h5>
                    <p class="text-muted small mb-3">Enter the prices once and they are applied to every day from the start date to the end date
                        (past dates are never changed, up to 366 days at a time). Fill in the meal plans you offer; any you leave empty keep their current prices.</p>
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label>Room type<span class="text-danger">*</span></label>
                            <select id="range_roomType_id" class="form-control">
                                <option value="">Select room type</option>
                                @foreach($roomTypes as $roomType)
                                    <option value="{{ $roomType->id }}" data-max="{{ $roomType->max_occupancy }}">{{ $roomType->roomType }} (max {{ $roomType->max_occupancy }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>From<span class="text-danger">*</span></label>
                            <input id="range_from" class="form-control" type="text" readonly value="{{ $today }}"/>
                        </div>
                        <div class="form-group col-md-3">
                            <label>To<span class="text-danger">*</span></label>
                            <input id="range_to" class="form-control" type="text" readonly value="{{ \Carbon\Carbon::parse($today)->addDays(29)->toDateString() }}"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="d-block">On these days</label>
                        @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $day => $label)
                            <label class="me-2"><input type="checkbox" class="range-weekday" value="{{ $day }}" checked> {{ $label }}</label>
                        @endforeach
                    </div>

                    <div id="range-prices" style="display:none;">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-2 range-table">
                                <thead>
                                    <tr>
                                        <th rowspan="2">Guests</th>
                                        @foreach($mealTypes as $mealType)
                                            <th colspan="2" class="text-center">{{ $mealType->mealType }}</th>
                                        @endforeach
                                    </tr>
                                    <tr>
                                        @foreach($mealTypes as $mealType)
                                            <th class="text-center">USD</th><th class="text-center">LKR</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($occupancies as $occupancy)
                                    <tr class="range-row" data-size="{{ $occupancy->occupancy }}">
                                        <td class="text-nowrap">{{ $occupancy->occupancy_name }}</td>
                                        @foreach($mealTypes as $mealType)
                                            @foreach(['USD', 'LKR'] as $code)
                                                <td><input type="number" min="0" step="0.01" class="form-control form-control-sm range-price"
                                                           data-occupancy="{{ $occupancy->id }}" data-meal="{{ $mealType->id }}" data-currency="{{ $code }}"
                                                           aria-label="{{ $occupancy->occupancy_name }}, {{ $mealType->mealType }}, {{ $code }}"></td>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button id="applyRange" type="button" class="btn btn-primary">Apply to all dates in the range</button>
                    </div>
                    <p id="range-hint" class="text-muted small mb-0">Choose a room type to enter its prices.</p>
                </div>
            </div>

            <h5>Rates calendar</h5>
            @include('rate._selector')

            {{-- Copy one date's rates to future dates --}}
            <div id="copy-panel" class="card border mb-3" style="display:none;">
                <div class="card-body">
                    <h5>Copy rates of <span id="copy-source"></span></h5>
                    <div class="row">
                        <div class="form-group col-md-3">
                            <label>To dates from</label>
                            <input id="copy_from" class="form-control" type="text" readonly/>
                        </div>
                        <div class="form-group col-md-3">
                            <label>until</label>
                            <input id="copy_to" class="form-control" type="text" readonly/>
                        </div>
                        <div class="form-group col-md-6">
                            <label>On these days</label><br/>
                            @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $day => $label)
                                <label class="me-2"><input type="checkbox" class="copy-weekday" value="{{ $day }}" checked> {{ $label }}</label>
                            @endforeach
                        </div>
                    </div>
                    <small class="text-muted d-block mb-2">The selected dates get exactly the rates of the source date. Past dates are never changed (up to 366 days at a time).</small>
                    <button id="copyRates" type="button" class="btn btn-primary btn-sm">Copy</button>
                    <button id="cancelCopy" type="button" class="btn btn-light btn-sm">Cancel</button>
                </div>
            </div>

            <div class="table-responsive" id="response-container"></div>
            <div class="mt-3 d-flex gap-2" id="save-container" style="display:none !important;">
                <button id="saveRates" type="button" class="btn btn-primary">Save rates</button>
                <button id="resetAll" type="button" class="btn btn-outline-danger">Reset all future dates</button>
            </div>
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

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    $('#from_date, #to_date').datepicker({ format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true });
    $('#copy_from, #copy_to').datepicker({ format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true, startDate: TODAY });

    function showErrors(messages) {
        $('#rate-success').hide();
        $('#rate-errors').html(messages.map(function (m) { return $('<div>').text(m); })).show();
        $('html, body').animate({ scrollTop: $('#rate-errors').offset().top - 100 }, 200);
    }

    function showSuccess(message) {
        $('#rate-errors').hide();
        $('#rate-success').text(message).show();
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
            url: '/api/get-base-rates/' + PROPERTY_ID + '/' + roomType_id + '/' + from_date + '/' + to_date,
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
        $('#rate-errors, #rate-success, #copy-panel').hide();
        loadGrid();
    });

    // A combination needs both prices or neither
    function validateGrid() {
        var cells = {}, messages = [];
        $('.rate_field').removeClass('is-invalid').each(function () {
            var f = $(this), key = f.data('date') + '|' + f.data('occupancy') + '|' + f.data('meal');
            cells[key] = cells[key] || { fields: [], filled: 0 };
            cells[key].fields.push(f);
            var v = $.trim(f.val());
            if (v !== '' && parseFloat(v) < 0) {
                f.addClass('is-invalid');
                messages.push(f.data('date') + ': prices cannot be negative.');
            }
            if (v !== '' && parseFloat(v) > 0) cells[key].filled++;
        });
        $.each(cells, function (key, cell) {
            if (cell.filled === 1) {
                $.each(cell.fields, function (i, f) { f.addClass('is-invalid'); });
                var row = cell.fields[0].closest('tr');
                messages.push(key.split('|')[0] + ', ' + row.find('td').not('[rowspan]').first().text().trim()
                    + ': enter both the USD and the LKR price, or leave both empty.');
            }
        });
        return messages.filter(function (m, i) { return messages.indexOf(m) === i; });
    }

    $('#saveRates').click(function () {
        var messages = validateGrid();
        if (messages.length) {
            showErrors(messages);
            return;
        }

        // rates[date][occupancy_id][mealType_id] = {USD: "...", LKR: "..."} (future dates only; past dates have no inputs)
        var rates = {};
        $('.rate_field').each(function () {
            var f = $(this), date = f.data('date'), occ = f.data('occupancy'), meal = f.data('meal');
            rates[date] = rates[date] || {};
            rates[date][occ] = rates[date][occ] || {};
            rates[date][occ][meal] = rates[date][occ][meal] || {};
            rates[date][occ][meal][f.data('currency')] = f.val();
        });

        if ($.isEmptyObject(rates)) {
            showErrors(['There are no future dates in the selected range to save.']);
            return;
        }

        post('/api/save-base-rates', { rates: rates }, function (response) {
            loadGrid(function () { showSuccess(response.message); });
        });
    });

    $(document).on('click', '.reset-date', function () {
        var date = $(this).data('date');
        if (!confirm('Reset all rates of ' + date + '? The room type will be closed (not bookable) on this date.')) return;
        post('/api/reset-base-rates', { date: date }, function (response) {
            loadGrid(function () { showSuccess(response.message); });
        });
    });

    $('#resetAll').click(function () {
        if (!confirm('Reset the rates of ALL dates from today on for this room type? It will be hidden from guests at this property until you add rates again. Past dates are not affected.')) return;
        post('/api/reset-base-rates', { date: null }, function (response) {
            loadGrid(function () { showSuccess(response.message); });
        });
    });

    $(document).on('click', '.copy-date', function () {
        var date = $(this).data('date');
        if ($('.rate_field[data-date="' + date + '"]').filter(function () { return $(this).val() != (this.defaultValue); }).length) {
            alert('Please save your changes to ' + date + ' before copying it.');
            return;
        }
        $('#copy-source').text(date);
        $('#copy-panel').data('source', date).show();
        $('html, body').animate({ scrollTop: $('#copy-panel').offset().top - 100 }, 200);
    });

    $('#cancelCopy').click(function () { $('#copy-panel').hide(); });

    // ---- Set rates for a date range ----
    $('#range_from, #range_to').datepicker({ format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true, startDate: TODAY });

    // Only occupancies up to the room type's maximum
    $('#range_roomType_id').on('change', function () {
        var max = parseInt($(this).find(':selected').data('max'), 10);
        if (!max) {
            $('#range-prices').hide();
            $('#range-hint').show();
            return;
        }
        $('.range-row').each(function () {
            var show = parseInt($(this).data('size'), 10) <= max;
            $(this).toggle(show);
            if (!show) $(this).find('.range-price').val('');
        });
        $('#range-hint').hide();
        $('#range-prices').show();
    });

    $('#applyRange').click(function () {
        var roomTypeId = $('#range_roomType_id').val();
        var from = $('#range_from').val(), to = $('#range_to').val();
        var weekdays = $('.range-weekday:checked').map(function () { return parseInt(this.value); }).get();
        var messages = [];

        if (!from || !to || to < from) messages.push('Please choose a start date and an end date on or after it.');
        if (weekdays.length === 0) messages.push('Please select at least one day of the week.');

        // prices[occupancy_id][mealType_id] = {USD, LKR}; a meal plan needs both prices or neither
        var prices = {}, complete = 0;
        $('.range-price').removeClass('is-invalid');
        $('.range-row:visible').each(function () {
            var row = $(this);
            row.find('.range-price[data-currency="USD"]').each(function () {
                var usd = $(this), meal = usd.data('meal'), occ = usd.data('occupancy');
                var lkr = row.find('.range-price[data-currency="LKR"][data-meal="' + meal + '"]');
                var u = $.trim(usd.val()), l = $.trim(lkr.val());
                if (u === '' && l === '') return;
                if (u === '' || l === '' || parseFloat(u) <= 0 || parseFloat(l) <= 0) {
                    usd.add(lkr).addClass('is-invalid');
                    messages.push(row.find('td').first().text().trim() + ': enter both the USD and the LKR price (above 0), or leave both empty.');
                    return;
                }
                prices[occ] = prices[occ] || {};
                prices[occ][meal] = { USD: u, LKR: l };
                complete++;
            });
        });
        if (!roomTypeId) messages.push('Please choose a room type.');
        else if (complete === 0 && messages.length === 0) messages.push('Enter the USD and LKR price for at least one meal plan.');

        messages = messages.filter(function (m, i) { return messages.indexOf(m) === i; });
        if (messages.length) {
            showErrors(messages);
            return;
        }

        var days = Math.round((new Date(to) - new Date(from)) / 86400000) + 1;
        if (!confirm('Apply these prices to ' + $('#range_roomType_id option:selected').text() + ' on every selected day from ' + from + ' to ' + to + ' (' + days + ' day(s))?')) return;

        post('/api/set-range-rates', {
            roomType_id: roomTypeId,
            from_date: from,
            to_date: to,
            weekdays: weekdays.length === 7 ? [] : weekdays,
            prices: prices
        }, function (response) {
            showSuccess(response.message);
            // Refresh the calendar below if it shows the same room type
            if ($('#response-container').children().length && $('#roomType_id').val() == roomTypeId) {
                loadGrid(function () { showSuccess(response.message); });
            }
        });
    });

    $('#copyRates').click(function () {
        var weekdays = $('.copy-weekday:checked').map(function () { return parseInt(this.value); }).get();
        if ($('#copy_from').val() == '' || $('#copy_to').val() == '') {
            alert('Please select the date range to copy to.');
            return;
        }
        if (weekdays.length === 0) {
            alert('Please select at least one day of the week.');
            return;
        }
        post('/api/copy-base-rates', {
            source_date: $('#copy-panel').data('source'),
            from_date: $('#copy_from').val(),
            to_date: $('#copy_to').val(),
            weekdays: weekdays
        }, function (response) {
            $('#copy-panel').hide();
            loadGrid(function () { showSuccess(response.message); });
        });
    });
});
</script>
@stop
@section('styles')
@include('rate._styles')
@stop
