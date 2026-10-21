@extends('layout.ext-app')
@section('content')


  <div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper">
      <div class="content-wrapper d-flex align-items-center auth px-0">
        <div class="row w-100 mx-0">
          <div class="col-lg-5 mx-auto">
            <div class="auth-form-light text-left py-5 px-4 px-sm-5">

              <h6 class="fw-light">Admin SignIn</h6>
              <form class="pt-3" action="/admin/login" method="POST" id="loginForm">
                <div class="form-group">
                  <input type="email" class="form-control form-control-lg" id="exampleInputEmail1"
                    placeholder="email" name="email" value="{{ old('email') }}" required>
                </div>
                <div class="form-group">
                  <input type="password" class="form-control form-control-lg" id="exampleInputPassword1"
                    placeholder="Password" name="password" required>
                </div>
                <div class="mt-3 d-grid gap-2">
                  <button class="btn btn-block btn-primary btn-lg fw-medium auth-form-btn" >SIGN
                    IN</button>
                </div>
                    {{csrf_field()}}
              </form>
            </div>
          </div>
        </div>
      </div>
      <!-- content-wrapper ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->

@stop
