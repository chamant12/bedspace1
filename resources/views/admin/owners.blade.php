@extends('layout.app')
@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <h4 class="card-title">Property Owners</h4>
                <a href="/admin/owners/new" class="btn btn-primary btn-sm text-white">New owner</a>
            </div>
            <p class="card-description">Owners who signed up themselves must be approved before they can add properties, staff, room types or rates.</p>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Properties</th>
                            <th>Registered</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($owners as $owner)
                        <tr>
                            <td>{{ $owner->name }}</td>
                            <td>{{ $owner->email }}</td>
                            <td>{{ $owner->phone }}</td>
                            <td>{{ $owner->properties_count }}</td>
                            <td>{{ $owner->created_at?->format('Y-m-d') }}</td>
                            <td>
                                @if(!$owner->approved_at)
                                    <span class="badge badge-warning">Awaiting approval</span>
                                @elseif($owner->status)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="d-flex gap-2">
                                @if(!$owner->approved_at)
                                    <form method="POST" action="/admin/owners/{{ $owner->id }}/approve"
                                          onsubmit="return confirm('Approve {{ $owner->name }}? They will be able to add properties, staff and rates.');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                @endif
                                <form method="POST" action="/admin/owners/{{ $owner->id }}/toggle-status"
                                      onsubmit="return confirm('{{ $owner->status ? 'Deactivate' : 'Activate' }} this owner? Inactive owners cannot sign in.');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $owner->status ? 'btn-danger' : 'btn-success' }}">
                                        {{ $owner->status ? 'Deactivate' : 'Activate' }}
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
