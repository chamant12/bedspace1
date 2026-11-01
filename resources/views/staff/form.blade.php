@extends('layout.app')
@section('content')
<div class="col-md-7 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <h4 class="card-title">{{ $member ? 'Edit staff account' : 'New staff account' }}</h4>
        <form method="POST" action="{{ $member ? '/staff/'.$member->id : '/staff' }}">
            @csrf
            @if($member) @method('PUT') @endif
            <div class="form-group">
                <label>Full name<span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required maxlength="255" value="{{ old('name', $member?->name) }}">
            </div>
            <div class="row">
                <div class="form-group col-md-7">
                    <label>Email (sign-in name)<span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required maxlength="255" value="{{ old('email', $member?->email) }}">
                </div>
                <div class="form-group col-md-5">
                    <label>Mobile<span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" required maxlength="50" value="{{ old('phone', $member?->phone) }}">
                </div>
            </div>
            <div class="form-group">
                <label>{{ $member ? 'New password (leave empty to keep the current one)' : 'Password (at least 8 characters)' }}@unless($member)<span class="text-danger">*</span>@endunless</label>
                <input type="password" name="password" class="form-control" minlength="8" autocomplete="new-password" @unless($member) required @endunless>
            </div>
            <div class="form-group">
                <label>Properties this person works at</label>
                @forelse($properties as $property)
                    <div>
                        <label><input type="checkbox" name="property_ids[]" value="{{ $property->id }}"
                            @checked(in_array($property->id, old('property_ids', $assigned)))> {{ $property->property_name }}</label>
                    </div>
                @empty
                    <p class="text-muted">You have no properties yet.</p>
                @endforelse
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="can_edit_properties" value="1" @checked(old('can_edit_properties', $member?->can_edit_properties))>
                    <strong>Edit property details</strong> &ndash; may change property data, room types, rates, inventory, room numbers, amenities and photos
                </label>
            </div>
            <button type="submit" class="btn btn-primary">{{ $member ? 'Save' : 'Create staff account' }}</button>
            <a href="/staff" class="btn btn-light">Cancel</a>
        </form>
    </div></div>
</div>
@stop
