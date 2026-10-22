{{--
    Rates grid for one room type at one property.
    Rows: date x occupancy. Columns: meal plan, each with a USD and an LKR price.
    Past dates (and the whole grid when not $editable) are rendered as text only.
--}}
<table class="table table-bordered" id="rate-grid" data-room-type="{{ $roomType->id }}" data-property="{{ $property->id }}">
    <thead>
        <tr>
            <th rowspan="2">Date</th>
            <th rowspan="2">Occupancy</th>
            @foreach($mealTypes as $mealType)
                <th colspan="2" class="text-center">{{ $mealType->mealType }}</th>
            @endforeach
        </tr>
        <tr>
            @foreach($mealTypes as $mealType)
                <th>USD</th>
                <th>LKR</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($dates as $date)
            @php($isPast = $date < $today)
            @foreach($occupancies as $occupancy)
            <tr class="{{ $isPast ? 'table-secondary' : '' }}" data-date="{{ $date }}">
                @if($loop->first)
                    <td rowspan="{{ $occupancies->count() }}" class="align-top">
                        <strong>{{ $date }}</strong><br/>
                        <small>{{ \Carbon\Carbon::parse($date)->format('D') }}</small><br/>
                        @if($open[$date])
                            <span class="badge badge-success">Open</span>
                        @else
                            <span class="badge badge-danger" title="No complete rates: not bookable on this date">Closed</span>
                        @endif
                        @if($isPast)
                            <span class="badge badge-secondary">Past</span>
                        @elseif($editable)
                            <div class="mt-1 d-flex flex-column gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary copy-date" data-date="{{ $date }}">Copy&hellip;</button>
                                <button type="button" class="btn btn-sm btn-outline-danger reset-date" data-date="{{ $date }}">Reset</button>
                            </div>
                        @endif
                    </td>
                @endif
                <td>{{ $occupancy->occupancy_name }}</td>
                @foreach($mealTypes as $mealType)
                    @foreach(['USD', 'LKR'] as $code)
                    @php($price = $grid[$date][$occupancy->id][$mealType->id][$code] ?? null)
                    <td>
                        @if($editable && !$isPast)
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm rate_field"
                                   data-date="{{ $date }}" data-occupancy="{{ $occupancy->id }}" data-meal="{{ $mealType->id }}" data-currency="{{ $code }}"
                                   value="{{ $price }}">
                        @else
                            {{ $price !== null ? number_format($price, 2) : '–' }}
                        @endif
                    </td>
                    @endforeach
                @endforeach
            </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
