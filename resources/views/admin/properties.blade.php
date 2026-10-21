@extends('layout.app')
@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">All Properties</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Owner</th>
                            <th>City</th>
                            <th>District</th>
                            <th>Room Types</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($properties as $property)
                        <tr>
                            <td>{{ $property->property_name }}</td>
                            <td>
                                {{ $property->propertyOwner?->name }}<br/>
                                <small class="text-muted">{{ $property->propertyOwner?->email }}</small>
                            </td>
                            <td>{{ $property->city?->city_name }}</td>
                            <td>{{ $property->city?->district?->district_name }}</td>
                            <td>{{ $property->room_types_count }}</td>
                            <td>
                                @if($property->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="/admin/properties/{{ $property->id }}/toggle-active"
                                      onsubmit="return confirm('{{ $property->is_active ? 'Deactivate' : 'Activate' }} this property?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $property->is_active ? 'btn-danger' : 'btn-success' }}">
                                        {{ $property->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop
