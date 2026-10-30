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
						<li class="login"><a href="/signup" class="btn-4">Sign up / login</a></li>
					</ul>

                </div>
            </div>
        </nav>
    </header>
    <!--================================================
                       MENU PART END
    ==================================================-->
    
    
    
    <!--================================================
                       BANNER PART START
    ==================================================-->
    <section id="banner_part" class="banner-1">
        <div class="container">
            <div class="row">               
                <div class="col-lg-5 banner-center">
                    <div class="banner-text-t">
                       
                        <h2>Discover</h2>
                        <h5>The world together</h5>
                   
                    </div>
                </div>
            </div>
        </div>
    </section>
   <section id="reservation-form" class="reservation">
                              <div class="container">
                                 <div class="row">
                                  <div class="col-md-12">  

                                   <!--********************* Hotel search (booking engine) ********************-->
                                   <div class="reservation-tabs">
                                           <ul class="nav nav-tabs search-opt">
                                                    <li class="active"><a href="#hotels-tab" data-toggle="tab"><i class="flaticon-hotel"></i>hotels</a></li>
                                                </ul>
                                    </div>
                                    <div class="tab-content">
                                    <form id="hotels-tab" class="tab-pane form-inline reservation-hotel active" method="GET" action="/search" name="reservationform">
                                      <div class="row">
                                        <div class="col-md-3 step-where steo-f">
                                          <div class="form-group">
                                            <div class="content-checkin-data">
                                                 <i class="flaticon-map"></i>
                                                    <select name="district_id" class="form-control" aria-label="Where">
                                                        <option value="" selected="selected">Anywhere in Sri Lanka</option>
                                                        @foreach($districts as $district)
                                                            <option value="{{ $district->id }}">{{ $district->district_name }}</option>
                                                        @endforeach
                                                      </select>
                                                </div>
                                          </div>
                                        </div>
                                        <div class="col-md-2 departure-date step-where">
                                              <div class="form-group">
<i class="flaticon-appointment"></i>
                                                <input type="text" id="bs-check-in" name="check_in" class="field-input calendar-input" placeholder="Check-in" value="{{ $stay['check_in'] }}" autocomplete="off" readonly required aria-label="Check-in date">
                                              </div>
                                        </div>
                                        <div class="col-md-2 departure-date step-where">
                                              <div class="form-group">
<i class="flaticon-appointment"></i>
                                                <input type="text" id="bs-check-out" name="check_out" class="field-input calendar-input" placeholder="Check-out" value="{{ $stay['check_out'] }}" autocomplete="off" readonly required aria-label="Check-out date">
                                              </div>
                                        </div>
                                        <div class="col-md-3 travel-where">
                                          <div class="form-group">
                                            <div class="content-checkin-data">
                                                 <i class="flaticon-passenger"></i>
                                                    <select name="guests" class="form-control" aria-label="Guests per room">
                                                        <option value="" selected="selected">Guests per room</option>
                                                        @foreach($occupancies as $occupancy)
                                                            <option value="{{ $occupancy->occupancy }}">{{ $occupancy->occupancy }} ({{ $occupancy->occupancy_name }})</option>
                                                        @endforeach
                                                      </select>
                                                </div>
                                          </div>
                                        </div>
                                        <div class="col-md-2 travel-where">
                                          <div class="form-group">
                                            <div class="content-checkin-data">
                                                 <i class="flaticon-hotel"></i>
                                                    <select name="rooms" class="form-control" aria-label="Rooms">
                                                        @for($i = 1; $i <= \App\Services\BookingQuotes::MAX_ROOMS; $i++)
                                                            <option value="{{ $i }}">{{ $i }} {{ $i === 1 ? 'room' : 'rooms' }}</option>
                                                        @endfor
                                                      </select>
                                                </div>
                                          </div>
                                        </div>
                                        <div class="col-md-12">
                                          <div class="form-group">
                                           <button type="submit" class="banner-btn">Search<i class="fas fa-long-arrow-alt-right"></i></button>
                                            </div>
                                          </div>
                                      </div>
                                    </form>

                                    
                                    </div><!--Close tab-content form-->
                                  </div>
                                </div>
                              </div>
                      </section>

    <!--================================================
                        BANNER PART END
    ==================================================-->
    
    
    
    
    <!--================================================
                     About-us PART START
    ==================================================-->
    <section id="about-us" class="about-us">
        <div class="container"> 
           <div class="row">
               <div class="col-lg-6 col-md-12">
                   <div class="row">
            <div class="col-md-4">
                    <div class="heading">
                        <h4>about us</h4>                        
                    </div>
                </div>
            </div> 
            <div class="row">
            <div class="col-md-10">
                    <div class="sub-header">
                        <h3>Explore more about our
travel agency</h3>                     
                    </div>
                </div>
            </div>         
            <div class="row">
                                    
                    <div class="col-md-12">
                    <div class="about-para">
                        <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiuod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu.</p>

<p>fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam.</p>                     
                    </div>
                    <div class="about-btn travel-btn">                    
						<a href=//about-us.html" class="btn-4">Explore More</a>
					</div>
                </div>
                
            </div>
               </div>
               <div class="col-lg-3 col-md-6">
                    <div class="about-f-img">
                        <img src="{{url("theme/about-image-1.jpg");}}" );}} class="img-fluid" alt="about">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="about-s-img">
                        <img src="{{url("theme/about-image-2.jpg");}}" );}} class="img-fluid" alt="about">
                    </div>
                </div>
           </div> 
            
        </div>
    </section>
    <!--================================================
                     About PART END
    ==================================================-->
    
    
    <!--=====================
    Places Work Part HTML Start
    =======================-->

    <section id="places" class="places section">
        <div class="container-fluid">
            <div class="row justify-content-center text-center">
               <div class="col-md-12">
                   <div class="row justify-content-center text-center">
            <div class="col-lg-2 col-md-4">
                    <div class="heading">
                        <h4>popular destination</h4>                        
                    </div>
                </div>
            </div> 
            <div class="row justify-content-center text-center">
            <div class="col-md-6">
                    <div class="sub-header">
                        <h3>The places where most of the visitor come</h3>                     
                    </div>
                </div>
            </div>         
               </div>
               
            </div>
            <div class="row justify-content-center text-center">
                <div class="col-lg-12 m-auto col-12 text-center button-group">
                    <ul class="nav nav-pills" id="pills-tab1" role="tablist">
                        <li class="nav-item">
                            <a class="nav-menu first-shape active" id="pills-all-tab1" data-toggle="pill" href=//index.html#pills-all1" role="tab" aria-controls="pills-all1" aria-selected="true">Asia</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-menu" id="pills-logo-tab1" data-toggle="pill" href=//index.html#pills-logo1" role="tab" aria-controls="pills-all1" aria-selected="true">Africa</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-menu" id="pills-flyer-tab1" data-toggle="pill" href=//index.html#pills-flyer1" role="tab" aria-selected="false">North America</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-menu" id="pills-web-tab1" data-toggle="pill" href=//index.html#pills-web1" role="tab" aria-selected="false">South America</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-menu" id="pills-tem-tab1" data-toggle="pill" href=//index.html#pills-tem1" role="tab" aria-selected="false">Antarctica</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-menu last-shape" id="pills-eur-tab1" data-toggle="pill" href=//index.html#pills-eur1" role="tab" aria-selected="false">Europe</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-menu last-shape" id="pills-aus-tab1" data-toggle="pill" href=//index.html#pills-aus1" role="tab" aria-selected="false">Australia</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="row mx-0">
                <div class="col-lg-12 px-0">
                    <div class="tab-content" id="pills-tabContent1">
                        <div class="tab-pane fade show active" id="pills-all1" role="tabpanel" aria-labelledby="pills-all-tab1">
                            <div class="row work-cont-1">
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-1.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">
                                                   
                                                    <a href=//index.html#" class="over-btn">10 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-2.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">12 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-3.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">17 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-4.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">20 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                
                            </div>

                        </div>

                        <div class="tab-pane fade" id="pills-logo1" role="tabpanel" aria-labelledby="pills-logo-tab1">
                            <div class="row work-cont-1">
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-5.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">20 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-6.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">20 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-7.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">11 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-8.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">10 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pills-flyer1" role="tabpanel" aria-labelledby="pills-flyer-tab1">
                            <div class="row work-cont-1">
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-9.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">10 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-10.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">12 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-11.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">20 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-12.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">20 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pills-web1" role="tabpanel" aria-labelledby="pills-web-tab1">
                            <div class="row work-cont-1">
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-1.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">20 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-3.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">16 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-2.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">14 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-5.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">20 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pills-tem1" role="tabpanel" aria-labelledby="pills-tem-tab1">
                           <div class="row work-cont-1">
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-4.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">17 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-6.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">12 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-8.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">22 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-1.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">20 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pills-eur1" role="tabpanel" aria-labelledby="pills-eur-tab1">
                            <div class="row work-cont-1">
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-10.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">20 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-12.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">20 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-1.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">20 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-3.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">20 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pills-aus1" role="tabpanel" aria-labelledby="pills-aus-tab1">
                            <div class="row work-cont-1">
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-5.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">10 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-7.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">20 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-9.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">20 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 place-col">
                                    <div class="work-image">
                                        <img src="{{url("theme/popular-destination-11.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        
                                            <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn">20 photos</a>
                                                    <h2>St Mark's Campanile</h2>
                                                    <a href=//popular.html" class="view-all">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                       
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 text-center place-border">
                    <div class="about-btn travel-btn">                    
						<a href=//popular.html" class="btn-4">View All places</a>
					</div>
                </div>
            </div>

        </div>
    </section>

    <!--==================
    Places Work Part HTML End
    ====================-->
    <!--=====================
    Tour Packages Part HTML Start
    =======================-->
    <section id="tour-package" class="tour section">
        <div class="container">
            <div class="row justify-content-center text-center">
               <div class="col-md-12">
                   <div class="row justify-content-center text-center">
            <div class="col-lg-2 col-md-4">
                    <div class="heading">
                        <h4>tour Packages</h4>                        
                    </div>
                </div>
            </div> 
            <div class="row justify-content-center text-center">
            <div class="col-lg-5 col-md-7">
                    <div class="sub-header">
                        <h3>The reasonable prices for the beautiful places</h3>                     
                    </div>
                </div>
            </div>         
               </div>
               
            </div>
            <div class="row tour-cont slick-initialized slick-slider"><div class="slick-list draggable"><div class="slick-track" style="opacity: 1; width: 897px; transform: translate3d(0px, 0px, 0px);"><div class="slick-slide slick-current slick-active" data-slick-index="0" aria-hidden="false" style="width: 299px;"><div><div class="col-md-12" style="width: 100%; display: inline-block;">
                                   <div class="travel-place">
                                    <div class="work-image">
                                        <img src="{{url("theme/tour-package-1.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn" tabindex="0">20 photos</a>                                                    
                                                    <a href=//tourpackage.html" class="view-all" tabindex="0">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                    </div>
                                    <div class="travel-text">
                                        <h3>Romance, Arkansas, USA</h3>
                                        <p>for 7Days   <span>|</span>   $ 780.00</p>
                                    </div></div>
                                </div></div></div><div class="slick-slide slick-active" data-slick-index="1" aria-hidden="false" style="width: 299px;"><div><div class="col-md-12" style="width: 100%; display: inline-block;">
                                   <div class="travel-place">
                                    <div class="work-image">
                                        <img src="{{url("theme/tour-package-2.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn" tabindex="0">14 photos</a>                                                    
                                                    <a href=//tourpackage.html" class="view-all" tabindex="0">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                    </div>
                                    <div class="travel-text">
                                        <h3>Rockies, Colorado</h3>
                                        <p>for 3Days   <span>|</span>   $ 380.00</p>
                                    </div></div>
                                </div></div></div><div class="slick-slide slick-active" data-slick-index="2" aria-hidden="false" style="width: 299px;"><div><div class="col-md-12" style="width: 100%; display: inline-block;">
                                    <div class="travel-place">
                                    <div class="work-image">
                                        <img src="{{url("theme/tour-package-3.jpg");}}" );}} class="img-fluid person" alt="destination">
                                        <div class="overlay">
                                                <div class="overlay_shape">                                                   
                                                    <a href=//index.html#" class="over-btn" tabindex="0">18 photos</a>                                                    
                                                    <a href=//tourpackage.html" class="view-all" tabindex="0">View All Places<i class="fas fa-long-arrow-alt-right"></i></a>
                                                </div>
                                            </div>
                                    </div>
                                    <div class="travel-text">
                                        <h3>Lover, Wiltshire, England</h3>
                                        <p>for 7Days   <span>|</span>   $ 680.00</p>
                                    </div></div>
                                </div></div></div></div></div></div>
                                       <div class="row">
                                       <div class="col-md-12 text-center">
                    <div class="about-btn travel-btn">                    
						<a href=//tourpackage.html" class="btn-4">View All Packages</a>
					</div>
                </div>                       
                            </div>
        </div>
    </section>
    <!--=====================
    Tour Packages Part HTML End
    =======================-->
       <!--=====================
    Testimonial Part HTML Start
    =======================-->
       <section id="testimonial" class="testimonial section">
        <div class="container">
            <div class="row justify-content-center text-center">
               <div class="col-md-12">
                   <div class="row justify-content-center text-center">
            <div class="col-lg-2 col-md-4">
                    <div class="heading">
                        <h4>testimonial</h4>                        
                    </div>
                </div>
            </div> 
            <div class="row justify-content-center text-center">
            <div class="col-lg-5 col-md-7">
                    <div class="sub-header w-color">
                        <h3>Positive feedbacks from our clients &amp; blogers</h3>                     
                    </div>
                </div>
            </div>         
               </div>
               
            </div>
            <div class="row testimonial-cont slick-initialized slick-slider"><div class="slick-list draggable"><div class="slick-track" style="opacity: 1; width: 4560px; transform: translate3d(-2280px, 0px, 0px);"><div class="slick-slide slick-cloned" data-slick-index="-2" aria-hidden="true" tabindex="-1" style="width: 570px;"><div><div class="col-md-12" style="width: 100%; display: inline-block;">
                                  <div class="test-bg">
                                   <div class="row">
                                       <div class="col-lg-3 col-md-4">
                                       <img src="{{url("theme/testimonial-2.png");}}" class="img-fluid person" alt="testimonial">
                                       </div>
                                       <div class="col-lg-9 col-md-8">
                                       <div class="test-text">
                                           <h4>Eliza Miranda</h4>
                                           <h5>Travel Blogger</h5>
                                           <ul>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                           </ul>
                                       </div>
                                       </div>
                                   </div>
                                   <div class="row">
                                   <div class="col-md-12">
                                      <h6>Lorem ipsum dolor sit amet, consectetur adiiing elit, sed do eiusmod tempor incididunt ut abore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud.</h6>
                                       </div>
                                   </div>
                                   </div>
                               </div></div></div><div class="slick-slide slick-cloned" data-slick-index="-1" aria-hidden="true" tabindex="-1" style="width: 570px;"><div><div class="col-md-12" style="width: 100%; display: inline-block;">
                                  <div class="test-bg">
                                   <div class="row">
                                       <div class="col-lg-3 col-md-4">
                                       <img src="{{url("theme/testimonial-1.png");}}" class="img-fluid person" alt="testimonial">
                                       </div>
                                       <div class="col-lg-9 col-md-8">
                                       <div class="test-text">
                                           <h4>Helen Willa</h4>
                                           <h5>Travel Blogger</h5>
                                           <ul>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                           </ul>
                                       </div>
                                       </div>
                                   </div>
                                   <div class="row">
                                   <div class="col-md-12">
                                      <h6>Lorem ipsum dolor sit amet, consectetur adiiing elit, sed do eiusmod tempor incididunt ut abore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud.</h6>
                                       </div>
                                   </div>
                                   </div>
                               </div></div></div><div class="slick-slide" data-slick-index="0" aria-hidden="true" tabindex="-1" style="width: 570px;"><div><div class="col-md-12" style="width: 100%; display: inline-block;">
                                  <div class="test-bg">
                                   <div class="row">
                                       <div class="col-lg-3 col-md-4">
                                       <img src="{{url("theme/testimonial-1.png");}}" class="img-fluid person" alt="testimonial">
                                       </div>
                                       <div class="col-lg-9 col-md-8">
                                       <div class="test-text">
                                           <h4>Maria D’costa</h4>
                                           <h5>Travel Blogger</h5>
                                           <ul>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                           </ul>
                                       </div>
                                       </div>
                                   </div>
                                   <div class="row">
                                   <div class="col-md-12">
                                      <h6>Lorem ipsum dolor sit amet, consectetur adiiing elit, sed do eiusmod tempor incididunt ut abore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud.</h6>
                                       </div>
                                   </div>
                                   </div>
                               </div></div></div><div class="slick-slide" data-slick-index="1" aria-hidden="true" tabindex="-1" style="width: 570px;"><div><div class="col-md-12" style="width: 100%; display: inline-block;">
                                  <div class="test-bg">
                                   <div class="row">
                                       <div class="col-lg-3 col-md-4">
                                       <img src="{{url("theme/testimonial-2.png");}}" class="img-fluid person" alt="testimonial">
                                       </div>
                                       <div class="col-lg-9 col-md-8">
                                       <div class="test-text">
                                           <h4>Eliza Miranda</h4>
                                           <h5>Travel Blogger</h5>
                                           <ul>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                           </ul>
                                       </div>
                                       </div>
                                   </div>
                                   <div class="row">
                                   <div class="col-md-12">
                                      <h6>Lorem ipsum dolor sit amet, consectetur adiiing elit, sed do eiusmod tempor incididunt ut abore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud.</h6>
                                       </div>
                                   </div>
                                   </div>
                               </div></div></div><div class="slick-slide slick-current slick-active" data-slick-index="2" aria-hidden="false" style="width: 570px;"><div><div class="col-md-12" style="width: 100%; display: inline-block;">
                                  <div class="test-bg">
                                   <div class="row">
                                       <div class="col-lg-3 col-md-4">
                                       <img src="{{url("theme/testimonial-1.png");}}" class="img-fluid person" alt="testimonial">
                                       </div>
                                       <div class="col-lg-9 col-md-8">
                                       <div class="test-text">
                                           <h4>Helen Willa</h4>
                                           <h5>Travel Blogger</h5>
                                           <ul>
                                               <li><a tabindex="0"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="0"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="0"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="0"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="0"><i class="fas fa-star"></i></a></li>
                                           </ul>
                                       </div>
                                       </div>
                                   </div>
                                   <div class="row">
                                   <div class="col-md-12">
                                      <h6>Lorem ipsum dolor sit amet, consectetur adiiing elit, sed do eiusmod tempor incididunt ut abore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud.</h6>
                                       </div>
                                   </div>
                                   </div>
                               </div></div></div><div class="slick-slide slick-cloned slick-active" data-slick-index="3" aria-hidden="false" tabindex="-1" style="width: 570px;"><div><div class="col-md-12" style="width: 100%; display: inline-block;">
                                  <div class="test-bg">
                                   <div class="row">
                                       <div class="col-lg-3 col-md-4">
                                       <img src="{{url("theme/testimonial-1.png");}}" class="img-fluid person" alt="testimonial">
                                       </div>
                                       <div class="col-lg-9 col-md-8">
                                       <div class="test-text">
                                           <h4>Maria D’costa</h4>
                                           <h5>Travel Blogger</h5>
                                           <ul>
                                               <li><a tabindex="0"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="0"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="0"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="0"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="0"><i class="fas fa-star"></i></a></li>
                                           </ul>
                                       </div>
                                       </div>
                                   </div>
                                   <div class="row">
                                   <div class="col-md-12">
                                      <h6>Lorem ipsum dolor sit amet, consectetur adiiing elit, sed do eiusmod tempor incididunt ut abore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud.</h6>
                                       </div>
                                   </div>
                                   </div>
                               </div></div></div><div class="slick-slide slick-cloned" data-slick-index="4" aria-hidden="true" tabindex="-1" style="width: 570px;"><div><div class="col-md-12" style="width: 100%; display: inline-block;">
                                  <div class="test-bg">
                                   <div class="row">
                                       <div class="col-lg-3 col-md-4">
                                       <img src="{{url("theme/testimonial-2.png");}}" class="img-fluid person" alt="testimonial">
                                       </div>
                                       <div class="col-lg-9 col-md-8">
                                       <div class="test-text">
                                           <h4>Eliza Miranda</h4>
                                           <h5>Travel Blogger</h5>
                                           <ul>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                           </ul>
                                       </div>
                                       </div>
                                   </div>
                                   <div class="row">
                                   <div class="col-md-12">
                                      <h6>Lorem ipsum dolor sit amet, consectetur adiiing elit, sed do eiusmod tempor incididunt ut abore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud.</h6>
                                       </div>
                                   </div>
                                   </div>
                               </div></div></div><div class="slick-slide slick-cloned" data-slick-index="5" aria-hidden="true" tabindex="-1" style="width: 570px;"><div><div class="col-md-12" style="width: 100%; display: inline-block;">
                                  <div class="test-bg">
                                   <div class="row">
                                       <div class="col-lg-3 col-md-4">
                                       <img src="{{url("theme/testimonial-1.png");}}" class="img-fluid person" alt="testimonial">
                                       </div>
                                       <div class="col-lg-9 col-md-8">
                                       <div class="test-text">
                                           <h4>Helen Willa</h4>
                                           <h5>Travel Blogger</h5>
                                           <ul>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                               <li><a tabindex="-1"><i class="fas fa-star"></i></a></li>
                                           </ul>
                                       </div>
                                       </div>
                                   </div>
                                   <div class="row">
                                   <div class="col-md-12">
                                      <h6>Lorem ipsum dolor sit amet, consectetur adiiing elit, sed do eiusmod tempor incididunt ut abore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud.</h6>
                                       </div>
                                   </div>
                                   </div>
                               </div></div></div></div></div></div>
        </div>
    </section>
       <!--=====================
    Testimonial Part HTML End
    =======================-->
    
    <!-- =============================
        Start: News Letter
    ============================= -->
	<!-- =============================
        End: News Letter
    ============================= -->

	<!-- =============================
        Start: Our Articles
    ============================= -->

	<section id="artical" class="artical section">
		<div class="container">
			<div class="row justify-content-center text-center">
               <div class="col-md-12">
                   <div class="row justify-content-center text-center">
            <div class="col-lg-2 col-md-4">
                    <div class="heading">
                        <h4>our blog</h4>                        
                    </div>
                </div>
            </div> 
            <div class="row justify-content-center text-center">
            <div class="col-lg-5 col-md-7">
                    <div class="sub-header">
                        <h3>The most popular travel blogs can get here</h3>                     
                    </div>
                </div>
            </div>         
               </div>
               
            </div>
			<div class="row artical-slick slick-initialized slick-slider"><i class="fa fa-angle-right articalNext slick-arrow" style=""></i><div class="slick-list draggable"><div class="slick-track" style="opacity: 1; width: 4560px; transform: translate3d(-1140px, 0px, 0px);"><div class="slick-slide slick-cloned" data-slick-index="-2" aria-hidden="true" tabindex="-1" style="width: 570px;"><div><div class="col-md-12" style="width: 100%; display: inline-block;">
					<div class="artical-inner">
						<div class="row">
							<div class="col-md-6 artical-img">
								<div class="">
									<img src="{{url("theme/artical-image-2.jpg");}}" );}} class="img-fluid" alt="img">
								</div>
							</div>
							<div class="col-md-6 border-artical">
								<div class="artical-content">
									<h6>26 Feb 2020</h6>
									<h2>Mountain beautiful Houses which like you most</h2>
									<a href=//blog-details.html" tabindex="-1">Read more</a>
									<ul>
                                        <li><i class="fa fa-thumbs-up"></i><span>1.7k</span></li>
                                        <li><i class="fa fa-comments"></i><span>1k</span></li>
                                        
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div></div></div><div class="slick-slide slick-cloned" data-slick-index="-1" aria-hidden="true" tabindex="-1" style="width: 570px;"><div><div class="col-md-12" style="width: 100%; display: inline-block;">
					<div class="artical-inner">
						<div class="row">
							<div class="col-md-6 artical-img">
								<div class="">
									<img src="{{url("theme/artical-image-1.jpg");}}" );}} class="img-fluid" alt="img">
								</div>
							</div>
							<div class="col-md-6 border-artical">
								<div class="artical-content">
									<h6>28 Jan 2020</h6>
									<h2>World’s Best Places which you ever seen are here</h2>
									<a href=//blog-details.html" tabindex="-1">Read more</a>
									<ul>
                                        <li><i class="fa fa-thumbs-up"></i><span>1.7k</span></li>
                                        <li><i class="fa fa-comments"></i><span>1k</span></li>
                                        
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div></div></div><div class="slick-slide slick-current slick-active" data-slick-index="0" aria-hidden="false" style="width: 570px;"><div><div class="col-md-12" style="width: 100%; display: inline-block;">
					<div class="artical-inner">
						<div class="row">
							<div class="col-md-6 artical-img">
								<div class="">
									<img src="{{url("theme/artical-image-1.jpg");}}" );}} class="img-fluid" alt="img">
								</div>
							</div>
							<div class="col-md-6 border-artical">
								<div class="artical-content">
									<h6>23 Oct 2019</h6>
									<h2>World’s Best Places which you ever seen are here</h2>
									<a href=//blog-details.html" tabindex="0">Read more</a>
									<ul>
                                        <li><i class="fa fa-thumbs-up"></i><span>1.7k</span></li>
                                        <li><i class="fa fa-comments"></i><span>1k</span></li>
                                        
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div></div></div><div class="slick-slide slick-active" data-slick-index="1" aria-hidden="false" style="width: 570px;"><div><div class="col-md-12" style="width: 100%; display: inline-block;">
					<div class="artical-inner">
						<div class="row">
							<div class="col-md-6 artical-img">
								<div class="">
									<img src="{{url("theme/artical-image-2.jpg");}}" );}} class="img-fluid" alt="img">
								</div>
							</div>
							<div class="col-md-6 border-artical">
								<div class="artical-content">
									<h6>26 Feb 2020</h6>
									<h2>Mountain beautiful Houses which like you most</h2>
									<a href=//blog-details.html" tabindex="0">Read more</a>
									<ul>
                                        <li><i class="fa fa-thumbs-up"></i><span>1.7k</span></li>
                                        <li><i class="fa fa-comments"></i><span>1k</span></li>
                                        
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div></div></div><div class="slick-slide" data-slick-index="2" aria-hidden="true" tabindex="-1" style="width: 570px;"><div><div class="col-md-12" style="width: 100%; display: inline-block;">
					<div class="artical-inner">
						<div class="row">
							<div class="col-md-6 artical-img">
								<div class="">
									<img src="{{url("theme/artical-image-1.jpg");}}" );}} class="img-fluid" alt="img">
								</div>
							</div>
							<div class="col-md-6 border-artical">
								<div class="artical-content">
									<h6>28 Jan 2020</h6>
									<h2>World’s Best Places which you ever seen are here</h2>
									<a href=//blog-details.html" tabindex="-1">Read more</a>
									<ul>
                                        <li><i class="fa fa-thumbs-up"></i><span>1.7k</span></li>
                                        <li><i class="fa fa-comments"></i><span>1k</span></li>
                                        
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div></div></div><div class="slick-slide slick-cloned" data-slick-index="3" aria-hidden="true" tabindex="-1" style="width: 570px;"><div><div class="col-md-12" style="width: 100%; display: inline-block;">
					<div class="artical-inner">
						<div class="row">
							<div class="col-md-6 artical-img">
								<div class="">
									<img src="{{url("theme/artical-image-1.jpg");}}" );}} class="img-fluid" alt="img">
								</div>
							</div>
							<div class="col-md-6 border-artical">
								<div class="artical-content">
									<h6>23 Oct 2019</h6>
									<h2>World’s Best Places which you ever seen are here</h2>
									<a href=//blog-details.html" tabindex="-1">Read more</a>
									<ul>
                                        <li><i class="fa fa-thumbs-up"></i><span>1.7k</span></li>
                                        <li><i class="fa fa-comments"></i><span>1k</span></li>
                                        
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div></div></div><div class="slick-slide slick-cloned" data-slick-index="4" aria-hidden="true" tabindex="-1" style="width: 570px;"><div><div class="col-md-12" style="width: 100%; display: inline-block;">
					<div class="artical-inner">
						<div class="row">
							<div class="col-md-6 artical-img">
								<div class="">
									<img src="{{url("theme/artical-image-2.jpg");}}" );}} class="img-fluid" alt="img">
								</div>
							</div>
							<div class="col-md-6 border-artical">
								<div class="artical-content">
									<h6>26 Feb 2020</h6>
									<h2>Mountain beautiful Houses which like you most</h2>
									<a href=//blog-details.html" tabindex="-1">Read more</a>
									<ul>
                                        <li><i class="fa fa-thumbs-up"></i><span>1.7k</span></li>
                                        <li><i class="fa fa-comments"></i><span>1k</span></li>
                                        
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div></div></div><div class="slick-slide slick-cloned" data-slick-index="5" aria-hidden="true" tabindex="-1" style="width: 570px;"><div><div class="col-md-12" style="width: 100%; display: inline-block;">
					<div class="artical-inner">
						<div class="row">
							<div class="col-md-6 artical-img">
								<div class="">
									<img src="{{url("theme/artical-image-1.jpg");}}" );}} class="img-fluid" alt="img">
								</div>
							</div>
							<div class="col-md-6 border-artical">
								<div class="artical-content">
									<h6>28 Jan 2020</h6>
									<h2>World’s Best Places which you ever seen are here</h2>
									<a href=//blog-details.html" tabindex="-1">Read more</a>
									<ul>
                                        <li><i class="fa fa-thumbs-up"></i><span>1.7k</span></li>
                                        <li><i class="fa fa-comments"></i><span>1k</span></li>
                                        
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div></div></div></div></div><i class="fa fa-angle-left articalprev slick-arrow" style=""></i></div>
		</div>
	</section>


	<!-- =============================
        End: Our Articles
    ============================= -->

<!-- =============================
        Start: get this app
    ============================= -->
    <!-- =============================
        End: get this app
    ============================= -->
	<!-- =======================
        footer Start 
    ========================== -->
	<footer id="contact" class="contact">
        <div class="container">
            <div class="row contact-part">
                <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                    <div class="footer-add">
                        <img src="{{url("theme/footer-logo.png");}}" class="img-fluid" alt="logo">    
                        <div class="row footer-ad-txt footer-top">
                            <div class="col-lg-4 col-md-4 col-4">
                                <h3>Address :</h3>
                            </div>
                            <div class="col-lg-8 col-md-8 col-8">
                                <ul>
                                    <li>420 Rankin Street Avenue</li>
                                    <li>London City Center,</li>
                                     <li>United Kingdom</li>
                                </ul>
                            </div>
                        </div>
<div class="row footer-ad-txt">
                            <div class="col-4">
                                <h3>Phone	:</h3>
                            </div>
                            <div class="col-8">
                                <ul>
                                    <li>+442012345678</li>
                                    
                                </ul>
                            </div>
                        </div>
                        <div class="row footer-ad-txt footer-bot">
                            <div class="col-4">
                                <h3>Website	:</h3>
                            </div>
                            <div class="col-8">
                                <ul>
                                    <li> www.travelagency.com</li>
                                    
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="con-inner">
                        <h2>Help &amp; Support</h2>
                        <ul>
                            <li><a href=//index.html#">Contact Us</a></li>
                            <li><a href=//index.html#">FAQs</a></li>
                            <li><a href=//index.html#">Privacy policy</a></li>
                            <li><a href=//index.html#">Cookie policy</a></li>
                            <li><a href=//index.html#">Terms of use</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="con-inner">
                        <h2>Important Search</h2>
                        <ul>
                            <li><a href=//index.html#">Countries </a></li>
                            <li><a href=//index.html#">Cities</a></li>
                            <li><a href=//index.html#">Districts</a></li>
                            <li><a href=//index.html#">Airports</a></li>
                            <li><a href=//index.html#">Hotels</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 col-sm-6 col-12">
                    <div class="con-inner">
                        <h2>Follow us on</h2>
                        <ul>
                            <li><a href=//index.html#"><i class="fab fa-facebook-f"></i>Facebook</a></li>
                            <li><a href=//index.html#"><i class="fab fa-twitter"></i>Twitter</a></li>
                            <li><a href=//index.html#"><i class="fab fa-tripadvisor"></i>tripadvisor</a></li>                            
                            <li><a href=//index.html#"><i class="fab fa-linkedin"></i>Linkedin</a></li>
                            <li><a href=//index.html#"><i class="fab fa-behance"></i>Behance</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="footer">
                        <p>Copyright © 2020. All rights reserved by Designer</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
	<!-- =====================
     footer end 
     ===================== -->
    
    
    
    <!--================================================
                JAVA SCRIPT LINK PART START
    ==================================================-->
    <script src="{{url("theme/jquery-3.2.1.min.js");}}");}}"></script>
    <script src="{{url("theme/popper.min.js");}}"></script>
    <script src="{{url("theme/jquery-migrate-3.0.0.min.js");}}"></script>
    <script src="{{url("theme/bootstrap.min.js");}}"></script>
    <script src="{{url("theme/waypoints.min.js");}}"></script>
    <script src="{{url("theme/jquery.counterup.min.js");}}"></script>
    <script src="{{url("theme/jquery-ui.min.js");}}"></script>
    <script src="{{url("theme/jquery.dd.min.js");}}"></script>
    <script src="{{url("theme/mixitup.min.js");}}"></script>
    <script src="{{url("theme/slick.min.js");}}"></script>
    <script src="{{url("theme/SmoothScroll.js");}}"></script>
    <script src="{{url("theme/script.js");}}"></script>
    <!--================================================
                JAVA SCRIPT LINK PART END
    ==================================================-->
    


<script>
// Home page search: no past dates, check-out always after check-in
$(function () {
    var checkIn = $('#bs-check-in'), checkOut = $('#bs-check-out');
    var nextDay = function (date) { var d = new Date(date.getTime()); d.setDate(d.getDate() + 1); return d; };

    checkIn.datepicker({ dateFormat: 'yy-mm-dd', minDate: 0, onSelect: function () {
        var inDate = checkIn.datepicker('getDate');
        checkOut.datepicker('option', 'minDate', nextDay(inDate));
        var outDate = checkOut.datepicker('getDate');
        if (!outDate || outDate <= inDate) {
            checkOut.datepicker('setDate', nextDay(inDate));
        }
    }});
    checkOut.datepicker({ dateFormat: 'yy-mm-dd', minDate: 1 });

    $('#hotels-tab').on('submit', function (e) {
        if (!checkIn.val() || !checkOut.val()) {
            e.preventDefault();
            alert('Please choose your check-in and check-out dates.');
        }
    });
});
</script>
@include('partials.chat-widget')
<div id="ui-datepicker-div" class="ui-datepicker ui-widget ui-widget-content ui-helper-clearfix ui-corner-all"></div></body></html>
