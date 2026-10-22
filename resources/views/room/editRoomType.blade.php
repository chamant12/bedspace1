@extends('layout.app')
@section('content')
<div class="col-md-6 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Room type</h4>
            <form class="forms-sample" method="post" action="/update-room-type">
                <div class="form-group">
                    <label>Room Type<span style="color:red;">*</span></label>
                    <input type="text" class="form-control" placeholder="Room Type" name="roomType" required maxlength="255" value="{{ old('roomType', $roomType->roomType) }}">
                </div>
                <div class="form-group">
                    <label>Maximum Occupancy<span style="color:red;">*</span></label>
                    <input type="number" class="form-control" placeholder="Max Occupancy" name="max_occupancy" required min="1" step="1" max="10" value="{{ old('max_occupancy', $roomType->max_occupancy) }}">
                </div>
                <input type="hidden" name="roomType_id" value="{{$roomType->id}}"/>
                @csrf
                <button type="submit" class="btn btn-primary">Update</button>
                <a class="btn btn-light" href="/my-properties">Cancel</a>
            </form>
        </div>
    </div>
</div>

<div class="col-md-6 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Rooms per property</h4>
            <p class="card-description">This room type can be used at any of your properties. Set the number of rooms on each property's Inventory page.</p>
            <table class="table table-striped">
                <thead>
                    <tr><th>Property</th><th>Rooms</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($properties as $property)
                    <tr>
                        <td>{{ $property->property_name }}</td>
                        <td>{{ $roomCounts[$property->id] ?? 0 }}</td>
                        <td><a href="/inventory/{{ $property->id }}" class="btn btn-sm btn-outline-primary">Inventory</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@stop
