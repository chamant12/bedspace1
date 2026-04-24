@extends('layout.app')
@section('content')
<form action="/update-doillar-rate" method="POST" class="forms-sample">
<div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Update Currency Rate</h4>
                      <div class="form-group">
                        <label for="exampleInputUsername1">Dollar Rate<span style="color:red;">*</span></label>
                        <input step="0.01" min="1" type="number" class="form-control" placeholder="Currency Rate" name="rate" required value="{{$currencyRate->rate}}">
                      </div>                      
                      {{csrf_field()}}
                      
                      <button type="submit" class="btn btn-primary">Update</button>
                      <a class="btn btn-light" href="/dashboard">Cancel</a>

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
@section('styles')
<style>
    .tooltip-container {
  position: relative;
  display: inline-block;
}

/* Hide tooltip by default */
.tooltip-text {
  visibility: hidden;
  width: 140px;
  background-color: #333;
  color: #fff;
  text-align: center;
  border-radius: 5px;
  padding: 5px;
  position: absolute;
  z-index: 1;
  bottom: 125%; /* Position above the link */
  left: 50%;
  margin-left: -70px;
  opacity: 0;
  transition: opacity 0.3s;
}

/* Show tooltip on hover */
.tooltip-container:hover .tooltip-text {
  visibility: visible;
  opacity: 1;
}

</style>
@stop