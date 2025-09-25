<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" />
<div class="table-block">
    <table class="table table-striped table-bordered table-sm">
        <thead>
        <tr>
            <th scope="col" style="text-align: left; padding-left: 20px;">Department name</th>
            <th scope="col">Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($departments as $key => $department)
            <tr class="departmentTR" department_id="{{$department->id}}">
                <td class="title-{{$department->id}}" style="cursor: pointer; justify-content: left; align-items: center; padding-left: 20px"><p class="text-depart-table" >{{$department->title}}</p></td>
                <td class="title-{{$department->id}}-newTitle" style="display: none;">
                    <form class='form-{{$department->id}}' style="display: flex; align-content: center; justify-content: left">
                        <input type="text" class="form-control newTitle-{{$department->id}}" name="newTitle" style="max-width: 650px; height: 40px; max-height: 40px;" value="{{$department->title}}" placeholder="Max. 50 symbols">
                        <button class="btn btn-success ok" style="margin-left: 3px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check" viewBox="0 0 16 16">
                                <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                            </svg>
                        </button>
                        <a class="cancel-form-{{$department->id}} btn btn-secondary" style="margin-left: 3px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                            </svg>
                        </a>
                    </form>
                </td>
                <td>
                <span class="btn btn-primary editDepartment">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16">
                      <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                    </svg>
                </span>
                    <span item_value="{{$department->title}}" class="btn btn-danger confirmDeleteDepartment" data-bs-toggle="modal" data-bs-target="#bootModal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6Z"/>
                        <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1ZM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118ZM2.5 3h11V2h-11v1Z"/>
                    </svg>
                </span>
                </td>
            </tr>

            <script>
                var requestEnd = true;
                $(document).ready(() => {
                    $('.form-{{$department->id}}').off('submit').on('submit', function(e) {
                        if (!requestEnd){
                            return false;
                        }

                        requestEnd = false;
                        e.preventDefault();

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

                        var newTitle = $('.newTitle-{{$department->id}}').val();
                        $.ajax({
                            url: '/departments/update/{{$department->title}}',
                            method: 'POST',
                            data: {
                                '_token': '{{csrf_token()}}',
                                'newTitle': newTitle,
                            },
                            success: function(data) {
                                requestEnd = true;
                                if(data.status === 200) {
                                    var t = data.title;

                                    $('.title-{{$department->id}} p').text(t);
                                    $('.newTitle-{{$department->id}}').val(t);
                                    $('.title-{{$department->id}}-newTitle').hide();
                                    $('.title-{{$department->id}}').show();
                                    $('.editDepartment').prop('disabled', false);
                                }

                                else {
                                    console.log()
                                    if ($('#toast-container').length == 0){
                                        toastr["error"](data.message)
                                    }
                                }
                            },
                            error: function(data) {
                                requestEnd = true;
                                if ($('#toast-container').length == 0) {
                                    toastr["error"](data.message)
                                }
                            }
                        })
                    })
                })
            </script>
        @endforeach
        </tbody>
    </table>
</div>

<div class="pagination">{{$departments->links('vendor.pagination.bootstrap-5')}}</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
