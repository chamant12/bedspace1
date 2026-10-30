@php($site = \App\Cms\Cms::settings())
    <footer id="contact" class="contact site-footer">
        <div class="container">
            <div class="row contact-part">
                <div class="col-lg-4 col-md-6 col-12 mb-4">
                    <div class="site-brand-name site-brand-footer">{{ $site['site_name'] ?? 'EpickTours' }}</div>
                    <p class="site-footer-about">{{ $site['footer_about'] ?? '' }}</p>
                    @if(!empty($site['address']))
                        <p class="mb-1"><i class="fas fa-map-marker-alt"></i> {!! nl2br(e($site['address'])) !!}</p>
                    @endif
                    @if(!empty($site['contact_phone']))
                        <p class="mb-1"><i class="fas fa-phone"></i> <a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['contact_phone']) }}">{{ $site['contact_phone'] }}</a></p>
                    @endif
                    @if(!empty($site['contact_email']))
                        <p class="mb-1"><i class="fas fa-envelope"></i> <a href="mailto:{{ $site['contact_email'] }}">{{ $site['contact_email'] }}</a></p>
                    @endif
                </div>
                <div class="col-lg-3 col-md-6 col-12 mb-4">
                    <div class="con-inner">
                        <h2>Plan your trip</h2>
                        <ul>
                            <li><a href="{{ url('/search') }}">Find a stay</a></li>
                            <li><a href="{{ url('/#destinations') }}">Popular destinations</a></li>
                            <li><a href="{{ url('/blog') }}">Travel journal</a></li>
                            <li><a href="{{ url('/booking/find') }}">Find my booking</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12 mb-4">
                    <div class="con-inner">
                        <h2>Help &amp; information</h2>
                        <ul>
                            <li><a href="{{ url('/about') }}">About us</a></li>
                            <li><a href="{{ url('/contact') }}">Contact us</a></li>
                            @foreach(\App\Cms\Cms::footerPages() as $footerPage)
                                <li><a href="{{ \App\Cms\Cms::pageUrl($footerPage->slug) }}">{{ $footerPage->title }}</a></li>
                            @endforeach
                            <li><a href="{{ url('/signup') }}">List your property</a></li>
                        </ul>
                    </div>
                </div>
                @php($socials = array_filter(['facebook' => $site['facebook_url'] ?? null, 'instagram' => $site['instagram_url'] ?? null, 'tripadvisor' => $site['tripadvisor_url'] ?? null, 'youtube' => $site['youtube_url'] ?? null]))
                @if($socials)
                <div class="col-lg-2 col-md-6 col-12 mb-4">
                    <div class="con-inner">
                        <h2>Follow us</h2>
                        <ul>
                            @foreach($socials as $network => $link)
                                @if(preg_match('#^https?://#i', $link))
                                    <li><a href="{{ $link }}" target="_blank" rel="noopener"><i class="fab fa-{{ $network === 'facebook' ? 'facebook-f' : $network }}"></i> {{ ucfirst($network) }}</a></li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="footer">
                        <p>&copy; {{ date('Y') }} {{ $site['copyright'] ?? 'EpickTours. All rights reserved.' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="{{ url('theme/jquery-3.2.1.min.js') }}"></script>
    <script src="{{ url('theme/popper.min.js') }}"></script>
    <script src="{{ url('theme/bootstrap.min.js') }}"></script>
    <script src="{{ url('theme/jquery-ui.min.js') }}"></script>
    <script src="{{ url('js/site.js') }}"></script>
    @yield('scripts')
    @include('partials.chat-widget')
</body>
</html>
