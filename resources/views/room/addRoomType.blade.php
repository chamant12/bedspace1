@extends('layout.app')
@section('content')
<form action="/create-room-type" method="POST">
<div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Add new room type</h4>

                    <form class="forms-sample" method="post" action="/create-room-type">
                    <div class="form-group">
                        <label for="exampleInputConfirmPassword1">City<span style="color:red;">*</span></label>
                        <select name="city_id" id="city_id" required class="form-control">
                            <option value="">Select City</option>
                            @foreach($cities as $city)
                                <option value="{{$city->id}}">{{$city->city_name}}</option>
                            @endforeach
                        </select>
                      </div>
                      <div class="form-group">
                        <label for="exampleInputEmail1">Property<span style="color:red;">*</span></label>
                        <select name="property_id" id="property_id" required class="form-control">
                            <option value="">Select Property</option>
                        </select>
                      </div>

                      <div class="form-group">
                        <label for="exampleInputUsername1">Room Type<span style="color:red;">*</span></label>
                        <input type="text" class="form-control" placeholder="Room Type" name="roomType"  required>
                      </div>
                    <div class="form-group">
                        <label for="exampleInputUsername1">Number of Rooms<span style="color:red;">*</span></label>
                        <input type="number" class="form-control" placeholder="Number of Rooms" name="noOfRooms"  required min="1" step="1">
                      </div>
                      <div class="form-group">
                        <label for="exampleInputUsername1">Maximum Occupancy<span style="color:red;">*</span></label>
                        <input type="number" class="form-control" placeholder="Max Occupancy" name="max_occupancy"  required min="1" step="1" max="10">
                      </div>
                      {{csrf_field()}}
                      <button type="submit" class="btn btn-primary">Add</button>
                      <a class="btn btn-light" href="/dashboard">Cancel</a>
                    </form>
                  </div>
                </div>
              </div>
</form>
@stop
@section('scripts')
<script>
    $(document).ready(function(){
        $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$("#city_id").change(function(){
    if($(this).val()!=""){

        $.ajax({
            url: '/api/get-properties-by-city/' + $(this).val()+'/{{auth()->user()->id}}',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                // 1. Start with the default option
                let str = "<option value=''>Select Property</option>";
                
                // 2. jQuery automatically parses JSON if dataType is 'json', 
                // so JSON.parse(data) is usually unnecessary.
                let data_arr = (typeof data === 'string') ? JSON.parse(data) : data;

                // 3. Use $.each() for arrays or data_arr.forEach()
                $.each(data_arr, function(index, val) {
                    str += "<option value='" + val.id + "'>" + val.property_name + "</option>";
                });

                // 4. Use .html() instead of .append() to clear previous results
                $("#property_id").html(str);
            },
            error: function(error) {
                console.error('Error fetching districts:', error);
            }
        });
    }
});


    });
</script>
@stop