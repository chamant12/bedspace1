@extends('layout.app')
@section('content')
@if($missingRates->isNotEmpty())
<div class="alert alert-warning">
    You have not updated today's {{ $missingRates->pluck('code')->join(', ') }} exchange rate.
    <a href="/exchange-rates">Update now</a>
</div>
@endif
<div class="row">
    @foreach([
        'Properties' => $stats['properties'],
        'Active Properties' => $stats['activeProperties'],
        'Room Types' => $stats['roomTypes'],
        'Rooms' => $stats['rooms'],
    ] as $label => $value)
    <div class="col-sm-6 col-lg-3 grid-margin stretch-card">
        <div class="card card-rounded">
            <div class="card-body">
                <p class="statistics-title">{{ $label }}</p>
                <h3 class="rate-percentage">{{ $value }}</h3>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card card-rounded">
            <div class="card-body">
                <div class="d-sm-flex justify-content-between align-items-start">
                    <h4 class="card-title card-title-dash">My Properties</h4>
                    <a href="/add-property" class="btn btn-primary btn-sm text-white">Add Property</a>
                </div>
                @if($properties->isEmpty())
                    <p class="mt-3">You have not added any properties yet.</p>
                @else
                <div class="table-responsive mt-3">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>City</th>
                                <th>Rooms</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($properties as $property)
                            <tr>
                                <td>{{ $property->property_name }}</td>
                                <td>{{ $property->city?->city_name }}</td>
                                <td>{{ (int) ($roomCounts[$property->id] ?? 0) }}</td>
                                <td>
                                    @if($property->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="/view-property/{{ $property->id }}" class="btn btn-primary btn-sm" title="View Property Details"><i class="fa fa-eye"></i></a>
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
</div>
@stop
