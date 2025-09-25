@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="{{asset('/css/reset.css')}}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css">

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
                <form id="reset_old_password">
                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <input id="email" type="email" placeholder="Use only email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <input id="password" type="password" placeholder="Min 8 symbols" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                                @error('password')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <input id="password-confirm" placeholder="Repeat new password" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>

                        <div>
                            <div>
                                <button type="submit" class="btn btn-danger reset" disabled>
                                    {{ __('Reset Password') }}
                                </button>
                            </div>
                        </div>
                    </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="{{asset('/js/auth/reset_password_confirm.js')}}" type="module"></script>
@endsection
