@php($site = \App\Cms\Cms::settings())
@php($siteName = $site['site_name'] ?? 'EpickTours')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ trim($__env->yieldContent('title')) ? trim($__env->yieldContent('title')).' | '.$siteName : $siteName.' – '.($site['tagline'] ?? '') }}</title>
    <meta name="description" content="{{ trim($__env->yieldContent('meta_description')) ?: ($site['meta_description'] ?? '') }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ trim($__env->yieldContent('title')) ?: $siteName }}">
    <link rel="icon" href="{{ url('assets/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ url('theme/flaticon.css') }}">
    <link rel="stylesheet" href="{{ url('theme/all.min.css') }}">
    <link rel="stylesheet" href="{{ url('theme/animate.min.css') }}">
    <link rel="stylesheet" href="{{ url('theme/jquery-ui.min.css') }}">
    <link rel="stylesheet" href="{{ url('theme/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ url('theme/style-1.css') }}">
    <link rel="stylesheet" href="{{ url('theme/responsive-1.css') }}">
    <link rel="stylesheet" href="{{ url('css/site.css') }}">
    @yield('styles')
</head>
<body class="@yield('body_class')">
    <a href="#top" id="back-top-btn" aria-label="Back to top"><i class="fas fa-angle-double-up"></i></a>
    <span id="top"></span>

    <header>
        <nav id="nav-part" class="navbar header-nav custom_nav navbar-expand-lg">
            <div class="container">
                <a class="navbar-brand site-brand" href="{{ url('/') }}">
                    {{-- A logo uploaded in Website > Settings replaces the built-in EpickTours logo --}}
                    <img src="{{ !empty($site['logo_path']) ? \App\Cms\Cms::imageUrl($site['logo_path'], $site['logo_disk'] ?? null) : url('images/epicktours-logo.png') }}"
                         alt="{{ $siteName }}" width="135" height="56">
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#siteNav" aria-controls="siteNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span></span><span></span><span></span>
                </button>
                <div class="collapse navbar-collapse justify-content-end" id="siteNav">
                    <ul class="navbar-nav">
                        <li class="nav-item"><a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}">Home</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->is('search', 'stay/*', 'book') ? 'active' : '' }}" href="{{ url('/search') }}">Find a stay</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ url('/#destinations') }}">Destinations</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->is('blog', 'blog/*') ? 'active' : '' }}" href="{{ url('/blog') }}">Blog</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->is('about') ? 'active' : '' }}" href="{{ url('/about') }}">About</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->is('contact') ? 'active' : '' }}" href="{{ url('/contact') }}">Contact</a></li>
                    </ul>
                    <ul class="login_menu navbar-right nav-sign">
                        @auth
                            <li class="login"><a href="{{ auth()->user()->dashboardUrl() }}" class="btn-4">{{ auth()->user()->isCustomer() ? 'My bookings' : 'Dashboard' }}</a></li>
                        @else
                            <li class="login"><a href="{{ url('/account/login') }}" class="btn-4">Sign in</a></li>
                        @endauth
                    </ul>
                </div>
            </div>
        </nav>
        <div class="site-subbar">
            <div class="container">
                <a href="{{ url('/booking/find') }}">Find my booking</a>
                @guest<a href="{{ url('/login') }}">Property owners: sign in</a><a href="{{ url('/signup') }}">List your property</a>@endguest
                @include('partials.currency-switcher')
            </div>
        </div>
    </header>

    @if (session('success'))
        <div class="alert alert-success site-flash">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger site-flash">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger site-flash">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
