{{--
    Inventory for one room type at one property.
    Past dates (and everything when not $editable) are text only.
--}}
<div id="inventory-grid" data-room-type="{{ $roomType->id }}" data-property="{{ $property->id }}">
    <div class="d-flex align-items-end gap-2 mb-3">
        <div>
            <label class="mb-1">Total {{ $roomType->roomType }} rooms at {{ $property->property_name }}</label>
            @if($editable)
                <input type="number" id="total_rooms" class="form-control" min="0" step="1" value="{{ $totalRooms }}" style="width:120px;">
            @else
                <div><strong>{{ $totalRooms }}</strong></div>
            @endif
        </div>
        @if($editable)
            <button type="button" id="saveTotal" class="btn btn-outline-primary">Save total</button>
        @endif
    </div>

    @unless($sellable)
        <div class="alert alert-warning">
            This room type is not bookable at this property (the property or room type is inactive, or no rooms have been set). Availability shows 0.
        </div>
    @endunless

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Date</th>
                <th>Rates</th>
                <th>Total</th>
                <th>Closed</th>
                <th>Booked</th>
                <th>Available</th>
            </tr>
        </thead>
        <tbody>
            @foreach($days as $date => $day)
            <tr class="{{ $day['past'] ? 'table-secondary' : '' }}">
                <td>
                    <strong>{{ $date }}</strong> <small>{{ \Carbon\Carbon::parse($date)->format('D') }}</small>
                    @if($day['past']) <span class="badge badge-secondary">Past</span> @endif
                </td>
                <td>
                    @if($day['open'])
                        <span class="badge badge-success">Open</span>
                    @else
                        <span class="badge badge-danger" title="No complete rates in the rates calendar: not bookable">Closed</span>
                    @endif
                </td>
                <td>{{ $totalRooms }}</td>
                <td>
                    @if($editable && !$day['past'])
                        <input type="number" class="form-control form-control-sm closed_field" style="width:90px;"
                               min="0" max="{{ max(0, $totalRooms - $day['booked']) }}" step="1"
                               data-date="{{ $date }}" value="{{ $day['closed'] }}">
                    @else
                        {{ $day['closed'] }}
                    @endif
                </td>
                <td>{{ $day['booked'] }}</td>
                <td><strong>{{ $day['available'] }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
