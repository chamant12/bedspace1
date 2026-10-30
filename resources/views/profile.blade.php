@extends('layout.app')
@section('content')
<div class="col-lg-7 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <h4 class="card-title">My profile</h4>
        <p class="card-description">
            {{ $user->isAdmin() ? 'Administrator' : ($user->isStaff() ? 'Staff of '.($user->owner?->name ?? 'property owner') : 'Property owner') }}
        </p>
        <form method="POST" action="/profile">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label>Full name<span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required maxlength="255" value="{{ old('name', $user->name) }}">
            </div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Email (sign-in name)<span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required maxlength="255" value="{{ old('email', $user->email) }}">
                </div>
                <div class="form-group col-md-6">
                    <label>Mobile<span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" required maxlength="50" value="{{ old('phone', $user->phone) }}">
                </div>
            </div>

            <h5 class="mt-3">Change password</h5>
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
            <div class="form-group">
                <label>Current password <small class="text-muted">(needed to change your email or password)</small></label>
                <input type="password" name="current_password" class="form-control" autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary text-white">Save my details</button>
        </form>
    </div></div>
</div>
@stop
