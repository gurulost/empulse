@extends('layouts.app')
@section('title')
    Departments
@endsection
@section("content")
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" />
    <div class="departmentsMainBlock" style="">
        <button type="button" class="btn btn-primary addNewDepartment" data-bs-toggle="modal" data-bs-target="#exampleModal" style="margin-top: 100px; margin-left: 100px;">Add new department</button><hr />

        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true" style="position: fixed; top: 25%; left: 0">
            <div class="modal-dialog">
                <div class="modal-content" style="height: 100%; width: 100%">
                    <div class="modal-header">
                        <h4 class="modal-title fs-5" id="exampleModalLabel">Add new department</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="margin-top: -5px;">
                        <label>
                            <b>Department name:</b>
                            <input type="text" class="form-control title" name="title" placeholder="Max. 50 symbols" style="width: 300px;">
                            {{--                                <p class="text-danger fw-bold d-none danger-alert mt-1">Please, enter only 50 symbols!</p>--}}
                        </label>

                        <br /><br />
                        <button class="btn btn-primary add">ADD</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="depart-content-main">
            <div id="users-table">
                @include('roles.departments_table')
            </div>
        </div>
    </div>

@endsection

@section('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src='{{asset('/js/roles/departments.js')}}' type="module"></script>
    <script>
        toastr.options = {
            "closeButton": false,
            "debug": true,
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

        @if(Session::has('addDepartment_error'))
            toastr["error"](" {{Session::get('addDepartment_error')}} ")
            {{Session::forget('addDepartment_error')}}
        @elseif(Session::has('deleteDepartment_error_user_exist'))
            toastr["error"](" {{Session::get('deleteDepartment_error_user_exist')}} ")
            {{Session::forget('deleteDepartment_error_user_exist')}}
        @endif
    </script>
@endsection
