@extends('layout.app')
@section('content')
<form action="/create-property" method="POST">
<div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Add new property</h4>

                    <form class="forms-sample">
                      <div class="form-group">
                        <label for="exampleInputUsername1">Property name<span style="color:red;">*</span></label>
                        <input type="text" class="form-control" placeholder="Property name" name="property_name" required>
                      </div>
                      <div class="form-group">
                        <label for="exampleInputEmail1">Province<span style="color:red;">*</span></label>
                        <select name="" id="province_id" required class="form-control">
                            <option value="">Select Province</option>
                            @foreach($provinces as $province)
                                <option value="{{$province->id}}">{{$province->province_name}}</option>
                            @endforeach
                        </select>
                      </div>
                      <div class="form-group">
                        <label for="exampleInputPassword1">District<span style="color:red;">*</span></label>
                        <select name="" id="district_id" required class="form-control">
                            <option value="">Select District</option>
                        </select>
                      </div>
                      <div class="form-group">
                        <label for="exampleInputConfirmPassword1">City<span style="color:red;">*</span></label>
                        <select name="city_id" id="city_id" required class="form-control">
                            <option value="">Select City</option>
                        </select>
                      </div>
                      <div class="form-group">
                        <label for="exampleInputUsername1">Address<span style="color:red;">*</span></label>
                        <textarea name="address" id="" class="form-control" required></textarea>
                      </div>
                      <div class="form-group">
                        <label for="exampleInputUsername1">Location (on Google maps)</label>
                        <input type="text" class="form-control" placeholder="Google location" name="location">
                      </div>
                      <div class="form-group">
                        <label for="exampleInputUsername1">Contact Person<span style="color:red;">*</span></label>
                        <input type="text" class="form-control" placeholder="Contact Person" name="contact_name" required>
                      </div>
                      <div class="form-group">
                        <label for="exampleInputUsername1">Contact Number<span style="color:red;">*</span></label>
                        <input type="text" class="form-control" placeholder="Contact Number" name="contact_number" required>
                      </div>
                      <div class="form-group">
                        <label for="exampleInputUsername1">Contact Email<span style="color:red;">*</span></label>
                        <input type="text" class="form-control" placeholder="Email" name="contact_email" required>
                      </div>
                      <div class="form-check form-check-flat form-check-primary">
                        <label class="form-check-label">
                          <input type="checkbox" class="form-check-input" required>I hereby agree to the terms and conditions<i class="input-helper"></i><span style="color:red;">*</span> </label>
                      </div>
                      {{csrf_field()}}
                      <button type="submit" class="btn btn-primary">Submit</button>
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

$("#province_id").change(function(){
    if($(this).val()!=""){

        $.ajax({
            url: '/api/get-districts-by-province/' + $(this).val(),
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                // 1. Start with the default option
                let str = "<option value=''>Select District</option>";
                
                // 2. jQuery automatically parses JSON if dataType is 'json', 
                // so JSON.parse(data) is usually unnecessary.
                let data_arr = (typeof data === 'string') ? JSON.parse(data) : data;

                // 3. Use $.each() for arrays or data_arr.forEach()
                $.each(data_arr, function(index, val) {
                    str += "<option value='" + val.id + "'>" + val.district_name + "</option>";
                });

                // 4. Use .html() instead of .append() to clear previous results
                $("#district_id").html(str);
            },
            error: function(error) {
                console.error('Error fetching districts:', error);
            }
        });
    }
});

$("#district_id").change(function(){
    if($(this).val()!=""){

        $.ajax({
            url: '/api/get-cities-by-district/' + $(this).val(),
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                // 1. Start with the default option
                let str = "<option value=''>Select City</option>";
                
                // 2. jQuery automatically parses JSON if dataType is 'json', 
                // so JSON.parse(data) is usually unnecessary.
                let data_arr = (typeof data === 'string') ? JSON.parse(data) : data;

                // 3. Use $.each() for arrays or data_arr.forEach()
                $.each(data_arr, function(index, val) {
                    str += "<option value='" + val.id + "'>" + val.city_name + "</option>";
                });

                // 4. Use .html() instead of .append() to clear previous results
                $("#city_id").html(str);
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