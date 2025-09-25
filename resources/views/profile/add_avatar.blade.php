@extends('layouts.app')
@section('title')
    Update Avatar
@endsection
@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" />
    <link rel="stylesheet" type="text/css" href="/css/profile.css?v={{date('His')}}">

    <main  class="avatar-main">
        <div class="avatar-container">
            <div class="avatar-card">
                <div class="avatar-header-title">
                    <h4 class="avatar-title">Update Avatar</h4>
                </div>

                <div class="avatar-content">
                    <form method="post" action="{{ route('store.avatar')}}" enctype="multipart/form-data">
                        @csrf

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="avatar-adder-image">
                            <label for="example-text-input" class="avatar-title-img">Avatar Image</label>
                            <div class="avatar-input">
                                <input name="image" class="form-control" type="file" id="image" max="12288"
                                       accept=".jpg, .jpeg, .png" required>
                            </div>
                        </div>
                        <!-- end row -->
                        <div class="">
                            <label for="example-text-input" class="col-sm-2 col-form-label"></label>
                            <div class="">
                                <img id="showImage" class="rounded img-thumbnail"
                                     src="{{ (!empty(Auth::user()->image))?url('upload/'.Auth::user()->image):url('upload/no_image.jpg') }}"
                                     alt="Avatar Image" width="100px" height="100px">
                            </div>
                        </div>
                        <!-- end row -->
                        <input type="submit" class="avatar-update-btn" value="Update">
                    </form>
                </div>
            </div>
        </div>
    </main>
    <!-- end content -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        @if(Session::has('error-upload-avatar'))
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
            toastr["error"]("{{Session::get('error-upload-avatar')}}", "ERROR!!!")

            {{ Session::forget('error-upload-avatar') }}
        @endif
    </script>

    <script scr='{{asset('/js/profile_add_avatar.js')}}'></script>
@endsection


