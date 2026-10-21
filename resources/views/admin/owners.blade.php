@extends('layout.app')
@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Property Owners</h4>
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
                                @if($owner->status)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
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
