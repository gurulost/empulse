@extends('layouts.app')
@section('title')
    Profile
@endsection
@section('content')
    <main class="profile-main">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">Profile Settings</h1>
                <p class="page-subtitle">Manage your account information and security.</p>
            </div>

            <div class="profile-content">
                <div class="profile-card animate-fade-in-up">
                    <div class="profile-card-img">
                        <img id="showImage" class="rounded-circle"
                             src="{{ (!empty(Auth::user()->image))?url('upload/'.Auth::user()->image):url('upload/no_image.jpg') }}"
                             alt="Avatar Image">
                    </div>
                    <h5 class="fw-bold mb-1" style="font-family: 'Outfit', sans-serif;">{{ Auth::user()->name }}</h5>
                    <p class="text-muted small mb-4">{{ Auth::user()->email }}</p>
                    <div class="profile-card-buttons">
                        <div>
                            <a class="prof-btn-add" type="button" href="{{ route('add.avatar') }}">
                                <i class="bi bi-camera me-2"></i>Update Avatar
                            </a>
                        </div>
                        <div>
                            <a class="prof-btn-delete" type="button" href="{{ route('delete.avatar',Auth::user()->id) }}">
                                <i class="bi bi-trash3 me-2"></i>Remove Avatar
                            </a>
                        </div>
                    </div>
                </div>
                <div class="profile-change-password-card animate-fade-in-up delay-1">
                    <div class="formEditPasswordBlock">
                        <form class="form-editPassword">
                            <h1 class="form-password-title">Update Information</h1>

                            @if(Auth::user()->role == 1)
                                <div class="form-label-group">
                                    <label for="name">Company</label>
                                    <input type="text" class="form-control company_title text-dark" value="{{Auth::user()->company_title}}" placeholder="{{Auth::user()->company_title}}" name="company_title" disabled>
                                </div>
                            @endif

                            <div class="form-label-group">
                                <label for="name">Name</label>
                                <input type="text" class="form-control name" value="{{Auth::user()->name}}" placeholder="" name="name">
                            </div>

                            <div class="form-label-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control email" value="{{Auth::user()->email}}" placeholder="Use only email" name="email">
                            </div>

                            <hr class="my-4" style="border-color: #e2e8f0;">

                            <h6 class="fw-bold mb-3" style="font-family: 'Outfit', sans-serif; color: #0c1222;">Change Password</h6>

                            <div class="form-label-group">
                                <label for="new_pass">New password</label>
                                <input type="password" id="new_pass" class="form-control new_pass" placeholder="Min. 8 characters" name="new_pass">
                            </div>

                            <div class="form-label-group">
                                <label for="conf_new_pass">Confirm new password</label>
                                <input type="password" id="conf_new_pass" class="form-control conf_new_pass" placeholder="Repeat new password" name="conf_new_pass">
                            </div>

                            <button class="form-confirm-pass-btn" name="editPassword" type="submit">
                                <i class="bi bi-check2 me-2"></i>Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src='{{asset('/js/profile.js')}}' type="module"></script>
@endsection
