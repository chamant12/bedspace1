@extends('layout.ext-app')
@section('content')
<div class="container bs-page" style="max-width:480px;">
    <h2>Guest sign in</h2>
    <form method="POST" action="/account/login" class="bs-card">
        @csrf
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required value="{{ old('email') }}">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Sign in</button>
        <p class="bs-muted small mt-3 mb-0">No account? You can create one when you book. Booked without an account? <a href="/booking/find">Find your booking</a>.</p>
    </form>
</div>
@stop
@section('styles')
@include('public._styles')
@stop
