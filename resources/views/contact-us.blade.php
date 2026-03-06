@extends('layouts.app')
@section('title')
    Contact Us
@endsection
@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 72px; height: 72px; background: linear-gradient(135deg, rgba(79,70,229,0.08), rgba(99,102,241,0.04));">
                        <i class="bi bi-chat-dots-fill text-primary" style="font-size: 1.75rem;"></i>
                    </div>
                    <h1 class="page-title text-center" style="font-size: 1.75rem;">Get in Touch</h1>
                    <p class="page-subtitle text-center mx-auto" style="max-width: 400px;">
                        Have a question or need help? Send us a message and we'll get back to you within 3 business days.
                    </p>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-lg-5">
                        <form method="POST" action="{{ route('contact.send') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">Name</label>
                                <input id="name" type="text" name="name" class="form-control form-control-lg" value="{{ Auth::check() ? Auth::user()->name : '' }}" required placeholder="Your full name">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Email</label>
                                <input id="email" type="email" name="email" class="form-control form-control-lg" value="{{ Auth::check() ? Auth::user()->email : '' }}" required placeholder="your@email.com">
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label fw-semibold">Phone <span class="text-muted fw-normal">(optional)</span></label>
                                <input id="phone" type="tel" name="phone" class="form-control form-control-lg" placeholder="555-555-1234">
                            </div>
                            <div class="mb-4">
                                <label for="message" class="form-label fw-semibold">Message</label>
                                <textarea id="message" name="message" class="form-control form-control-lg" rows="4" required placeholder="How can we help you?"></textarea>
                            </div>
                            <button class="btn btn-primary btn-lg w-100 rounded-pill fw-bold sendMessageButton" type="submit">
                                <i class="bi bi-send me-2"></i>Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src='{{asset('/js/contactUs.js')}}' type="module"></script>
@endsection
