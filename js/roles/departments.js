$(document).on('click', '.addNewDepartment', function (){
    $('[name="title"]').val('');
});

var deleteDepartmentName = null;
$(document).on('click', '.confirmDeleteDepartment', function (){
    setTimeout(function (){$('#bootModal').removeClass('d-none').css('z-index', 10000);}, 250);
    deleteDepartmentName = $(this).attr('item_value');

    if (deleteDepartmentName) {
        $('#bootModal .modal-title').text('Delete department');
        $('#bootModal .modal-body').text('Do you really want to delete the department?');
        $('#bootModal .modal-content').css('width', '100%');
        onCloseModal = function () {
            deleteDepartmentName = null;
            $('#bootModal').addClass('d-none').css('z-index', 0);
        }
        $(".confirmModal").click(function () {
            if (deleteDepartmentName !== null && deleteDepartmentName != '') {
                window.location.href = `/departments/delete/${deleteDepartmentName}`;
            }
        })
        // setModalListeners();
    } else {
        deleteDepartmentName = null;
        $('#bootModal').addClass('d-none').css('z-index', 0);
    }
});

$(document).on('click', ".editDepartment", function(e) {
    e.preventDefault();
    $(this).parents('tr').find('[name="newTitle"]').val($(this).parents('tr').find('.text-depart-table').text());
    $(".editDepartment").prop("disabled", true);
    let editDepartmentId = parseInt($(this).parents('.departmentTR').attr('department_id'));
    if (!isNaN(editDepartmentId)) {
        $(`.title-${editDepartmentId}`).css("display", "none");
        $(`.title-${editDepartmentId}-newTitle`).css("display", "block");

        $(`.cancel-form-${editDepartmentId}`).on("click", function (e) {
            e.preventDefault();
            $(`.title-${editDepartmentId}`).css("display", "block");
            $(`.title-${editDepartmentId}-newTitle`).css("display", "none");
            $(".editDepartment").prop("disabled", false);
        })
    }
})

function pagination() {
    $(document).on('click', '.page-link', function (e) {
        e.preventDefault();
        var url = $(this).attr('href').split('page=')[1];
        getArticles(url);
    })
}

function getArticles(url) {
    $.ajax({
        url: '/departments/list?page=' + url
    }).done(function (data) {
        $('#users-table').html(data);
    }).fail(function () {
        console.log('Somethin went wrong with pagination.');
    });
}

function createNewDepartment() {
    $(document).off('click', '.add');
    $(document).on('click', '.add', async function(e) {
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

        const title = $('.title').val();
        const csrf = $("meta[name='csrf-token']").attr('content');

        const headers = {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf
        }

        const raw = {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({
                title: title
            })
        }

        const request = await fetch('/departments', raw);
        const response = await request.json();

        if(response['status'] === 200) {
            window.location.reload();
        } else {
            toastr["error"](response['message']);
        }
    })
}

$(document).ready(() => {
    /*$(".newTitle").each(function(index) {
        const $newTitle = $(this);
        const $relatedOkButton = $(".ok").eq(index);

        $newTitle.on("input", () => {
            if ($newTitle.val().replaceAll(" ", "").length < 1 || $newTitle.val().length > 30) {
                $relatedOkButton.prop("disabled", true);
            } else {
                $relatedOkButton.prop("disabled", false);
            }
        });
    });


    $(".title").on("input", () => {
        if($(".title").val().replaceAll(" ", "").length < 1 || $(".title").val().length > 30) {
            $(".add").prop("disabled", true)
            if($(".title").val().length > 30) {
                $('.danger-alert').addClass('d-block').removeClass('d-none');
            }
        } else {
            $(".add").prop("disabled", false)
            $('.danger-alert').addClass('d-none').removeClass('d-block');
        }

    })*/

    pagination();
    createNewDepartment();
})

