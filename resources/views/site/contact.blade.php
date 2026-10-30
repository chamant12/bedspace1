@extends('layout.ext-app')
@php($intro = $page->sections->get('intro'))
@php($site = \App\Cms\Cms::settings())
@section('title', $page->title)
@section('meta_description', $page->meta_description)
@section('content')
@include('site._pageHead', ['heading' => $intro?->heading ?? $page->title, 'subheading' => $intro?->subheading])
<div class="site-page-body">
    <div class="container">
        <div class="row">
            <div class="col-lg-7 mb-4">
                @if($intro)<div class="site-prose mb-3">{{ \App\Cms\Cms::markdown($intro->body) }}</div>@endif
                <form method="POST" action="/contact" class="site-contact-form">
                    @csrf
                    {{-- Leave empty: catches spam bots --}}
                    <div style="position:absolute; left:-10000px;" aria-hidden="true">
                        <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="contact-name">Your name<span class="text-danger">*</span></label>
                            <input type="text" id="contact-name" name="name" class="form-control" required maxlength="255" value="{{ old('name') }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="contact-email">Email<span class="text-danger">*</span></label>
                            <input type="email" id="contact-email" name="email" class="form-control" required maxlength="255" value="{{ old('email') }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="contact-phone">Phone</label>
                            <input type="text" id="contact-phone" name="phone" class="form-control" maxlength="50" value="{{ old('phone') }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="contact-subject">Subject</label>
                            <select id="contact-subject" name="subject" class="form-control">
                                @foreach(['A question about a booking', 'Planning a trip', 'Listing my property', 'Partnerships', 'Something else'] as $subject)
                                    <option @selected(old('subject') === $subject)>{{ $subject }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="contact-message">Message<span class="text-danger">*</span></label>
                            <textarea id="contact-message" name="message" class="form-control" rows="6" required minlength="10" maxlength="5000">{{ old('message') }}</textarea>
                        </div>
                    </div>
                    <button type="submit" class="site-btn">Send message</button>
                </form>
            </div>
            <div class="col-lg-5">
                <div class="site-contact-card">
                    <h3 class="site-heading" style="font-size:22px;">{{ $site['site_name'] ?? 'EpickTours' }}</h3>
                    @if(!empty($site['address']))
                        <p><i class="fas fa-map-marker-alt mr-2"></i>{!! nl2br(e($site['address'])) !!}</p>
                    @endif
                    @if(!empty($site['contact_phone']))
                        <p><i class="fas fa-phone mr-2"></i><a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['contact_phone']) }}">{{ $site['contact_phone'] }}</a></p>
                    @endif
                    @if(!empty($site['whatsapp']))
                        <p><i class="fab fa-whatsapp mr-2"></i><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $site['whatsapp']) }}" target="_blank" rel="noopener">{{ $site['whatsapp'] }}</a></p>
                    @endif
                    @if(!empty($site['contact_email']))
                        <p><i class="fas fa-envelope mr-2"></i><a href="mailto:{{ $site['contact_email'] }}">{{ $site['contact_email'] }}</a></p>
                    @endif
                    @if(!empty($site['office_hours']))
                        <p><i class="far fa-clock mr-2"></i>{{ $site['office_hours'] }}</p>
                    @endif
                    <hr>
                    <p class="mb-1"><strong>Already booked?</strong></p>
                    <p class="mb-0"><a href="/booking/find">Find your booking</a> to view, pay for or cancel it.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
