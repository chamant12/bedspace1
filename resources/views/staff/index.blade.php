@extends('layout.app')
@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <h4 class="card-title">Staff accounts</h4>
            <a href="/staff/new" class="btn btn-primary btn-sm text-white">New staff account</a>
        </div>
        <p class="card-description">Staff can view the details, rates and inventory of their properties and manage reservations
            (check-in, payments, cancellations). With <strong>Edit property details</strong> they can also change property data,
            room types, rates, inventory, room numbers, amenities and photos. Only you can add properties and staff.</p>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Name</th><th>Email / mobile</th><th>Properties</th><th>Edit property details</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($staff as $member)
                    <tr>
                        <td>{{ $member->name }}</td>
                        <td>{{ $member->email }}<br/><small class="text-muted">{{ $member->phone }}</small></td>
                        <td>{{ $member->assignedProperties->pluck('property_name')->join(', ') ?: 'None' }}</td>
                        <td>{!! $member->can_edit_properties ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-secondary">No</span>' !!}</td>
                        <td>{!! $member->status ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>' !!}</td>
                        <td class="d-flex gap-2">
                            <a href="/staff/{{ $member->id }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="/staff/{{ $member->id }}/toggle-status"
                                  onsubmit="return confirm('{{ $member->status ? 'Deactivate' : 'Activate' }} {{ $member->name }}?');">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $member->status ? 'btn-danger' : 'btn-success' }}">{{ $member->status ? 'Deactivate' : 'Activate' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No staff accounts yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div></div>
</div>
@stop
