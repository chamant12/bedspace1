@extends('layout.ext-app')
@section('title', 'Forgot password')
@section('content')
<div class="container bs-page" style="max-width:480px; padding-bottom:60px;">
    <h1 class="site-heading">Forgot your password?</h1>
    <p class="text-muted">Enter the email address of your account and we will send you a link to choose a new password.</p>
    <form method="POST" action="/forgot-password" class="bs-card">
        @csrf
        <div class="form-group">
            <label for="reset-email">Email</label>
            <input type="email" id="reset-email" name="email" class="form-control" required value="{{ old('email') }}">
        </div>
        <button type="submit" class="site-btn">Send reset link</button>
    </form>
</div>
@stop
@section('styles')
@include('public._styles')
@stop
