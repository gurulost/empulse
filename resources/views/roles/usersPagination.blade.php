<link rel="stylesheet" href="{{asset('/css/usersPagination.css')}}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" />
@if(Auth::user()->role == 1)
    <div id="users-table">
        <table class="table table-striped table-bordered table-sm bg-white">
            <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Email</th>
                <th scope="col" style="width: 12%">Role</th>
                <th scope="col" style="width: 30%">Department</th>
                <th scope="col" style="width: 12%">Actions</th>
            </tr>
            </thead>
            <tbody>
            @foreach($users as $user)
{{--                @if($user->email !== Auth::user()->email)--}}
                    <tr>
                        <td class="name-{{$user->id}}">
                            <form class="changeName-{{$user->id}} mt-3 d-none">
                                <input type="text" value="{{$user->name}}" class="newTableName-{{$user->id}} form-control">
                            </form>

                            <form class="currentyName-{{$user->id}} mt-4" style="display: block;">
                                <p class="fw-bold p-name">{{$user->name}}</p>
                            </form>
                        </td>
                        <td class="email-{{$user->id}}" style="">
                            <form class="changeEmail-{{$user->id}} mt-3 d-none">
                                <input type="text" value="{{$user->email}}" class="newTableEmail-{{$user->id}} form-control">
                            </form>

                            <form class="currentyEmail-{{$user->id}} mt-4" style="display: block;">
                                <p class="fw-bold p-email">{{$user->email}}</p>
                            </form>
                        </td>
                        <td>
                            <div class="btn-group btn-group-{{$user->id}}">
                                @if ($user->email == $head)
                                    <button type="button" class="btn btn-primary dropdown-toggle m-3" disabled>
                                        Owner
                                    </button>
                                @else
                                    <button type="button" class="btn btn-primary dropdown-toggle m-3 userRole" data-bs-toggle="dropdown" aria-expanded="false" disabled>
                                        @if($user->role == 1)
                                            Manager
                                        @elseif($user->role == 2)
                                            Chief
                                        @endif
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-{{$user->id}}">
                                        @if($user->role == 1)
                                            <li>
                                                <form class="table-form-select-chief-{{$user->id}}">
                                                    <button class="btn-select-{{$user->id}} dropdown-item" >Chief</button>
                                                </form>
                                            </li>
                                        @endif
                                        @if($user->role == 2)
                                            <li>
                                                <form class="table-form-select-manager-{{$user->id}}">
                                                    <button class="btn-select-{{$user->id}} dropdown-item" >Manager</button>
                                                </form>
                                            </li>
                                        @endif
                                    </ul>
                                @endif
                            </div>
                        </td>
                        <td class="department-col-{{$user->id}}" >
                            @if($user->role == 1)
                                <p class="m-4">None department</p>
                            @else
                                <button type="button" style="width: 300px" class="btn btn-primary dropdown-toggle m-3 userDepartment userDepartment-{{$user->id}}" data-bs-toggle="dropdown" aria-expanded="false" disabled default_value="{{ $user->department == '' || $user->department == null ? 'None department' : $user->department }}">
                                    @php
                                        $departments_array = [];
                                        if(isset($departments) && count($departments) > 0) {
                                            foreach($departments as $department) {
                                                $departments_array[] = $department->title;
                                            }
                                        }
                                    @endphp

                                    @if($user->department == '' || !in_array($user->department, $departments_array))
                                        None department
                                    @else
                                        {{$user->department}}
                                    @endif
                                </button>
                                @if(count($departments) > 0 && isset($departments))
                                    <ul class="dropdown-menu dropdown-menu-department-{{$user->id}}" style="max-height: 100px; overflow-y: auto;">
                                        @foreach($departments as $department)
                                            <li>
                                                <form class="department-form-{{$user->id}}">
                                                    <input type="hidden" value="{{$department->title}}">
                                                    <button class="btn-select-{{$user->id}} dropdown-item" >{{$department->title}}</button>
                                                </form>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            @endif
                        </td>
                        <td>
                            @if ($user->email != $head)
                                <button type="button" class="update-info-{{$user->id}} btn btn-primary mt-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16">
                                        <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                                    </svg>
                                </button>
                                <span item_value="{{$user->email}}" class="confirmDeleteWorker btn btn-danger mt-3" data-bs-toggle="modal" data-bs-target="#bootModal">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                            <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6Z"/>
                                            <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1ZM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118ZM2.5 3h11V2h-11v1Z"/>
                                        </svg>
                                    </span>

                                <button type="button" class="save-update-info-{{$user->id}} btn btn-success d-none mt-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 100 100">
                                        <circle cx="50" cy="50" r="40" stroke="white" stroke-width="5" fill="none" />
                                        <polyline points="35,50 45,60 65,40" stroke="white" stroke-width="5" fill="none" />
                                    </svg>
                                </button>
                                <button type="button" class="cancel-update-info-{{$user->id}} btn btn-danger d-none mt-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 100 100">
                                        <line x1="30" y1="30" x2="70" y2="70" stroke="white" stroke-width="5" />
                                        <line x1="30" y1="70" x2="70" y2="30" stroke="white" stroke-width="5" />
                                    </svg>
                                </button>
                            @endif
                        </td>
                    </tr>
                    <script>
                        $(document).ready(() => {
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
                                "timeOut": "1000",
                                "extendedTimeOut": "1000",
                                "showEasing": "swing",
                                "hideEasing": "linear",
                                "showMethod": "fadeIn",
                                "hideMethod": "fadeOut"
                            }

                            $(document).off('click', '.update-info-{{$user->id}}')
                            $(document).on('click', '.update-info-{{$user->id}}', async function() {
                                const tr = $(this).parent().parent();
                                $(this).addClass('d-none');
                                tr.find('.currentyName-{{$user->id}}').addClass('d-none');
                                tr.find('.changeName-{{$user->id}}').removeClass('d-none');

                                tr.find('.currentyEmail-{{$user->id}}').addClass('d-none');
                                tr.find('.changeEmail-{{$user->id}}').removeClass('d-none');

                                tr.find('.userRole').prop('disabled', false);
                                tr.find('.userDepartment-{{$user->id}}').prop('disabled', false);

                                tr.find('.confirmDeleteWorker').addClass('d-none');

                                tr.find('.save-update-info-{{$user->id}}').removeClass('d-none');
                                tr.find('.cancel-update-info-{{$user->id}}').removeClass('d-none');

                                sessionStorage.removeItem('department');
                                sessionStorage.removeItem('userRole');
                            })

                            $(document).on('click', '.cancel-update-info-{{$user->id}}', async function() {
                                const tr = $(this).parent().parent();
                                $(this).addClass('d-none');
                                $(this).parents('tr').find('.userDepartment').text($(this).parents('tr').find('.userDepartment').attr('default_value'));
                                tr.find('.currentyName-{{$user->id}}').removeClass('d-none');
                                tr.find('.changeName-{{$user->id}}').addClass('d-none');

                                tr.find('.currentyEmail-{{$user->id}}').removeClass('d-none');
                                tr.find('.changeEmail-{{$user->id}}').addClass('d-none');

                                tr.find('.userRole').prop('disabled', true);
                                tr.find('.userDepartment-{{$user->id}}').prop('disabled', true);

                                tr.find('.confirmDeleteWorker').removeClass('d-none');
                                tr.find('.update-info-{{$user->id}}').removeClass('d-none');

                                tr.find('.save-update-info-{{$user->id}}').addClass('d-none');
                            })

                            $(document).off('click', '.save-update-info-{{$user->id}}')
                            $(document).on('click', '.save-update-info-{{$user->id}}', async function() {
                                $(this).prop('disabled', true);

                                const tr = $(this).parent().parent();
                                const new_name = tr.find('.changeName-{{$user->id}} > input').val();
                                const new_email = tr.find('.changeEmail-{{$user->id}} > input').val();
                                const new_role = sessionStorage.getItem('userRole');
                                const new_department = sessionStorage.getItem('department');

                                try {
                                    const request = await fetch('{{route('udpateUser', ['email' => $user->email])}}', {
                                        method: 'PUT',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{csrf_token()}}'
                                        },
                                        body: JSON.stringify({
                                            new_name: new_name,
                                            new_email: new_email,
                                            new_role: new_role,
                                            new_department: new_department
                                        })
                                    });

                                    const response = await request.json();
                                    if(response.status === 200) {
                                        $(this).addClass('d-none');
                                        $(this).parents('tr').find('.userDepartment').attr('default_value', $(this).parents('tr').find('.userDepartment').text());
                                        tr.find('.currentyName-{{$user->id}}').removeClass('d-none');
                                        tr.find('.currentyName-{{$user->id}} > p').text(new_name);

                                        tr.find('.changeName-{{$user->id}}').addClass('d-none');

                                        tr.find('.currentyEmail-{{$user->id}}').removeClass('d-none');
                                        tr.find('.currentyEmail-{{$user->id}} > p').text(new_email);

                                        tr.find('.changeEmail-{{$user->id}}').addClass('d-none');

                                        tr.find('.userRole').prop('disabled', true);

                                        if(new_role) {
                                            if(new_role == 1) {
                                                tr.find('.userRole').text('Manager')
                                            } else if(new_role == 2) {
                                                tr.find('.userRole').text('Chief')
                                            } else if(new_role == 3) {
                                                tr.find('.userRole').text('Teamlead')
                                            } else if(new_role == 4) {
                                                tr.find('.userRole').text('Employee')
                                            }
                                        }

                                        tr.find('.userDepartment-{{$user->id}}').prop('disabled', true);
                                        new_department ? tr.find('.userDepartment-{{$user->id}}').text(new_department) :false;

                                        tr.find('.confirmDeleteWorker').removeClass('d-none');
                                        tr.find('.update-info-{{$user->id}}').removeClass('d-none');

                                        tr.find('.cancel-update-info-{{$user->id}}').addClass('d-none');

                                        toastr["success"](response.message)
                                    } else {
                                        toastr["error"](response.message)
                                    }
                                } catch(error) {
                                    toastr["error"](error);
                                    console.error(error)
                                }

                                $(this).prop('disabled', false);
                            })

                            $(document).on('click', '.dropdown-menu-department-{{$user->id}} .department-form-{{$user->id}}', async function(e) {
                                e.preventDefault();
                                const department = $(this).find('input').val();
                                $('.userDepartment-{{$user->id}}').text(department);
                                sessionStorage.setItem('department', department);
                            })

                            $('.dropdown-menu-{{$user->id}}').on('submit', '.table-form-select-chief-{{$user->id}}', function(e) {
                                e.preventDefault();

                                $('.department-col-{{$user->id}}').html(
                                    `
                                                    <button type="button" style="width: 300px" class="btn btn-primary dropdown-toggle m-3 userDepartment-{{$user->id}}" data-bs-toggle="dropdown" aria-expanded="false">
                                                        None department
                                                    </button>
                                                    @if(count($departments) > 0 && isset($departments))
                                                        <ul class="dropdown-menu dropdown-menu-department-{{$user->id}}" style="max-height: 100px; overflow-y: auto;">
                                                            @foreach($departments as $department)
                                                                 <li>
                                                                    <form class="department-form-{{$user->id}}">
                                                                        <input type="hidden" value="{{$department->title}}">
                                                                        <button class="btn-select-{{$user->id}} dropdown-item" >{{$department->title}}</button>
                                                                    </form>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
`
                                )
                                $('.dropdown-menu-{{$user->id}}').html(
                                    `
                                            <li>
                                                <form class="table-form-select-manager-{{$user->id}}">
                                                    <button class="btn-select-{{$user->id}} dropdown-item" >Manager</button>
                                                </form>
                                            </li>
                                        `
                                )
                                $('.btn-group-{{$user->id}} > .userRole').text('Chief')

                                sessionStorage.setItem('userRole', 2);
                            })

                            $('.dropdown-menu-{{$user->id}}').on('submit', '.table-form-select-manager-{{$user->id}}', function(e) {
                                e.preventDefault();
                                $('.department-col-{{$user->id}}').html(
                                    `<p class="m-4">None department</p>`
                                )
                                $('.dropdown-menu-{{$user->id}}').html(
                                    `
                                            <li>
                                                <form class="table-form-select-chief-{{$user->id}}">
                                                    <button class="btn-select-{{$user->id}} dropdown-item" >Chief</button>
                                                </form>
                                            </li>
                                        `
                                )
                                $('.btn-group-{{$user->id}} > .userRole').text('Manager');

                                sessionStorage.setItem('userRole', 1);
                            })
                        })
                    </script>
{{--                @endif--}}
            @endforeach
            </tbody>
        </table>
    </div>
@elseif(Auth::user()->role == 2)
    <div id="users-table">
        <table class="table table-striped table-bordered table-sm bg-white">
            <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Email</th>
                <th scope="col">Role</th>
                <th scope="col">Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach($users as $user)
                @if($user->email !== Auth::user()->email)
                    <tr>
                        <td class="name-{{$user->id}}">
                            <form class="changeName-{{$user->id}} mt-3 d-none">
                                <input type="text" value="{{$user->name}}" class="newTableName-{{$user->id}} form-control">
                            </form>

                            <form class="currentyName-{{$user->id}} mt-4" style="display: block;">
                                <p class="fw-bold p-name">{{$user->name}}</p>
                            </form>
                        </td>
                        <td class="email-{{$user->id}}" style="">
                            <form class="changeEmail-{{$user->id}} mt-3 d-none">
                                <input type="text" value="{{$user->email}}" class="newTableEmail-{{$user->id}} form-control">
                            </form>

                            <form class="currentyEmail-{{$user->id}} mt-4" style="display: block;">
                                <p class="fw-bold p-email">{{$user->email}}</p>
                            </form>
                        </td>
                        <td>
                            <div class="btn-group btn-group-{{$user->id}}">
                                <button type="button" class="btn btn-primary dropdown-toggle m-3 userRole" data-bs-toggle="dropdown" aria-expanded="false" disabled>
                                    @if($user->role == 3)
                                        Teamlead
                                    @elseif($user->role == 4)
                                        Employee
                                    @endif
                                </button>
                                <ul class="dropdown-menu dropdown-menu-{{$user->id}}">
                                    @if($user->role == 3)
                                        <li>
                                            <form class="table-form-select-employee-{{$user->id}}">
                                                <button class="btn-select-{{$user->id}} dropdown-item" >Employee</button>
                                            </form>
                                        </li>
                                    @endif
                                    @if($user->role == 4)
                                        <li>
                                            <form class="table-form-select-teamlead-{{$user->id}}">
                                                <button class="btn-select-{{$user->id}} dropdown-item" >Teamlead</button>
                                            </form>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                        <td>
                            <button type="button" class="update-info-{{$user->id}} btn btn-primary mt-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16">
                                    <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                                </svg>
                            </button>
                            <span item_value="{{$user->email}}" class="confirmDeleteWorker btn btn-danger mt-3" data-bs-toggle="modal" data-bs-target="#bootModal">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6Z"/>
                                        <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1ZM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118ZM2.5 3h11V2h-11v1Z"/>
                                    </svg>
                                </span>

                            <button type="button" class="save-update-info-{{$user->id}} btn btn-success d-none mt-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 100 100">
                                    <circle cx="50" cy="50" r="40" stroke="white" stroke-width="5" fill="none" />
                                    <polyline points="35,50 45,60 65,40" stroke="white" stroke-width="5" fill="none" />
                                </svg>
                            </button>
                            <button type="button" class="cancel-update-info-{{$user->id}} btn btn-danger d-none mt-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 100 100">
                                    <line x1="30" y1="30" x2="70" y2="70" stroke="white" stroke-width="5" />
                                    <line x1="30" y1="70" x2="70" y2="30" stroke="white" stroke-width="5" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                    <script>
                        $(document).ready(() => {
                            toastr.options = {
                                "closeButton": false,
                                "debug": true,
                                "newestOnTop": false,
                                "progressBar": false,
                                "positionClass": "toast-top-right",
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

                            $('.dropdown-menu-{{$user->id}}').on('submit', '.table-form-select-teamlead-{{$user->id}}', function(e) {
                                e.preventDefault();
                                $('.dropdown-menu-{{$user->id}}').html(
                                    `
                                            <li>
                                                <form class="table-form-select-employee-{{$user->id}}">
                                                    <button class="btn-select-{{$user->id}} dropdown-item" >Employee</button>
                                                </form>
                                            </li>
                                        `
                                )
                                $('.btn-group-{{$user->id}} > .userRole').text('Teamlead')

                                sessionStorage.setItem('userRole', 3);
                            })

                            $('.dropdown-menu-{{$user->id}}').on('submit', '.table-form-select-employee-{{$user->id}}', function(e) {
                                e.preventDefault();
                                $('.dropdown-menu-{{$user->id}}').html(
                                    `
                                            <li>
                                                <form class="table-form-select-teamlead-{{$user->id}}">
                                                    <button class="btn-select-{{$user->id}} dropdown-item" >Teamlead</button>
                                                </form>
                                            </li>
                                        `
                                )
                                $('.btn-group-{{$user->id}} > .userRole').text('Employee')

                                sessionStorage.setItem('userRole', 4);
                            })

                            $(document).off('click', '.update-info-{{$user->id}}')
                            $(document).on('click', '.update-info-{{$user->id}}', async function() {
                                const tr = $(this).parent().parent();
                                $(this).addClass('d-none');
                                tr.find('.currentyName-{{$user->id}}').addClass('d-none');
                                tr.find('.changeName-{{$user->id}}').removeClass('d-none');

                                tr.find('.currentyEmail-{{$user->id}}').addClass('d-none');
                                tr.find('.changeEmail-{{$user->id}}').removeClass('d-none');

                                tr.find('.userRole').prop('disabled', false);

                                tr.find('.confirmDeleteWorker').addClass('d-none');

                                tr.find('.save-update-info-{{$user->id}}').removeClass('d-none');
                                tr.find('.cancel-update-info-{{$user->id}}').removeClass('d-none');

                                sessionStorage.removeItem('userRole');
                            })

                            $(document).on('click', '.cancel-update-info-{{$user->id}}', async function() {
                                const tr = $(this).parent().parent();
                                $(this).addClass('d-none');
                                tr.find('.currentyName-{{$user->id}}').removeClass('d-none');
                                tr.find('.changeName-{{$user->id}}').addClass('d-none');

                                tr.find('.currentyEmail-{{$user->id}}').removeClass('d-none');
                                tr.find('.changeEmail-{{$user->id}}').addClass('d-none');

                                tr.find('.userRole').prop('disabled', true);

                                tr.find('.confirmDeleteWorker').removeClass('d-none');
                                tr.find('.update-info-{{$user->id}}').removeClass('d-none');

                                tr.find('.save-update-info-{{$user->id}}').addClass('d-none');
                            })

                            $(document).off('click', '.save-update-info-{{$user->id}}')
                            $(document).on('click', '.save-update-info-{{$user->id}}', async function() {
                                $(this).prop('disabled', true);

                                const tr = $(this).parent().parent();
                                const new_name = tr.find('.changeName-{{$user->id}} > input').val();
                                const new_email = tr.find('.changeEmail-{{$user->id}} > input').val();
                                const new_role = sessionStorage.getItem('userRole');
                                const new_department = sessionStorage.getItem('department');

                                try {
                                    const request = await fetch('{{route('udpateUser', ['email' => $user->email])}}', {
                                        method: 'PUT',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{csrf_token()}}'
                                        },
                                        body: JSON.stringify({
                                            new_name: new_name,
                                            new_email: new_email,
                                            new_role: new_role,
                                            new_department: '{{$chief_department}}'
                                        })
                                    });

                                    const response = await request.json();
                                    if(response.status === 200) {
                                        $(this).addClass('d-none');
                                        tr.find('.currentyName-{{$user->id}}').removeClass('d-none');
                                        tr.find('.currentyName-{{$user->id}} > p').text(new_name);

                                        tr.find('.changeName-{{$user->id}}').addClass('d-none');

                                        tr.find('.currentyEmail-{{$user->id}}').removeClass('d-none');
                                        tr.find('.currentyEmail-{{$user->id}} > p').text(new_email);

                                        tr.find('.changeEmail-{{$user->id}}').addClass('d-none');

                                        tr.find('.userRole').prop('disabled', true);

                                        if(new_role) {
                                            if(new_role == 1) {
                                                tr.find('.userRole').text('Manager')
                                            } else if(new_role == 2) {
                                                tr.find('.userRole').text('Chief')
                                            } else if(new_role == 3) {
                                                tr.find('.userRole').text('Teamlead')
                                            } else if(new_role == 4) {
                                                tr.find('.userRole').text('Employee')
                                            }
                                        }

                                        tr.find('.confirmDeleteWorker').removeClass('d-none');
                                        tr.find('.update-info-{{$user->id}}').removeClass('d-none');

                                        tr.find('.cancel-update-info-{{$user->id}}').addClass('d-none');

                                        toastr["success"](response.message)
                                    } else {
                                        toastr["error"](response.message)
                                    }
                                } catch(error) {
                                    toastr["error"](error)
                                    console.error(error)
                                }

                                $(this).prop('disabled', false);
                            })
                        })
                    </script>
                @endif
            @endforeach
            </tbody>
        </table>
    </div>
@elseif(Auth::user()->role == 3)
    <div id="users-table">
        <table class="table table-striped table-bordered table-sm bg-white">
            <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Email</th>
                <th scope="col">Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach($users as $user)
                @if($user->email !== Auth::user()->email)
                    <tr>
                        <td class="name-{{$user->id}}">
                            <form class="changeName-{{$user->id}} d-none">
                                <input type="text" value="{{$user->name}}" class="newTableName-{{$user->id}} form-control">
                            </form>

                            <form class="currentyName-{{$user->id}} mt-2" style="display: block;">
                                <p class="fw-bold p-name">{{$user->name}}</p>
                            </form>
                        </td>
                        <td class="email-{{$user->id}}" style="">
                            <form class="changeEmail-{{$user->id}} d-none">
                                <input type="text" value="{{$user->email}}" class="newTableEmail-{{$user->id}} form-control">
                            </form>

                            <form class="currentyEmail-{{$user->id}} mt-2" style="display: block;">
                                <p class="fw-bold p-email">{{$user->email}}</p>
                            </form>
                        </td>
                        <td>
                            <button type="button" class="update-info-{{$user->id}} btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16">
                                    <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                                </svg>
                            </button>
                            <span item_value="{{$user->email}}" class="confirmDeleteWorker btn btn-danger" data-bs-toggle="modal" data-bs-target="#bootModal">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6Z"/>
                                        <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1ZM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118ZM2.5 3h11V2h-11v1Z"/>
                                    </svg>
                                </span>

                            <button type="button" class="save-update-info-{{$user->id}} btn btn-success d-none">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 100 100">
                                    <circle cx="50" cy="50" r="40" stroke="white" stroke-width="5" fill="none" />
                                    <polyline points="35,50 45,60 65,40" stroke="white" stroke-width="5" fill="none" />
                                </svg>
                            </button>
                            <button type="button" class="cancel-update-info-{{$user->id}} btn btn-danger d-none">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 100 100">
                                    <line x1="30" y1="30" x2="70" y2="70" stroke="white" stroke-width="5" />
                                    <line x1="30" y1="70" x2="70" y2="30" stroke="white" stroke-width="5" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                    <script>
                        $(document).ready(() => {
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

                            $('.dropdown-menu-{{$user->id}}').on('submit', '.table-form-select-teamlead-{{$user->id}}', function(e) {
                                e.preventDefault();
                                $('.dropdown-menu-{{$user->id}}').html(
                                    `
                                            <li>
                                                <form class="table-form-select-employee-{{$user->id}}">
                                                    <button class="btn-select-{{$user->id}} dropdown-item" >Employee</button>
                                                </form>
                                            </li>
                                        `
                                )
                                $('.btn-group-{{$user->id}} > .userRole').text('Teamlead')

                                sessionStorage.setItem('userRole', 3);
                            })

                            $('.dropdown-menu-{{$user->id}}').on('submit', '.table-form-select-employee-{{$user->id}}', function(e) {
                                e.preventDefault();
                                $('.dropdown-menu-{{$user->id}}').html(
                                    `
                                            <li>
                                                <form class="table-form-select-teamlead-{{$user->id}}">
                                                    <button class="btn-select-{{$user->id}} dropdown-item" >Teamlead</button>
                                                </form>
                                            </li>
                                        `
                                )
                                $('.btn-group-{{$user->id}} > .userRole').text('Employee')

                                sessionStorage.setItem('userRole', 4);
                            })

                            $(document).off('click', '.update-info-{{$user->id}}')
                            $(document).on('click', '.update-info-{{$user->id}}', async function() {
                                const tr = $(this).parent().parent();
                                $(this).addClass('d-none');
                                tr.find('.currentyName-{{$user->id}}').addClass('d-none');
                                tr.find('.changeName-{{$user->id}}').removeClass('d-none');

                                tr.find('.currentyEmail-{{$user->id}}').addClass('d-none');
                                tr.find('.changeEmail-{{$user->id}}').removeClass('d-none');

                                tr.find('.confirmDeleteWorker').addClass('d-none');

                                tr.find('.save-update-info-{{$user->id}}').removeClass('d-none');
                                tr.find('.cancel-update-info-{{$user->id}}').removeClass('d-none');
                            })

                            $(document).on('click', '.cancel-update-info-{{$user->id}}', async function() {
                                const tr = $(this).parent().parent();
                                $(this).addClass('d-none');
                                tr.find('.currentyName-{{$user->id}}').removeClass('d-none');
                                tr.find('.changeName-{{$user->id}}').addClass('d-none');

                                tr.find('.currentyEmail-{{$user->id}}').removeClass('d-none');
                                tr.find('.changeEmail-{{$user->id}}').addClass('d-none');

                                tr.find('.confirmDeleteWorker').removeClass('d-none');
                                tr.find('.update-info-{{$user->id}}').removeClass('d-none');

                                tr.find('.save-update-info-{{$user->id}}').addClass('d-none');
                            })

                            $(document).off('click', '.save-update-info-{{$user->id}}')
                            $(document).on('click', '.save-update-info-{{$user->id}}', async function() {
                                $(this).prop('disabled', true);

                                const tr = $(this).parent().parent();
                                const new_name = tr.find('.changeName-{{$user->id}} > input').val();
                                const new_email = tr.find('.changeEmail-{{$user->id}} > input').val();

                                try {
                                    const request = await fetch('{{route('udpateUser', ['email' => $user->email])}}', {
                                        method: 'PUT',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{csrf_token()}}'
                                        },
                                        body: JSON.stringify({
                                            new_name: new_name,
                                            new_email: new_email,
                                            new_department: '{{$chief_department}}',
                                        })
                                    });

                                    const response = await request.json();
                                    if(response.status === 200) {
                                        $(this).addClass('d-none');
                                        tr.find('.currentyName-{{$user->id}}').removeClass('d-none');
                                        tr.find('.currentyName-{{$user->id}} > p').text(new_name);

                                        tr.find('.changeName-{{$user->id}}').addClass('d-none');

                                        tr.find('.currentyEmail-{{$user->id}}').removeClass('d-none');
                                        tr.find('.currentyEmail-{{$user->id}} > p').text(new_email);

                                        tr.find('.changeEmail-{{$user->id}}').addClass('d-none');

                                        tr.find('.confirmDeleteWorker').removeClass('d-none');
                                        tr.find('.update-info-{{$user->id}}').removeClass('d-none');

                                        tr.find('.cancel-update-info-{{$user->id}}').addClass('d-none');

                                        toastr["success"](response.message)
                                    } else {
                                        toastr["error"](response.message)
                                    }
                                } catch(error) {
                                    toastr["error"](error)
                                    console.error(error)
                                }

                                $(this).prop('disabled', false);
                            })
                        })
                    </script>
                @endif
            @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="mt-2">{{ $users->links('vendor.pagination.bootstrap-5') }}</div>

@section('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src='{{asset('/js/roles/usersPagination.js')}}' type='module'></script>
@endsection
