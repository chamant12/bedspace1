@extends('layout.app')
@section('content')
<div class="col-md-6 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <h4 class="card-title">New property owner</h4>
        <p class="card-description">Owners created here are approved immediately. Owners can also sign up themselves at /signup and are approved on the Property Owners page.</p>
        <form method="POST" action="/admin/owners">
            @csrf
            <div class="form-group">
                <label>Full name<span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required maxlength="255" value="{{ old('name') }}">
            </div>
            <div class="form-group">
                <label>Email (sign-in name)<span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" required maxlength="255" value="{{ old('email') }}">
            </div>
            <div class="form-group">
                <label>Mobile<span class="text-danger">*</span></label>
                <input type="text" name="phone" class="form-control" required maxlength="50" value="{{ old('phone') }}">
            </div>
            <div class="form-group">
                <label>Initial password (at least 8 characters)<span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary">Create owner</button>
            <a href="/admin/owners" class="btn btn-light">Cancel</a>
        </form>
    </div></div>
</div>
@stop
