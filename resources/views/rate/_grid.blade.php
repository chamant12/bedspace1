{{-- Rates grid: one row per date and occupancy, a USD + LKR pair per meal plan --}}
<table class="table table-bordered" id="rate-grid" data-room-type="{{ $roomType->id }}">
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
                <th>USD <span style="color:red;">*</span></th>
                <th>LKR</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($dates as $date)
            @foreach($occupancies as $occupancy)
            <tr>
                @if($loop->first)
                    <td rowspan="{{ $occupancies->count() }}">{{ $date }}</td>
                @endif
                <td>{{ $occupancy->occupancy_name }}</td>
                @foreach($mealTypes as $mealType)
                    @foreach(['USD', 'LKR'] as $code)
                    <td>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm rate_field"
                               data-date="{{ $date }}" data-occupancy="{{ $occupancy->id }}" data-meal="{{ $mealType->id }}" data-currency="{{ $code }}"
                               value="{{ $grid[$date][$occupancy->id][$mealType->id][$code] ?? '' }}">
                    </td>
                    @endforeach
                @endforeach
            </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
