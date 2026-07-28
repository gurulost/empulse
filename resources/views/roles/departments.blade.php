@extends('layouts.app')
@section('title')
    Departments
@endsection
@section("content")
    @if(Session::has('addDepartment_error'))
        <div class="alert alert-danger" role="alert">
            {{ Session::get('addDepartment_error') }}
        </div>
    @elseif(Session::has('deleteDepartment_error_user_exist'))
        <div class="alert alert-danger" role="alert">
            {{ Session::get('deleteDepartment_error_user_exist') }}
        </div>
    @endif
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
    <script src='{{asset('/js/roles/departments.js')}}' type="module"></script>
@endsection
