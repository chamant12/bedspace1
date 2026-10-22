{{-- Room type + date range picker shared by the editable and view-only calendars --}}
<div class="row">
    <div class="form-group col-md-4">
        <label>Room Type<span style="color:red;">*</span></label>
        <select id="roomType_id" required class="form-control">
            <option value="">Select Room Type</option>
            @foreach($roomTypes as $roomType)
                <option value="{{$roomType->id}}">{{$roomType->roomType}} (max {{$roomType->max_occupancy}})</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-3">
        <label>From Date<span style="color:red;">*</span></label>
        <input id="from_date" class="form-control" type="text" required readonly value="{{ $today }}"/>
    </div>
    <div class="form-group col-md-3">
        <label>To Date<span style="color:red;">*</span></label>
        <input id="to_date" class="form-control" type="text" required readonly value="{{ \Carbon\Carbon::parse($today)->addDays(13)->toDateString() }}"/>
    </div>
    <div class="form-group col-md-2 d-flex align-items-end">
        <input id="searchRates" class="btn btn-success" value="Load" type="button" />
    </div>
</div>
<small class="text-muted d-block mb-3">Up to 31 days at a time.</small>
