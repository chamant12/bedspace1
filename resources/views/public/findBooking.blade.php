@extends('layout.ext-app')
@section('content')
<div class="container bs-page" style="max-width:560px;">
    <h2>Find my booking</h2>
    <form method="POST" action="/booking/find" class="bs-card">
        @csrf
        <div class="form-group">
            <label>Booking reference</label>
            <input type="text" name="reference" class="form-control" required maxlength="20" value="{{ old('reference') }}" placeholder="e.g. BS261105A7KQ">
        </div>
        <div class="form-group">
            <label>Email used for the booking</label>
            <input type="email" name="email" class="form-control" required value="{{ old('email') }}">
        </div>
        <button type="submit" class="btn btn-primary">Find booking</button>
    </form>
</div>
@stop
@section('styles')
@include('public._styles')
@stop
