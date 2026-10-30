@extends('layout.ext-app')
@section('title', 'Choose a new password')
@section('content')
<div class="container bs-page" style="max-width:480px; padding-bottom:60px;">
    <h1 class="site-heading">Choose a new password</h1>
    <form method="POST" action="/reset-password" class="bs-card">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="form-group">
            <label for="reset-email">Email</label>
            <input type="email" id="reset-email" name="email" class="form-control" required value="{{ old('email', $email) }}">
        </div>
        <div class="form-group">
            <label for="reset-password">New password (at least 8 characters)</label>
            <input type="password" id="reset-password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
        </div>
        <div class="form-group">
            <label for="reset-password2">Repeat the new password</label>
            <input type="password" id="reset-password2" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
        </div>
        <button type="submit" class="site-btn">Change password</button>
    </form>
</div>
@stop
@section('styles')
@include('public._styles')
@stop
