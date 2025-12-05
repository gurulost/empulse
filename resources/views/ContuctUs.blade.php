@extends('layouts.app')
@section('title')
    Contuct us!
@endsection
@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" />
    <div class="d-flex align-items-center justify-content-center" style="height: 100vh; margin-left: 140px;">
        <div class="modal-dialog" role="document">
            <div class="rounded-4 shadow contuctUs-window border" style="width: 500px;">
                <div class="modal-header p-5 pb-4 border-bottom-0">
                    <h1 class="fw-bold mb-0 fs-2">Send your data to us</h1>
                </div>

                <div class="modal-body p-5 pt-0">
                    <form method="POST" action="{{ route('contact.send') }}">
                        @csrf
                        <div class="form-floating mb-3">
                            <input id="name" type="text" name="name" class="form-control" value="{{ Auth::check() ? Auth::user()->name : '' }}" required>
                            <label for="floatingInput">Name</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input id="email" type="email" name="email" class="form-control" value="{{ Auth::check() ? Auth::user()->email : '' }}" required>
                            <label for="floatingPassword">Email</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input id="phone" type="tel" name="phone" class="form-control" value="555-555-1234">
                            <label for="floatingPassword">Phone</label>
                        </div>
                        <button class="w-100 mb-2 btn btn-lg rounded-3 btn-primary sendMessageButton" type="submit">Send</button>
                        <small class="text-muted">We will read your message during 3 days.</small>
                        <hr class="my-4">
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src='{{asset('/js/contuctUs.js')}}' type="module"></script>
@endsection
