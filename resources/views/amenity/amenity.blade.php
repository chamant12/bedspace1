@extends('layout.app')
@section('content')

<div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Amenities</h4>
                    <form action="/addAmenity" method="POST">
                      <div class="form-group">
                        <label for="exampleInputUsername1">Property name<span style="color:red;">*</span></label>
                        <select name="property_id" id="property_id" required class="form-control">
                            <option value="">Select Property</option>
                        @foreach($properties as $property)
                            <option value="{{$property->id}}">{{$property->property_name}}</option>
                        @endforeach
                        </select>
                        </div>
                      <div class="form-group">
                        <label for="exampleInputEmail1">Amenity Type<span style="color:red;">*</span></label>
                        <select name="amenity_type" id="amenity_type" required class="form-control">
                            <option value="">Select Amenity Type</option>
                            <option value="property">Property</option>
                            <option value="roomType">RoomType</option>
                        </select>
                      </div>
                      <div class="form-group" id="room_type_id">
                        <label for="exampleInputUsername1">Available Room Types</label>
                         <selecl name="room_type_id" id="room_type_id">
                        </select>
                        </div>
                      <div class="form-group">
                        <label id="amenity_label">Amenities</label>
                        <ul id="amenity_id">
                        </ul id="amenity_list">
                        <div class="spinner-border" role="status" id="amenity_spinner">
                        <span class="visually-hidden">Loading...</span>
                        </div>
                      </div>
                      
                    <div class="form-group">
                        <label for="exampleInputUsername1">Add Amenity<span style="color:red;">*</span></label>
                        <select name="amenity_id" id="amenity_id" required class="form-control">
                            <option value="">Select Amenity</option>
                        </select>
                      </div>
                      <div class="form-group">
                        
                        <input type="submit" class="btn btn-success" value="Add"/>
                      </div>
                      </form>
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

$("#property_id").change(function(){
    $("#amenity_id").html('');
    $("#amenity_type").val('');
    $("#room_type_id").hide();
});
$("#amenity_type").change(function(){
    if($(this).val()!=""){
        if($(this).val()=="roomType"){
            $("#room_type_id").show();
        } else {
            $("#room_type_id").hide();
            $("#amenity_spinner").show();
            $.ajax({
                    url: '/api/get-amenities/'+$(this).val()+'/'+$("#property_id").val(),
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        $("#amenity_spinner").hide();
                        $("#amenity_list").html(response.html);
                        $("#amenity_id").html(response.selectHtml);
                        $("#room_type_id").html(response.roomTypeHtml);
                        
                    },
                    error: function(error) {
                        $('#loader-overlay').hide();
                        console.error('Error fetching districts:', error);
                    }
                });
        }
    } else {
        $("#amenity_id").html('');
    }
});

$("#room_type_id").change(function(){
    if($(this).val()!=""){
            $("#amenity_spinner").show();
            $.ajax({
                    url: '/api/get-amenities/roomType/'+$(this).val(),
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        $("#amenity_spinner").hide();
                        $("#amenity_id").html(response.html);
                        
                    },
                    error: function(error) {
                        $('#loader-overlay').hide();
                        console.error('Error fetching districts:', error);
                    }
                });
        } else {
            $("#amenity_id").html('');
        }
    
}):



function deleteAmenity(amenity_id,amenityType){
    var confirmed = confirm('Are you sure you want to delete this?');
    if(confirmed){
        $("#"+amenity_id+"_"+amenityType+"_"+type_id).remove();
        $('#loader-overlay').show();
        $.ajax({
            url: '/api/deleteAmenity/'+amenity_id+'/'+amenityType,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                    $("#"+amenity_id+"_"+amenityType+"_"+type_id).remove();
                    $('#loader-overlay').hide();
            }
        });
    }

    
    
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