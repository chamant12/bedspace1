
@extends('layout.ext-app')
@section('content')
<div>
<div class="col-md-6 grid-margin stretch-card" style="margin-left:25%;margin-top:10%;margin-bottom:5%;">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Partner SignUp</h4>

                    <form class="forms-sample" method="POST" action="/create-property-owner">
                      <div class="form-group">
                        <label for="exampleInputEmail1">Name</label>
                        <input type="text" class="form-control" placeholder="Full name" name="full_name">
                      </div>
                      <div class="form-group">
                        <label for="exampleInputEmail1">Email address</label>
                        <input type="email" class="form-control" id="exampleInputEmail1" placeholder="Email" name="email">
                      </div>
                      <div class="form-group">
                        <label for="phone">Phone number</label>
                        <input type="phone" class="form-control" id="phone" placeholder="Phone" name="phone ">
                      </div>
                      <div class="form-group">
                        <label for="exampleInputPassword1">Password</label>
                        <input type="password" class="form-control" id="exampleInputPassword1" placeholder="Password" name="password">
                      </div>
                      <div class="form-group">
                        <label for="exampleInputConfirmPassword1">Confirm Password</label>
                        <input type="password" class="form-control" id="exampleInputConfirmPassword1" placeholder="Password">
                      </div>
                      <div class="form-check form-check-flat form-check-primary">
                        <label class="form-check-label">
                          Already registered? <a href="/login">Click here to login</a></label>
                      </div>
                      {{csrf_field()}}
                      <button type="submit" class="btn btn-primary me-2">Signup</button>
                      <button class="btn btn-light">Cancel</button>
                    </form>
                  </div>
                </div>
              </div>
</div>
@stop
@section('styles')
<style>
  .form-group {
    padding: 10px;
}
button.btn {
    float: right;
}
</style>
@stop