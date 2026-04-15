<!DOCTYPE html>
<!-- saved from url=(0048)https://ingenioushubs.com/Travel/demo/index.html -->
<html lang="en"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Travel Home</title>
    
    <!--================================================
                   CSS LINK PART START
    ==================================================-->
    <link rel="icon" href="{{url("images/favicon.png");}}">
    <link rel="stylesheet" href="{{url("theme/flaticon.css");}}">
    <link rel="stylesheet" href="{{url("theme/all.min.css");}}">
    <link rel="stylesheet" href="{{url("theme/animate.min.css");}}">
    <link rel="stylesheet" href="{{url("theme/dd.css");}}">
    <link rel="stylesheet" href="{{url("theme/jquery-ui.min.css");}}">
    <link href="{{url("theme/jquery.fancybox.min.css");}}" rel="stylesheet">
    <link rel="stylesheet" href="{{url("theme/slick.css");}}">
    <link rel="stylesheet" href="{{url("theme/bootstrap.min.css");}}">
    <link rel="stylesheet" href="{{url("theme/style-1.css");}}">
    <link rel="stylesheet" href="{{url("theme/responsive-1.css");}}">
    <style>
        .content-wrapper {
            margin-top: 100px;
        }
    </style>
    @yield('styles')
    <!--================================================
                   CSS LINK PART END
    ==================================================-->
</head>

<body>
     <!--=============
    Preloader Part HTML Start
    ===================-->
    <div id="preloader" style="display: none;">
        <div id="loading-center">
            <img src="{{url("theme/white.gif");}}" alt="">
        </div>
    </div>
    <!--=============
    Preloader Part HTML End
    ===================-->
     <!-- ===================
       back to top start 
     =================== -->
    <a href="/" id="back-top-btn">
       <i class="fas fa-angle-double-up"></i>
     </a>
     <!-- ===================
       back to top End 
     =================== -->
        
    <!--================================================
                      MENU PART START
    ==================================================-->
    <header>
        <nav id="nav-part" class="navbar header-nav custom_nav navbar-expand-md">
            <div class="container p-0">
                <a class="navbar-brand" href="/"><img src="{{url('assets/images/logo.jpg');}}" class="img-fluid" alt="logo" style="width:64%"></a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
                    <ul class="navbar-nav">                                                                     
                        <li class="nav-item"><a class="nav-link active" href=//index.html#banner_part">Home<span class="sr-only">(current)</span></a></li>
                        <li class="nav-item"><a class="nav-link" href=//popular.html">Popular Destination  </a></li>
                        <li class="nav-item"><a class="nav-link" href=//blog.html">Blog</a></li>
                        <li class="nav-item"><a class="nav-link" href=//tourpackage.html">Tour Packages</a></li>
                        @if(auth()->check())
                        <li class="nav-item"><a class="nav-link" href="/dashboard">Dashboard</a></li>
                        @else
                        <li class="nav-item"><a class="nav-link" href="/signup">Signup</a></li>
                        @endif
                        <li class="nav-item"><a class="nav-link" href=//index.html#contact">Contact</a></li>
                    </ul>
                    <ul class="login_menu navbar-right nav-sign">                    
						<li class="login"><a href="/signup"" class="btn-4">Sign up / login</a></li>
					</ul>

                </div>
            </div>
        </nav>
    </header>
    <!--================================================
                       MENU PART END
    ==================================================-->
@if (session('success'))
    <div class="alert alert-success" style="margin-top: 137px;text-align: center;margin-bottom: -80px;">
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger" style="margin-top: 137px;text-align: center;margin-bottom: -80px;">
        {{ session('error') }}
    </div>
@endif