<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bedspace</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="{{url("assets/vendors/feather/feather.css")}}">
    <link rel="stylesheet" href="{{url("assets/vendors/mdi/css/materialdesignicons.min.css")}}">
    <link rel="stylesheet" href="{{url("assets/vendors/ti-icons/css/themify-icons.css")}}">
    <link rel="stylesheet" href="{{url("assets/vendors/font-awesome/css/font-awesome.min.css")}}">
    <link rel="stylesheet" href="{{url("assets/vendors/typicons/typicons.css")}}">
    <link rel="stylesheet" href="{{url("assets/vendors/simple-line-icons/css/simple-line-icons.css")}}">
    <link rel="stylesheet" href="{{url("assets/vendors/css/vendor.bundle.base.css")}}">
    <link rel="stylesheet" href="{{url("assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.css")}}">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="{{url("assets/vendors/datatables.net-bs4/dataTables.bootstrap4.css")}}">
    <link rel="stylesheet" type="text/css" href="{{url("assets/js/select.dataTables.min.css")}}">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="{{url("assets/css/style.css")}}">
    <!-- endinject -->
    <link rel="shortcut icon" href="{{url("assets/images/.png")}}" />
    @yield('styles')
  </head>
  <body class="with-welcome-text">
    <div class="container-scroller">

      <!-- partial:partials/_navbar.html -->
      <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">
        <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
          <div class="me-3">
            <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize">
              <span class="icon-menu"></span>
            </button>
          </div>
          <div>
            <a class="navbar-brand brand-logo" href="/">
              <img src="{{url("assets/images/logo.png")}}" alt="logo" width="100%"/>
            </a>
            <a class="navbar-brand brand-logo-mini" href="index.html">
              <img src="{{url("assets/images/logo-mini.svg")}}" alt="logo" />
            </a>
          </div>
        </div>
        <div class="navbar-menu-wrapper d-flex align-items-top">
          <ul class="navbar-nav">
            <li class="nav-item fw-semibold d-none d-lg-block ms-0">
              <h1 class="welcome-text">Good Morning, <span class="text-black fw-bold">{{auth()->user()->name}}</span></h1>
              <h3 class="welcome-sub-text">Your performance summary this week </h3>
            </li>
          </ul>
          <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown d-none d-lg-block">
              <a class="nav-link dropdown-bordered dropdown-toggle dropdown-toggle-split" id="messageDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false"> Select Category </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list pb-0" aria-labelledby="messageDropdown">
                <a class="dropdown-item py-3">
                  <p class="mb-0 fw-medium float-start">Select category</p>
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item">
                  <div class="preview-item-content flex-grow py-2">
                    <p class="preview-subject ellipsis fw-medium text-dark">Bootstrap Bundle </p>
                    <p class="fw-light small-text mb-0">This is a Bundle featuring 16 unique dashboards</p>
                  </div>
                </a>
                <a class="dropdown-item preview-item">
                  <div class="preview-item-content flex-grow py-2">
                    <p class="preview-subject ellipsis fw-medium text-dark">Angular Bundle</p>
                    <p class="fw-light small-text mb-0">Everything you’ll ever need for your Angular projects</p>
                  </div>
                </a>
                <a class="dropdown-item preview-item">
                  <div class="preview-item-content flex-grow py-2">
                    <p class="preview-subject ellipsis fw-medium text-dark">VUE Bundle</p>
                    <p class="fw-light small-text mb-0">Bundle of 6 Premium Vue Admin Dashboard</p>
                  </div>
                </a>
                <a class="dropdown-item preview-item">
                  <div class="preview-item-content flex-grow py-2">
                    <p class="preview-subject ellipsis fw-medium text-dark">React Bundle</p>
                    <p class="fw-light small-text mb-0">Bundle of 8 Premium React Admin Dashboard</p>
                  </div>
                </a>
              </div>
            </li>
            <li class="nav-item d-none d-lg-block">
              <div id="datepicker-popup" class="input-group date datepicker navbar-date-picker">
                <span class="input-group-addon input-group-prepend border-right">
                  <span class="icon-calendar input-group-text calendar-icon"></span>
                </span>
                <input type="text" class="form-control">
              </div>
            </li>
            <li class="nav-item">
              <form class="search-form" action="#">
                <i class="icon-search"></i>
                <input type="search" class="form-control" placeholder="Search Here" title="Search here">
              </form>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link count-indicator" id="notificationDropdown" href="#" data-bs-toggle="dropdown">
                <i class="icon-bell"></i>
                <span class="count"></span>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list pb-0" aria-labelledby="notificationDropdown">
                <a class="dropdown-item py-3 border-bottom">
                  <p class="mb-0 fw-medium float-start">You have 4 new notifications </p>
                  <span class="badge badge-pill badge-primary float-end">View all</span>
                </a>
                <a class="dropdown-item preview-item py-3">
                  <div class="preview-thumbnail">
                    <i class="mdi mdi-alert m-auto text-primary"></i>
                  </div>
                  <div class="preview-item-content">
                    <h6 class="preview-subject fw-normal text-dark mb-1">Application Error</h6>
                    <p class="fw-light small-text mb-0"> Just now </p>
                  </div>
                </a>
                <a class="dropdown-item preview-item py-3">
                  <div class="preview-thumbnail">
                    <i class="mdi mdi-lock-outline m-auto text-primary"></i>
                  </div>
                  <div class="preview-item-content">
                    <h6 class="preview-subject fw-normal text-dark mb-1">Settings</h6>
                    <p class="fw-light small-text mb-0"> Private message </p>
                  </div>
                </a>
                <a class="dropdown-item preview-item py-3">
                  <div class="preview-thumbnail">
                    <i class="mdi mdi-airballoon m-auto text-primary"></i>
                  </div>
                  <div class="preview-item-content">
                    <h6 class="preview-subject fw-normal text-dark mb-1">New user registration</h6>
                    <p class="fw-light small-text mb-0"> 2 days ago </p>
                  </div>
                </a>
              </div>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link count-indicator" id="countDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="icon-mail icon-lg"></i>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list pb-0" aria-labelledby="countDropdown">
                <a class="dropdown-item py-3">
                  <p class="mb-0 fw-medium float-start">You have 7 unread mails </p>
                  <span class="badge badge-pill badge-primary float-end">View all</span>
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <img src="{{url("assets/images/faces/face10.jpg")}}" alt="image" class="img-sm profile-pic">
                  </div>
                  <div class="preview-item-content flex-grow py-2">
                    <p class="preview-subject ellipsis fw-medium text-dark">Marian Garner </p>
                    <p class="fw-light small-text mb-0"> The meeting is cancelled </p>
                  </div>
                </a>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <img src="{{url("assets/images/faces/face12.jpg")}}" alt="image" class="img-sm profile-pic">
                  </div>
                  <div class="preview-item-content flex-grow py-2">
                    <p class="preview-subject ellipsis fw-medium text-dark">David Grey </p>
                    <p class="fw-light small-text mb-0"> The meeting is cancelled </p>
                  </div>
                </a>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <img src="{{url("assets/images/faces/face1.jpg")}}" alt="image" class="img-sm profile-pic">
                  </div>
                  <div class="preview-item-content flex-grow py-2">
                    <p class="preview-subject ellipsis fw-medium text-dark">Travis Jenkins </p>
                    <p class="fw-light small-text mb-0"> The meeting is cancelled </p>
                  </div>
                </a>
              </div>
            </li>
            <li class="nav-item dropdown d-none d-lg-block user-dropdown">
              <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                <img class="img-xs rounded-circle" src="{{url("assets/images/faces/face8.jpg")}}" alt="Profile image"> </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                <div class="dropdown-header text-center">
                  <img class="img-md rounded-circle" src="{{url("assets/images/faces/face8.jpg")}}" alt="Profile image">
                  <p class="mb-1 mt-3 fw-semibold">{{auth()->user()->name}}</p>
                  <p class="fw-light text-muted mb-0">{{auth()->user()->email}}</p>
                </div>
                <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-account-outline text-primary me-2"></i> My Profile <span class="badge badge-pill badge-danger">1</span></a>
                <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-message-text-outline text-primary me-2"></i> Messages</a>
                <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-calendar-check-outline text-primary me-2"></i> Activity</a>
                <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-help-circle-outline text-primary me-2"></i> FAQ</a>
                <form method="POST" action="/logout">
                  @csrf
                  <button type="submit" class="dropdown-item"><i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>Sign Out</button>
                </form>
              </div>
            </li>
          </ul>
          <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-bs-toggle="offcanvas">
            <span class="mdi mdi-menu"></span>
          </button>
        </div>
      </nav>
            <!-- partial -->
      <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_sidebar.html -->
        <nav class="sidebar sidebar-offcanvas" id="sidebar">
          <ul class="nav">
            <li class="nav-item">
              <a class="nav-link" href="{{ auth()->user()->dashboardUrl() }}">
                <i class="mdi mdi-grid-large menu-icon"></i>
                <span class="menu-title">Dashboard</span>
              </a>
            </li>
            @if(auth()->user()->isOwner() || auth()->user()->isAdmin())
            <li class="nav-item">
              <a class="nav-link" href="/reservations">
                <i class="mdi mdi-calendar-check menu-icon"></i>
                <span class="menu-title">Reservations</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="{{ auth()->user()->isAdmin() ? '/admin/reports/income' : '/reports/income' }}">
                <i class="mdi mdi-chart-line menu-icon"></i>
                <span class="menu-title">Income report</span>
              </a>
            </li>
            @endif
            @if(auth()->user()->isOwner())
            <li class="nav-item nav-category">Properties</li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="collapse" href="#menu-properties" aria-expanded="false" aria-controls="menu-properties">
                <i class="menu-icon mdi mdi-floor-plan"></i>
                <span class="menu-title">Properties</span>
                <i class="menu-arrow"></i>
              </a>
              <div class="collapse" id="menu-properties">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item"> <a class="nav-link" href="/my-properties">My Properties</a></li>
                  <li class="nav-item"> <a class="nav-link" href="/add-property">Add Property</a></li>
                  <li class="nav-item"> <a class="nav-link" href="/add-room-type">Add Room Type</a></li>
                  <li class="nav-item"> <a class="nav-link" href="/exchange-rates">Exchange Rates</a></li>
                  <li class="nav-item"> <a class="nav-link" href="/amenities">Amenities</a></li>
                  <li class="nav-item"> <a class="nav-link" href="/photos">Photos</a></li>
                </ul>
              </div>
            </li>
            @endif
            @if(auth()->user()->isAdmin())
            <li class="nav-item nav-category">Platform</li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="collapse" href="#menu-admin" aria-expanded="false" aria-controls="menu-admin">
                <i class="menu-icon mdi mdi-floor-plan"></i>
                <span class="menu-title">Manage</span>
                <i class="menu-arrow"></i>
              </a>
              <div class="collapse" id="menu-admin">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item"> <a class="nav-link" href="/admin/properties">Properties</a></li>
                  <li class="nav-item"> <a class="nav-link" href="/admin/owners">Property Owners</a></li>
                  <li class="nav-item"> <a class="nav-link" href="/admin/amenities">Amenities</a></li>
                  <li class="nav-item"> <a class="nav-link" href="/admin/commission-dues">Commission dues</a></li>
                  <li class="nav-item"> <a class="nav-link" href="/admin/payouts">Hotel payouts</a></li>
                </ul>
              </div>
            </li>
            @endif
          </ul>
        </nav>
        <!-- partial -->
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="row">
              <div class="col-sm-12">
                <div class="home-tab">
                  <div class="tab-content tab-content-basic">
                    <div class="tab-pane fade show active" id="overview" role="tabpanel">
@if (session('success'))
    <div class="alert alert-success show-message" >
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger show-message">
        {{ session('error') }}
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger show-message">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif
