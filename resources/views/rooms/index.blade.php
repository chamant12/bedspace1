@extends('layout.app')
@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Room numbers &ndash; {{ $property->property_name }}</h4>
            <p class="card-description">
                Room numbers are used to assign guests to rooms at check-in. They are separate from the
                room counts on the <a href="/inventory/{{ $property->id }}">Inventory</a> page.
            </p>
            <form method="POST" action="/rooms" class="row g-2 align-items-end">
                @csrf
                <input type="hidden" name="property_id" value="{{ $property->id }}">
                <div class="col-md-3">
                    <label>Room type<span style="color:red;">*</span></label>
                    <select name="roomType_id" class="form-control" required>
                        <option value="">Select Room Type</option>
                        @foreach($roomTypes as $roomType)
                            <option value="{{ $roomType->id }}" @selected(old('roomType_id') == $roomType->id)>{{ $roomType->roomType }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Room numbers<span style="color:red;">*</span></label>
                    <input type="text" name="room_numbers" class="form-control" required maxlength="2000"
                           placeholder="e.g. 101-110, 201, 202, A1" value="{{ old('room_numbers') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-success">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Summary</h4>
            <table class="table table-striped">
                <thead>
                    <tr><th>Room type</th><th>Rooms (inventory)</th><th>Active room numbers</th></tr>
                </thead>
                <tbody>
                    @foreach($roomTypes as $roomType)
                        @php($total = (int) ($totals[$roomType->id] ?? 0))
                        @php($count = (int) ($numbered[$roomType->id] ?? 0))
                        @continue($total === 0 && $count === 0)
                        <tr>
                            <td>{{ $roomType->roomType }}</td>
                            <td>{{ $total }}</td>
                            <td>
                                {{ $count }}
                                @if($count < $total)
                                    <span class="text-warning">({{ $total - $count }} without a room number)</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">All room numbers</h4>
            @if($rooms->isEmpty())
                <p>No room numbers added yet.</p>
            @else
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr><th>Room number</th><th>Room type</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach($rooms as $room)
                        <tr>
                            <td>
                                <form method="POST" action="/rooms/{{ $room->id }}" id="room-form-{{ $room->id }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="room_number" class="form-control form-control-sm" style="width:120px;"
                                           value="{{ $room->room_number }}" required maxlength="20">
                                </form>
                            </td>
                            <td>
                                <select name="roomType_id" form="room-form-{{ $room->id }}" class="form-control form-control-sm" required>
                                    @foreach($roomTypes as $roomType)
                                        <option value="{{ $roomType->id }}" @selected($room->roomType_id == $roomType->id)>{{ $roomType->roomType }}</option>
                                    @endforeach
                                    @unless($roomTypes->contains('id', $room->roomType_id))
                                        <option value="{{ $room->roomType_id }}" selected>{{ $room->roomType?->roomType }} (inactive)</option>
                                    @endunless
                                </select>
                            </td>
                            <td>
                                @if($room->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="d-flex gap-2">
                                <button type="submit" form="room-form-{{ $room->id }}" class="btn btn-sm btn-primary">Save</button>
                                <form method="POST" action="/rooms/{{ $room->id }}/toggle-active"
                                      onsubmit="return confirm('{{ $room->is_active ? 'Deactivate' : 'Activate' }} room {{ $room->room_number }}?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $room->is_active ? 'btn-danger' : 'btn-success' }}">
                                        {{ $room->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
@stop
