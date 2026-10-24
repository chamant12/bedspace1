@extends('layout.app')
@section('content')
<div class="col-md-8 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <h4 class="card-title">Welcome to Bedspace</h4>
        @if(auth()->user()->isStaff())
            <p>Your employer's account is not active at the moment. Please contact the property owner.</p>
        @else
            <p>Thank you for signing up. Your account is <strong>awaiting approval</strong> by the Bedspace team.</p>
            <p>Once it is approved you will receive an email and can add your properties, room types, rates and staff accounts.</p>
        @endif
    </div></div>
</div>
@stop
