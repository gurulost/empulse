@extends('layouts.app')

@section('title')
    Hello, {{Auth::user()->name}}!
@endsection

@section('content')
    <!-- USERS DATA -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" />
    <link rel="stylesheet" href="/public/css/admin.css" />
    <div class="companyStaffBlock mt-5 bg-white text-center h-100" style="padding-top: 20px">
        <div class="row table-main-block" >
            <div class="main-buttons d-flex align-items-center justify-content-center mt-5">
                <button type="button" class="btn btn-primary addNewWorkerModalWindow m-2" data-bs-toggle="modal" data-bs-target="#exampleModal">
                    Add new worker
                </button>
                <button type="button" class="btn btn-primary importWorkers m-2" data-bs-toggle="modal" data-bs-target="#importWorkersModal">
                    Import workers
                </button>
                <div class="modal fade" id="importWorkersModal" tabindex="-1" aria-labelledby="importWorkersModal" aria-hidden="true" style="margin-left: 3%; height: 100%; margin-top: 0">
                    <div class="modal-dialog" style="margin-top: 10%">
                        <div class="modal-content" style="height: 100%">
                            <div class="modal-header">
                                <h4 class="modal-title fs-5" id="exampleModalLabel">Import new workers</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <a href="/users/export/{{Auth::user()->role}}" type="button" class="btn btn-primary saveTableExample">Save table example</a>

                                <div class="mt-3 mb-3">
                                    <label for="importUsers" class="form-label">Input file (.xlsx <span class="text-danger">*</span>)</label>
                                    <input type="file" name="file" class="importUsers form-control" id="importUsers">
                                </div>
                                <button class="btn btn-primary importUsersSubmit">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        @if(Auth::user()->role == 0)@endif

        <div class="row table-main-block user-table">
            @if(Auth::user()->role != 0)
                <div class="container mb-3">
                    <div class="card">
                        <div class="card-body d-flex flex-wrap gap-2 align-items-end">
                            <div class="me-2">
                                <label class="form-label mb-1">Search</label>
                                <input type="text" id="filter-q" class="form-control" placeholder="Name or email">
                            </div>
                            @if(Auth::user()->role == 1)
                                <div class="me-2">
                                    <label class="form-label mb-1">Role</label>
                                    <select id="filter-role" class="form-select">
                                        <option value="">All</option>
                                        <option value="1">Manager</option>
                                        <option value="2">Chief</option>
                                    </select>
                                </div>
                                <div class="me-2">
                                    <label class="form-label mb-1">Department</label>
                                    <select id="filter-department" class="form-select">
                                        <option value="">All</option>
                                        @if(isset($departments))
                                            @foreach($departments as $d)
                                                <option value="{{ $d->title }}">{{ $d->title }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            @elseif(Auth::user()->role == 2)
                                <div class="me-2">
                                    <label class="form-label mb-1">Role</label>
                                    <select id="filter-role" class="form-select">
                                        <option value="">All</option>
                                        <option value="3">Teamlead</option>
                                        <option value="4">Employee</option>
                                    </select>
                                </div>
                            @endif
                            <div class="ms-auto d-flex align-items-center gap-2">
                                <div id="users-loading" class="spinner-border spinner-border-sm text-secondary d-none" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <button id="filter-clear" class="btn btn-outline-secondary">Clear</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <div class="pricing-header p-3 pb-md-4 mx-auto text-center">
                <section>
                    <div class="table-responsive d-none">
                        @include('roles.usersPagination')
                    </div>
                </section>
            </div>
        </div>
    </div>

    @if(Auth::user()->role !== 0)
        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true" style="position: fixed; top: 7%; left: 0; margin-left: 3%; height: 100%; margin-top: 0">
            <div class="modal-dialog">
                <div class="modal-content" style="height: 100%">
                    <div class="modal-header">
                        <h4 class="modal-title fs-5" id="exampleModalLabel">Add new worker</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form class="add-worker-form" novalidate>
                            <label><b>Name:</b>
                                <input type="text" placeholder="Min. 5 symbols" class="form-control add-worker-form-name" required>
                                <div class="form-text text-danger name-helper">* This field is required (minimum 5 symbols)</div>
                            </label><br />
                            <label class="mb-3"><b>Email:</b>
                                <input type="email" placeholder="Use only email" class="form-control add-worker-form-email" required>
                                <div class="form-text text-danger email-helper">* This field is required (use only email address)</div>
                            </label><br />
{{--                            @if(\Auth::user()->role !== 3)--}}
{{--                                <label style="margin-bottom: 20px;"><b>Password:</b>--}}
{{--                                    <input type="password" placeholder="Min. 8 symbols" class="form-control add-worker-form-password" required>--}}
{{--                                    <div class="form-text text-danger password-helper">* This field is required (minimum 8 symbols)</div>--}}
{{--                                </label>--}}
{{--                            @endif--}}

                            @if(Auth::user()->role == 1)
                                <label class="form-label">Department chief: </label>
                                <input type="radio" name="status" value="0" class="chief-add"><br />

                                <div class="departments" style="display: none; height:70px">
                                    <label class="form-label">Choose department for department chief: </label><br>
                                    <select class='form-select' id="departmentForCard">
                                        <option value="" selected>None</option>
                                        @foreach($departments as $department)
                                            <option value="{{$department->title}}">{{$department->title}}</option>
                                        @endforeach
                                    </select>
                                    <br /><br />
                                </div>

                                <label class="form-label">Company manager: </label>
                                <input type="radio" name="status" value="1" class="manager-add" checked><br />
                                <script>
                                    $(".chief-add").on("click", function()
                                    {
                                        $(".departments").css("display", "block");
                                    })

                                    $(".manager-add").on("click", function()
                                    {
                                        $(".departments").css("display", "none");
                                    })
                                </script>
                                <div style="display: none;">
                                    <label class="form-label">Teamlead: </label>
                                    <input type="radio" name="status" value="0" class="teamlead-add"><br />
                                    <label class="form-label">Employee: </label>
                                    <input type="radio" name="status" value="0" class="employee-add" style="margin-bottom: 20px;"><br />
                                </div>
                            @elseif(Auth::user()->role == 2)
                                <div style="display: none;">
                                    <label class="form-label">Department chief: </label>
                                    <input type="radio" name="status" value="0" class="chief-add"><br />

                                    <div class="departments" style="display: none; height:70px">
                                        <label class="form-label">Choose department for department chief: </label><br>
                                        <select id="departmentForCard" style="margin: 5px 5px 5px 0px; width: 290px; height: 30px;">
                                            <option value="" selected>None</option>
                                            @foreach($departments as $department)
                                                <option value="{{$department->title}}">{{$department->title}}</option>
                                            @endforeach
                                        </select>
                                        <br />
                                    </div>

                                    <label class="form-label">Company manager: </label>
                                    <input type="radio" name="status" value="1" class="manager-add" checked><br />
                                </div>
                                <div style="display: block;">
                                    <label class="form-label">Teamlead: </label>
                                    <input type="radio" name="status" value="1" checked class="teamlead-add"><br />
                                    <label class="form-label">Employee: </label>
                                    <input type="radio" name="status" value="0" class="employee-add" style="margin-bottom: 20px;"><br />
                                    <input id="departmentForCard" type="text" value="" style="margin: 5px 5px 5px 0px; display: none;">
                                </div>
                            @elseif(Auth::user()->role == 3)
                                <br />
                            @endif
                            <button class="btn btn-primary addNewWorkerBtn" disabled>Add new worker</button>
                        </form>
{{--                        <hr />--}}
{{--                        <form method="POST" action="/users/import" enctype="multipart/form-data">--}}
{{--                            @csrf--}}
{{--                            <label><b>Upload file with employees:</b></label>--}}
{{--                            <input type="file" name="file" class="form-control" id="fileControl" style="margin-bottom: 20px;">--}}
{{--                            <a class="btn btn-primary" href="/export">Save table example</a>--}}
{{--                            <button class="btn btn-primary uploadFile" disabled>Upload</button>--}}
{{--                        </form>--}}
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- END USERS DATA -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        toastr.options = {
            "closeButton": false,
            "debug": false,
            "newestOnTop": false,
            "progressBar": false,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "10000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        }

        function alerts() {
            @if(\Session::has('error'))
                toastr.warning("Maybe, you used incorrect file, something doesn't similar on xlsx and csv? Or forgot to add the necessary columns to the table. Change the error and try again!", "Error!!!");
                {{Session::forget('error')}}
            @elseif(Session::has('non-insert'))
                var non_insert = "{{Session::get('non-insert')}}";
                var insert = "{{Session::get('insert')}}";
                var exist = non_insert > 1 ? 'exists' : 'exist';
                var user = non_insert > 1 ? 'users' : 'user';
                // toastr.warning(`${non_insert} of ${insert} ${user} didn't upload, because ${exist}!`);
                toastr.warning(`${non_insert} of ${insert} ${user} are already registered in the database and have not been added again!`);
                {{Session::forget('non-insert')}}
                {{Session::forget('insert')}}
            @elseif(\Session::has('import_error'))
                toastr.warning("{{Session::get('import_error')}}");
                {{Session::forget('import_error')}}
            @elseif(Session::has('insert') && !Session::has('non-insert'))
                {{Session::forget('insert')}}
            @endif
        }

        alerts();

        $(".add-worker-form").on("submit", function(e) {
            e.preventDefault();
            var buttonForm = $('.addNewWorkerBtn');
            buttonForm.prop('disabled', true);

            @if(\Auth::user()->role !== 3)
                var role = null;

                if($(".manager-add")[0].checked == true)
                {
                    $(".manager-add").val("yes");
                    $(".teamlead-add").val("no");
                    $(".employee-add").val("no");
                    $(".chief-add").val("no");

                    role = 1;
                }
                else if($(".teamlead-add")[0].checked == true)
                {
                    $(".teamlead-add").val("yes");
                    $(".manager-add").val("no");
                    $(".employee-add").val("no");
                    $(".chief-add").val("no");

                    role = 3;
                }
                else if($(".chief-add")[0].checked == true)
                {
                    $(".chief-add").val("yes");
                    $(".teamlead-add").val("no");
                    $(".manager-add").val("no");
                    $(".employee-add").val("no");

                    role = 2;
                }

                else
                {
                    $(".teamlead-add").val("no");
                    $(".manager-add").val("no");
                    $(".employee-add").val("yes");
                    $(".chief-add").val("no");

                    role = 4;
                }

                let name = $(".add-worker-form-name").val();
                let email = $(".add-worker-form-email").val();
                // let password = $(".add-worker-form-password").val().length == 0 ? "" : $(".add-worker-form-password").val();
                let department = $("#departmentForCard").val();

                if(department === '' ) {
                    if(role === 2) {
                        department = 'None department';
                    }
                }

                const formData = new FormData();
                formData.append("_token", "{{ csrf_token() }}");
                formData.append("name", name);
                formData.append("email", email);
                // formData.append("password", password);
                formData.append("role", role);
                formData.append("department", department);

                const requestOptions = {
                    method: "POST",
                    body: formData
                };

                fetch("/users", requestOptions)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 200) {
                            toastr["success"]('New worker added!', 'Success:')
                            setTimeout(() => {
                                window.location.reload()
                            }, 1500);
                        } else {
                            toastr["error"](data.message, 'Error:')
                            buttonForm.prop('disabled', false);
                        }
                    })
                    .catch(error => {
                        console.error('Ошибка:', error);
                        buttonForm.prop('disabled', false);
                    });
            @else
                const formData = new FormData();
                formData.append("_token", "{{ csrf_token() }}");
                formData.append("name", $(".add-worker-form-name").val());
                formData.append("email", $(".add-worker-form-email").val());
                formData.append("role", 4);
                formData.append("supervisor", "{{\Auth::user()->name}}");
                formData.append("department", "{{$teamlead_department}}");

                fetch("/users", {
                    method: "POST",
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 200) {
                            toastr["success"]('New worker added!', 'Success:');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            toastr["error"](data.message, 'Error:');
                            buttonForm.prop('disabled', false);
                        }
                    })
                    .catch(error => {
                        toastr["error"]('An error occurred while processing your request.', 'Error:');
                        buttonForm.prop('disabled', false);
                    });

            @endif
        })

        function isValidEmail(email) {
            var emailRegex = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;
            return emailRegex.test(email);
        }

        function form_validation() {
            const add_worker_form_name = $(".add-worker-form-name");
            const add_worker_form_email = $(".add-worker-form-email");
            // const add_worker_form_password = $(".add-worker-form-password");
            const input_radio = $('input[type="radio"]');
            const buttonForConfirm = $(".addNewWorkerBtn");

            var add_worker_form_name_value = null;
            var add_worker_form_email_value = null;
            // var add_worker_form_password_value = null;

            function disabledForm() {
                @if(Auth::user()->role == 1)
                    add_worker_form_name_value = add_worker_form_name.val();
                    add_worker_form_email_value = add_worker_form_email.val();
                    // add_worker_form_password_value = add_worker_form_password.val();
                    const atLeastOneRadioChecked = input_radio.is(":checked");

                    if (add_worker_form_name_value.length >= 5 && atLeastOneRadioChecked && isValidEmail(add_worker_form_email_value)) {
                        buttonForConfirm.prop("disabled", false);
                    } else {
                        buttonForConfirm.prop("disabled", true);
                    }
                @elseif(Auth::user()->role == 2)
                    var employeeRadio = $('.employee-add').is(':checked');
                    var emailBlock = $('.add-worker-form > label:eq(1)');

                    add_worker_form_name_value = add_worker_form_name.val();
                    add_worker_form_email_value = add_worker_form_email.val();

                    if (add_worker_form_name_value.length >= 5 && isValidEmail(add_worker_form_email_value)) {
                        buttonForConfirm.prop("disabled", false);
                    } else {
                        buttonForConfirm.prop("disabled", true);
                    }

                    // if(passwordBlock.is(':visible')) {
                    //     if (add_worker_form_name_value.length >= 5 && isValidEmail(add_worker_form_email_value)) {
                    //         buttonForConfirm.prop("disabled", false);
                    //     } else {
                    //         buttonForConfirm.prop("disabled", true);
                    //     }
                    // } else {
                    //     if (add_worker_form_name_value.length >= 5 && isValidEmail(add_worker_form_email_value)) {
                    //         buttonForConfirm.prop("disabled", false);
                    //     } else {
                    //         buttonForConfirm.prop("disabled", true);
                    //     }
                    // }

                    // function hidePasswordFunc() {
                    //     if(employeeRadio) {
                    //         passwordBlock.hide();
                    //         emailBlock.css('margin-bottom', '20px');
                    //     } else {
                    //         passwordBlock.show();
                    //         emailBlock.css('margin-bottom', '0px');
                    //     }
                    //
                    //     requestAnimationFrame(hidePasswordFunc)
                    // }
                    //
                    // hidePasswordFunc()
                @elseif(Auth::user()->role == 3)
                    add_worker_form_name_value = add_worker_form_name.val();
                    add_worker_form_email_value = add_worker_form_email.val();

                    if (add_worker_form_name_value.length >= 5 && isValidEmail(add_worker_form_email_value)) {
                        buttonForConfirm.prop("disabled", false);
                    } else {
                        buttonForConfirm.prop("disabled", true);
                    }
                @endif

                if($('.add_worker_form_name') !== null) {
                    if(add_worker_form_name_value !== null && add_worker_form_name_value.length >= 5) {
                        $('.name-helper').addClass('d-none');
                    }   else {
                        $('.name-helper').removeClass('d-none');
                    }
                }

                // if($('.add_worker_form_password') !== null) {
                //     if(add_worker_form_password_value !== null && add_worker_form_password_value.length >= 8) {
                //         $('.password-helper').addClass('d-none');
                //     } else {
                //         $('.password-helper').removeClass('d-none');
                //     }
                // }

                if($('.add_worker_form_email') !== null) {
                    if(add_worker_form_email_value !== null && isValidEmail(add_worker_form_email_value)) {
                        $('.email-helper').addClass('d-none');
                    } else {
                        $('.email-helper').removeClass('d-none');
                    }
                }
            }

            add_worker_form_name.on("input", disabledForm);
            add_worker_form_email.on("input", disabledForm);
            // add_worker_form_password.on("input", disabledForm);
            input_radio.on("change", disabledForm);
        }
        form_validation();
    </script>
    <script>
        // Filters: fetch via AJAX and replace the table
        const $tableWrapper = document.querySelector('.table-responsive');
        const $q = document.getElementById('filter-q');
        const $role = document.getElementById('filter-role');
        const $dep = document.getElementById('filter-department');
        const $clear = document.getElementById('filter-clear');

        let sortKey = 'name';
        let sortDir = 'asc';

        function currentParams() {
            const params = new URLSearchParams();
            if ($q && $q.value.trim() !== '') params.set('q', $q.value.trim());
            if ($role && $role.value) params.set('role', $role.value);
            if ($dep && $dep.value) params.set('department', $dep.value);
            if (sortKey) params.set('sort', sortKey);
            if (sortDir) params.set('dir', sortDir);
            return params;
        }

        async function fetchList(url = null) {
            try {
                const base = url || ('/users/list');
                const params = currentParams();
                const full = params.toString() ? `${base}?${params.toString()}` : base;
                // show loading
                const loading = document.getElementById('users-loading');
                if (loading) loading.classList.remove('d-none');
                const res = await fetch(full, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const html = await res.text();
                $tableWrapper.innerHTML = html;
                $tableWrapper.classList.remove('d-none');
                $tableWrapper.classList.add('d-block');
                if (loading) loading.classList.add('d-none');
                // highlight matches
                const term = ($q && $q.value) ? $q.value.trim() : '';
                if (term) {
                    const re = new RegExp(term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'ig');
                    document.querySelectorAll('.p-name, .p-email').forEach(el => {
                        const text = el.textContent;
                        el.innerHTML = text.replace(re, (m) => `<mark>${m}</mark>`);
                    });
                }
                // update sort indicators
                document.querySelectorAll('.js-sort').forEach(a => {
                    // remove previous indicator
                    a.querySelectorAll('.sort-indicator').forEach(n => n.remove());
                    const key = a.getAttribute('data-sort');
                    if (key === sortKey) {
                        const s = document.createElement('span');
                        s.className = 'sort-indicator';
                        s.textContent = sortDir === 'asc' ? ' ▲' : ' ▼';
                        a.appendChild(s);
                    }
                });
            } catch (e) { console.error(e); }
        }

        function debounce(fn, ms) {
            let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
        }

        if ($q) $q.addEventListener('input', debounce(() => fetchList(), 300));
        if ($role) $role.addEventListener('change', () => fetchList());
        if ($dep) $dep.addEventListener('change', () => fetchList());
        if ($clear) $clear.addEventListener('click', () => {
            if ($q) $q.value = '';
            if ($role) $role.value = '';
            if ($dep) $dep.value = '';
            fetchList();
        });

        // Intercept pagination clicks inside table wrapper and preserve filters
        document.addEventListener('click', function(e) {
            const a = e.target.closest('.pagination a');
            if (a && $tableWrapper.contains(a)) {
                e.preventDefault();
                const url = new URL(a.href, window.location.origin);
                fetchList(url.pathname + url.search);
            }
            const sorter = e.target.closest('.js-sort');
            if (sorter) {
                e.preventDefault();
                const key = sorter.getAttribute('data-sort');
                if (!key) return;
                if (sortKey === key) {
                    sortDir = (sortDir === 'asc') ? 'desc' : 'asc';
                } else {
                    sortKey = key;
                    sortDir = 'asc';
                }
                fetchList();
            }
        });
    </script>
@endsection
