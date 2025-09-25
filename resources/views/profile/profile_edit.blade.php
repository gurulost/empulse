@extends('layouts.app')
@section('title')
    Profile
@endsection
@section('content')
    <!-- content -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" />
    <main class="profile-main">
        <div class="profile-content">
            <div class="profile-card">
                <div class="profile-card-img">
                    <img id="showImage" class="rounded img-thumbnail mx-auto d-block"
                         src="{{ (!empty(Auth::user()->image))?url('upload/'.Auth::user()->image):url('upload/no_image.jpg') }}"
                         alt="Avatar Image">
                </div>
                <div class="profile-card-buttons">
                    <div>
                        <a class="prof-btn-add" type="button" href="{{ route('add.avatar') }}">Update Avatar</a>
                    </div>
                    <div>
                        <a class="prof-btn-delete" type="button" href="{{ route('delete.avatar',Auth::user()->id) }}">Delete Avatar</a>
                    </div>
                </div>
            </div>
            <div class="profile-change-password-card">
                <div class="formEditPasswordBlock">
                    <form class="form-editPassword">
                        <div class="text-center mb-2">
                            <h1 class="form-password-title">Update data</h1>
                        </div>

                        @if(Auth::user()->role == 1)
                            <div class="form-label-group">
                                <label for="name">Company title:</label>
                                <input type="text" class="form-control company_title text-dark" value="{{Auth::user()->company_title}}" placeholder="{{Auth::user()->company_title}}" name="company_title" disabled>
                            </div>
                        @endif

                        <div class="form-label-group">
                            <label for="name">Name:</label>
                            <input type="text" class="form-control name" value="{{Auth::user()->name}}" placeholder="" name="name">
                        </div>

                        <div class="form-label-group">
                            <label for="email">Email:</label>
                            <input type="email" class="form-control email" value="{{Auth::user()->email}}" placeholder="Use only email" name="email">
                        </div>

                        <div class="form-label-group">
                            <label for="new_pass">New password:</label>
                            <input type="password" id="new_pass" class="form-control new_pass" placeholder="Min. 8 symbols" name="new_pass">
                        </div>

                        <div class="form-label-group">
                            <label for="conf_new_pass">Confirm new password:</label>
                            <input type="password" id="conf_new_pass" class="form-control conf_new_pass" placeholder="Repeat new password" name="conf_new_pass">
                        </div>

                        <button class="form-confirm-pass-btn" name="editPassword" type="submit">Edit</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        toastr.options = {
            "closeButton": false,
            "debug": false,
            "newestOnTop": false,
            "progressBar": false,
            "positionClass": "toast-top-center",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        }

        @if(Session::has("error"))
            toastr["error"]("{{Session::get('error')}}", "ERROR")
            {{ Session::forget('error') }}
        @elseif(Session::has("success"))
            toastr["success"]("{{Session::get('success')}}", "SUCCESS")
            {{Session::forget('success')}}
        @endif
    </script>

    <script src='{{asset('/js/profile.js')}}' type="module"></script>
@endsection


