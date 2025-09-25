@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{asset('/css/email.css')}}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css">
<link rel="stylesheet" type="text/css" href="{{asset('/css/auth/auth.css')}}">

<div class="content-main">
    <div class="content-logo">
        <div class="content-logo-block">
            <img class="content-main-logo" src="../../materials/images/workfitdxr_logo_1.png">
        </div>
        <div class="content-title-block">
            <h1 class="content-title">
                A better way to address the gap in employee satisfaction
            </h1>
        </div>
    </div>
    <div class="content-auth">
        <div class="cont-log">
            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif
            <form>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input id="email" type="email" autofocus placeholder="Email Address:" class="userEmail form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                        @error('email')
                        <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                        @enderror
                    </div>
                </div>

                <div class="row mb-0">
                    <div>
                        <button type="submit" class="resetPasswordButton">
                            {{ __('Send Password Reset Link') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="{{asset('/js/auth/email.js')}}" type="module"></script>
@endsection
