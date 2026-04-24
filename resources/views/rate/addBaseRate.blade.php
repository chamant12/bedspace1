@extends('layout.app')
@section('content')

<div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Add base rate</h4>
                      <div class="form-group">
                        <label for="exampleInputUsername1">Property name<span style="color:red;">*</span></label>
                        <input type="text" class="form-control" placeholder="Property name" name="property_name" readonly value="{{$property->property_name}}">
                      </div>
                      <div class="form-group">
                        <label for="exampleInputEmail1">Room Type<span style="color:red;">*</span></label>
                        <select name="" id="roomType_id" required class="form-control">
                            <option value="">Select Room Type</option>
                            @foreach($roomTypes as $roomType)
                                <option value="{{$roomType->id}}">{{$roomType->roomType}}</option>
                            @endforeach
                        </select>
                      </div>
                      <div class="form-group">
                        <label for="exampleInputEmail1">Currency<span style="color:red;">*</span></label>
                        <select name="" id="currency_id" required class="form-control">
                            <option value="">Select Currency</option>
                            @foreach($currencies as $currency)
                                <option value="{{$currency->id}}">{{$currency->currency}}</option>
                            @endforeach
                        </select>
                      </div>
                    <div class="form-group">
                        <label for="exampleInputUsername1">From Date<span style="color:red;">*</span></label>
                        <input id="from_date" class="form-control" type="date" required readonly/>
                      </div>
                      <div class="form-group">
                        <label for="exampleInputUsername1">To Date<span style="color:red;">*</span></label>
                        <input id="to_date" class="form-control" type="date" required readonly/>
                      </div>
                      <div class="form-group">
                        
                        <input id="searchRates" class="btn btn-success" value="Search" type="button" />
                      </div>
                      <div class="form-group">
                                   <div class="table-responsive" id="response-container">
                      
                    </div>
                        </div>

                  </div>
                </div>
              </div>
</form>
<!-- Add a unique ID to the container to toggle it with JS -->
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
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});


  $('#from_date').datepicker({
    format: 'yyyy-mm-dd',
    autoclose: true,
    todayHighlight: true
  });

  $('#to_date').datepicker({
    format: 'yyyy-mm-dd',
    autoclose: true,
    todayHighlight: true
  });


$("#searchRates").click(function(){
    var from_date = $("#from_date").val();
    var to_date = $("#to_date").val();
    var roomType_id = $("#roomType_id").val();
    var currency_id = $("#currency_id").val();
    
    if(roomType_id==""){
        alert('Please select a room type to proceed!');
        return false;
    }

    if(currency_id==""){
        alert('Please select a currency to proceed!');
        return false;
    }
    if(from_date ==""){
        alert('Please select start date to proceed!');
        return false;
    }
    if(to_date ==""){
        alert('Please select end date to proceed!');
        return false;
    }

    $('#loader-overlay').show();
        $.ajax({
            url: '/api/get-base-rates/'+roomType_id+'/'+currency_id+'/'+from_date+'/'+to_date,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $('#loader-overlay').hide();
                $("#response-container").html(response.html);
                $(".rate_field").focus(function() {
                            $(this).data('oldVal', $(this).val());
                        }).blur(function() {
                            var newVal = $(this).val();
                            if(newVal !== "" && newVal !== $(this).data('oldVal')) {
                        var occupancy_id = $(this).attr('occupancy_id');
                        var mealtype_id = $(this).attr('mealtype_id');
                        var roomType_id = $(this).attr('roomType_id');
                        var currency_type_id = $(this).attr('currency_type_id');
                        var rate_for = $(this).attr('rate_for');
                        var rate = $(this).val();
                        var input_box = $(this);
                        $(input_box).hide();
                        var spinner = $(this).parent().find(".spinner-border");
                        $(spinner).show();
                                $.ajax({
                                    url: '/api/update-baserate',
                                    type: 'POST',
                                    data: {
                                        _token: $('meta[name="csrf-token"]').attr('content'), // Required for Laravel
                                        occupancy_id: occupancy_id,
                                        mealtype_id: mealtype_id,
                                        roomType_id: roomType_id,
                                        currency_type_id: currency_type_id,
                                        rate_for: rate_for,
                                        rate: rate
                                    },
                                    dataType: 'json',
                                    success: function(data) {
                                        $(spinner).hide();
                                        $(input_box).show();
                                    },
                                    error: function(error) {
                                        console.error('Error fetching districts:', error);
                                    }
                                });
                            }
                        });
            },
            error: function(error) {
                $('#loader-overlay').hide();
                console.error('Error fetching districts:', error);
            }
        });
    
});


    });
</script>
@stop
@section('styles')
<style>
    .spinner-overlay {
  position: fixed; /* Fixes it to the viewport */
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(255, 255, 255, 0.7); /* Semi-transparent background */
  z-index: 9999; /* Ensures it is on the top layer */
  display: flex;
  justify-content: center; /* Centers horizontally */
  align-items: center;    /* Centers vertically */
}

</style>
@stop