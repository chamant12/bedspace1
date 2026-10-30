@extends('layout.app')
@section('content')
<div class="row">
    <div class="col-lg-6 grid-margin stretch-card">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between">
                <h4 class="card-title">Edit property owner</h4>
                <a href="/admin/owners" class="btn btn-light btn-sm">Back</a>
            </div>
            <p class="card-description">
                @if(!$owner->approved_at)
                    <span class="badge badge-warning">Awaiting approval</span>
                @elseif($owner->status)
                    <span class="badge badge-success">Active</span>
                @else
                    <span class="badge badge-secondary">Disabled</span>
                @endif
                &middot; registered {{ $owner->created_at?->format('Y-m-d') }}
            </p>
            <form method="POST" action="/admin/owners/{{ $owner->id }}">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Full name<span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required maxlength="255" value="{{ old('name', $owner->name) }}">
                </div>
                <div class="form-group">
                    <label>Email (sign-in name)<span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required maxlength="255" value="{{ old('email', $owner->email) }}">
                </div>
                <div class="form-group">
                    <label>Mobile<span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" required maxlength="50" value="{{ old('phone', $owner->phone) }}">
                </div>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label>New password</label>
                        <input type="password" name="password" class="form-control" minlength="8" autocomplete="new-password" placeholder="Leave empty to keep">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Repeat new password</label>
                        <input type="password" name="password_confirmation" class="form-control" minlength="8" autocomplete="new-password">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary text-white">Save</button>
            </form>
        </div></div>
    </div>

    <div class="col-lg-6 grid-margin stretch-card">
        <div class="card"><div class="card-body">
            <h4 class="card-title">Account status</h4>
            @if($owner->status)
                <p>Disabling this owner immediately stops them and their staff from signing in, and disables all {{ $owner->properties_count }} of their properties, so guests can no longer book them. Existing bookings are kept.</p>
                <form method="POST" action="/admin/owners/{{ $owner->id }}/toggle-status"
                      onsubmit="return confirm('Disable {{ $owner->name }} and all their properties?');">
                    @csrf
                    <button type="submit" class="btn btn-danger">Disable owner and properties</button>
                </form>
            @else
                <p>Enabling this owner lets them sign in again and re-enables the properties that were disabled with their account.</p>
                <form method="POST" action="/admin/owners/{{ $owner->id }}/toggle-status">
                    @csrf
                    <button type="submit" class="btn btn-success">Enable owner</button>
                </form>
            @endif

            <h5 class="mt-4">Properties</h5>
            <table class="table table-sm">
                <tbody>
                @forelse($properties as $property)
                    <tr>
                        <td><a href="/properties/{{ $property->id }}/details">{{ $property->property_name }}</a></td>
                        <td>
                            @if($property->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-secondary">Disabled</span>
                                <small class="text-muted">{{ ['owner' => 'by owner', 'admin' => 'by admin', 'owner_account_disabled' => 'with the account'][$property->deactivated_reason] ?? '' }}</small>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td>No properties yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div></div>
    </div>
</div>
@stop
