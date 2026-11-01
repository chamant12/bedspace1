@extends('layout.app')
@section('content')
<form action="/update-property" method="POST">
<div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Update property details</h4>

                    <form class="forms-sample">
                      <div class="form-group">
                        <label for="exampleInputUsername1">Property name<span style="color:red;">*</span></label>
                        <input type="text" class="form-control" placeholder="Property name" name="property_name" required value="{{$property->property_name}}">
                      </div>
                      <div class="form-group">
                        <label for="exampleInputEmail1">Province<span style="color:red;">*</span></label>
                        <select name="" id="province_id" required class="form-control">
                            <option value="">Select Province</option>
                            @foreach($provinces as $province)
                                <option value="{{$province->id}}" {{($province->id==$property->city->district->province_id)?"selected":""}}>{{$province->province_name}}</option>
                            @endforeach
                        </select>
                      </div>
                      <div class="form-group">
                        <label for="exampleInputPassword1">District<span style="color:red;">*</span></label>
                        <select name="" id="district_id" required class="form-control">
                            <option value="">Select District</option>
                            @foreach($districts as $district)
                                <option value="{{$district->id}}" {{($district->id==$property->city->district->id)?"selected":""}}>{{$district->district_name}}</option>
                            @endforeach
                        </select>
                      </div>
                      <div class="form-group">
                        <label for="exampleInputConfirmPassword1">City<span style="color:red;">*</span></label>
                        <select name="city_id" id="city_id" required class="form-control">
                            <option value="">Select City</option>
                            @foreach($cities as $city)
                                <option value="{{$city->id}}" {{($city->id==$property->city->id)?"selected":""}}>{{$city->city_name}}</option>
                            @endforeach
                        </select>
                      </div>
                      <div class="form-group">
                        <label for="exampleInputUsername1">Address<span style="color:red;">*</span></label>
                        <textarea name="address" id="" class="form-control" required>{{$property->address}}</textarea>
                      </div>
                      @include('property._mapPicker', ['latitude' => $property->latitude, 'longitude' => $property->longitude])
                      @if(!$property->hasPin() && $property->mapUrl())
                        <p class="small text-muted">Previously entered map link: <a href="{{ $property->mapUrl() }}" target="_blank" rel="noopener">open</a>. Place a pin above to replace it.</p>
                      @endif
                      <div class="form-group">
                        <label>Cancellation policy: no refund within (days before check-in)<span style="color:red;">*</span></label>
                        <input type="number" class="form-control" name="cancellation_days" min="0" max="365" step="1" required
                               value="{{ old('cancellation_days', $property->effectiveCancellationDays()) }}">
                        <small class="text-muted">Applies to new bookings; existing bookings keep the policy they were booked under.</small>
                      </div>
                      <div class="form-group">
                        <label for="exampleInputUsername1">Contact Person<span style="color:red;">*</span></label>
                        <input type="text" class="form-control" placeholder="Contact Person" name="contact_name" required value="{{ old('contact_name', $property->primaryContact?->name) }}">
                      </div>
                      <div class="form-group">
                        <label for="exampleInputUsername1">Contact Mobile<span style="color:red;">*</span></label>
                        <input type="text" class="form-control" placeholder="Contact Mobile" name="contact_number" required value="{{ old('contact_number', $property->primaryContact?->mobile) }}">
                      </div>
                      <div class="form-group">
                        <label for="exampleInputUsername1">Contact Email</label>
                        <input type="email" class="form-control" placeholder="Email" name="contact_email" value="{{ old('contact_email', $property->primaryContact?->email) }}">
                      </div>
                      <div class="form-group">
                        <label for="exampleInputUsername1">Available Room Types</label>
                        @if(count($roomTypes)>0)
                            <ul style="margin-left:10%;">
                                @foreach($roomTypes as $roomType)
                                <li>{{$roomType->roomType}} <small class="text-muted">({{ (int) ($roomCounts[$roomType->id] ?? 0) }} rooms here)</small>
                                @unless($roomType->is_active)<span class="badge badge-secondary">Inactive</span>@endunless
                                &nbsp;&nbsp;&nbsp;
                                <a href="/view-roomType/{{$roomType->id}}" alt="View Property Details">
                                    <i class="fa fa-eye"></i>
                                </a>&nbsp;&nbsp;&nbsp;
                                <a data-toggle="tooltip" style="text-decoration:none;color:{{ $roomType->is_active ? 'red' : 'green' }};" title="{{ $roomType->is_active ? 'Deactivate room type (stops new bookings)' : 'Activate room type' }}" href="#" onclick="if(confirm('{{ $roomType->is_active ? 'Deactivate this room type? It will no longer be bookable.' : 'Activate this room type?' }}')){ var f=document.getElementById('toggleRoomTypeForm'); f.action='/roomType/{{$roomType->id}}/toggle-active'; f.submit(); } return false;">
                                    <i class="{{ $roomType->is_active ? 'ti-close' : 'ti-check' }}"></i>
                                </a>
                            </li>
                                @endforeach
                            </ul>
                        @else
                            <span class="form-control" style="border:0px;">No room types added</span><br/>
                        @endif
                        </div>
                      <div class="form-check form-check-flat form-check-primary">
                        <label class="form-check-label">
                          <input type="checkbox" class="form-check-input" required checked="checked">I hereby agree to the terms and conditions<i class="input-helper"></i><span style="color:red;">*</span> </label>
                      </div>
                      
                      {{csrf_field()}}
                      <input type="hidden" name='property_id' value="{{$property->id}}">
                      <button type="submit" class="btn btn-primary">Update</button>
                      <a class="btn btn-light" href="/dashboard">Cancel</a>
                    </form>
                  </div>
                </div>
              </div>
</form>
<form id="toggleRoomTypeForm" method="POST" style="display:none;">
    @csrf
</form>
@stop
@section('scripts')
@include('property._mapPickerScript')
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