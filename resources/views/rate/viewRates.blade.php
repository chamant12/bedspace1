@extends('layout.app')
@section('content')

{{-- Read-only copy of the rates calendar, for roles that may see but not change rates --}}
<div class="col-md-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Rates calendar &ndash; {{ $property->property_name }} <span class="badge badge-secondary">View only</span></h4>
            <p class="card-description">
                A date is open for booking only if at least one occupancy and meal plan has both a USD and an LKR price.
            </p>
            @include('rate._selector')

            <div id="rate-errors" class="alert alert-danger" style="display:none;"></div>
            <div class="table-responsive" id="response-container"></div>
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

    $('#from_date, #to_date').datepicker({ format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true });

    $('#searchRates').click(function () {
        var roomType_id = $('#roomType_id').val();
        var from_date = $('#from_date').val();
        var to_date = $('#to_date').val();

        if (roomType_id == '' || from_date == '' || to_date == '') {
            alert('Please select a room type and a date range to proceed!');
            return;
        }

        $('#rate-errors').hide();
        $('#loader-overlay').show();
        $.ajax({
            url: '/api/view-base-rates/' + PROPERTY_ID + '/' + roomType_id + '/' + from_date + '/' + to_date,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                $('#loader-overlay').hide();
                $('#response-container').html(response.html);
            },
            error: function (xhr) {
                $('#loader-overlay').hide();
                var messages = (xhr.responseJSON && xhr.responseJSON.errors)
                    ? Object.values(xhr.responseJSON.errors).flat()
                    : ['Something went wrong. Please try again.'];
                $('#rate-errors').html(messages.map(function (m) { return $('<div>').text(m); })).show();
            }
        });
    });
});
</script>
@stop
@section('styles')
@include('rate._styles')
@stop
