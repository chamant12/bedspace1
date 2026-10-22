@extends('layout.app')
@section('content')

<div class="col-md-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Rates calendar &ndash; {{ $property->property_name }}</h4>
            <p class="card-description">
                Enter the nightly price for each occupancy and meal plan you offer. The
                <strong>USD price is required</strong> for every combination you fill in; the LKR
                price is shown to local guests. Leave a whole combination empty if you do not offer it.
            </p>
            <div class="row">
                <div class="form-group col-md-4">
                    <label>Room Type<span style="color:red;">*</span></label>
                    <select id="roomType_id" required class="form-control">
                        <option value="">Select Room Type</option>
                        @foreach($roomTypes as $roomType)
                            <option value="{{$roomType->id}}">{{$roomType->roomType}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>From Date<span style="color:red;">*</span></label>
                    <input id="from_date" class="form-control" type="text" required readonly/>
                </div>
                <div class="form-group col-md-3">
                    <label>To Date<span style="color:red;">*</span></label>
                    <input id="to_date" class="form-control" type="text" required readonly/>
                </div>
                <div class="form-group col-md-2 d-flex align-items-end">
                    <input id="searchRates" class="btn btn-success" value="Load" type="button" />
                </div>
            </div>
            <div id="rate-errors" class="alert alert-danger" style="display:none;"></div>
            <div class="table-responsive" id="response-container"></div>
            <div class="mt-3" id="save-container" style="display:none;">
                <button id="saveRates" type="button" class="btn btn-primary">Save rates</button>
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
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    $('#from_date, #to_date').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });

    function showErrors(messages) {
        $('#rate-errors').html(messages.map(function (m) { return $('<div>').text(m); })).show();
    }

    function errorMessages(xhr) {
        if (xhr.responseJSON && xhr.responseJSON.errors) {
            return Object.values(xhr.responseJSON.errors).flat();
        }
        return ['Something went wrong. Please try again.'];
    }

    $("#searchRates").click(function(){
        var roomType_id = $("#roomType_id").val();
        var from_date = $("#from_date").val();
        var to_date = $("#to_date").val();

        if (roomType_id == "" || from_date == "" || to_date == "") {
            alert('Please select a room type and a date range to proceed!');
            return false;
        }

        $('#rate-errors').hide();
        $('#loader-overlay').show();
        $.ajax({
            url: '/api/get-base-rates/' + roomType_id + '/' + from_date + '/' + to_date,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $('#loader-overlay').hide();
                $("#response-container").html(response.html);
                $('#save-container').show();
            },
            error: function(xhr) {
                $('#loader-overlay').hide();
                showErrors(errorMessages(xhr));
            }
        });
    });

    $("#saveRates").click(function(){
        // rates[date][occupancy_id][mealType_id] = {USD: "...", LKR: "..."}
        var rates = {};
        $('.rate_field').each(function () {
            var f = $(this);
            var date = f.data('date'), occ = f.data('occupancy'), meal = f.data('meal');
            rates[date] = rates[date] || {};
            rates[date][occ] = rates[date][occ] || {};
            rates[date][occ][meal] = rates[date][occ][meal] || {};
            rates[date][occ][meal][f.data('currency')] = f.val();
        });

        $('#rate-errors').hide();
        $('#loader-overlay').show();
        $.ajax({
            url: '/api/save-base-rates',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ roomType_id: $('#rate-grid').data('room-type'), rates: rates }),
            dataType: 'json',
            success: function(response) {
                $('#loader-overlay').hide();
                alert(response.message);
            },
            error: function(xhr) {
                $('#loader-overlay').hide();
                showErrors(errorMessages(xhr));
            }
        });
    });
});
</script>
@stop
@section('styles')
<style>
.spinner-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(255, 255, 255, 0.7);
  z-index: 9999;
  display: flex;
  justify-content: center;
  align-items: center;
}
#rate-grid input { width: 95px; }
#rate-grid td, #rate-grid th { padding: 4px 6px; white-space: nowrap; }
</style>
@stop
