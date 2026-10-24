@extends('layout.app')
@section('content')
@if($stats['pendingOwners'] > 0)
<div class="alert alert-warning">
    {{ $stats['pendingOwners'] }} property owner(s) awaiting approval. <a href="/admin/owners">Review</a>
</div>
@endif
<div class="row">
    @foreach([
        'Property Owners' => $stats['owners'],
        'Properties' => $stats['properties'],
        'Active Properties' => $stats['activeProperties'],
        'Room Types' => $stats['roomTypes'],
        'Rooms' => $stats['rooms'],
    ] as $label => $value)
    <div class="col-sm-6 col-lg grid-margin stretch-card">
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
                    <h4 class="card-title card-title-dash">Latest Properties</h4>
                    <a href="/admin/properties" class="btn btn-primary btn-sm text-white">All Properties</a>
                </div>
                <div class="table-responsive mt-3">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Owner</th>
                                <th>City</th>
                                <th>Registered</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($latestProperties as $property)
                            <tr>
                                <td>{{ $property->property_name }}</td>
                                <td>{{ $property->propertyOwner?->name }}</td>
                                <td>{{ $property->city?->city_name }}</td>
                                <td>{{ $property->created_at?->format('Y-m-d') }}</td>
                                <td>
                                    @if($property->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
