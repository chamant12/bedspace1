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
                            <th>Rooms</th>
                            <th>Commission %</th>
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
                            <td>{{ (int) ($roomCounts[$property->id] ?? 0) }}</td>
                            <td>
                                <form method="POST" action="/admin/properties/{{ $property->id }}/commission" class="d-flex gap-1">
                                    @csrf
                                    <input type="number" name="commission_rate" step="0.01" min="0" max="100" class="form-control form-control-sm" style="width:80px;"
                                           value="{{ $property->commission_rate }}" placeholder="{{ number_format(config('bedspace.default_commission_rate'), 2) }}"
                                           title="Empty = platform default">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Set</button>
                                </form>
                            </td>
                            <td>
                                @if($property->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="d-flex gap-2">
                                <a href="/rates/{{ $property->id }}/view" class="btn btn-sm btn-outline-primary">View rates</a>
                                <a href="/inventory/{{ $property->id }}/view" class="btn btn-sm btn-outline-primary">View inventory</a>
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
