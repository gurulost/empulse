var deleteDepartmentName = null;
$(document).on('click', '.confirmDeleteDepartment', function (){
    setTimeout(function (){$('#bootModal').removeClass('d-none').css('z-index', 10000);}, 250);
    deleteDepartmentName = $(this).attr('item_value');

    if (deleteDepartmentName) {
        $('#bootModal .modal-body').text('Do you really want to delete the department?');
        onCloseModal = function () {
            deleteDepartmentName = null;
            $('#bootModal').addClass('d-none').css('z-index', 0);
        }
        onConfirmModal = function () {
            if (deleteDepartmentName !== null && deleteDepartmentName != '') {
                window.location.href = `/departments/delete/${deleteDepartmentName}`;
            }
        }
        setModalListeners();
    } else {
        deleteDepartmentName = null;
        $('#bootModal').addClass('d-none').css('z-index', 0);
    }
});

$(document).on('click', ".editDepartment", function(e) {
    e.preventDefault();
    let editDepartmentId = parseInt($(this).parents('.departmentTR').attr('department_id'));
    if (!isNaN(editDepartmentId)) {
        $(`.title-${editDepartmentId}`).css("display", "none");
        $(`.title-${editDepartmentId}-newTitle`).css("display", "block");

        $(`.cancel-form-${editDepartmentId}`).on("click", function (e) {
            e.preventDefault();
            $(`.title-${editDepartmentId}`).css("display", "block");
            $(`.title-${editDepartmentId}-newTitle`).css("display", "none");
        })
    }
})

$(document).ready(() => {
    $(".newTitle").on("input", () => {
        if($(".newTitle").val().replaceAll(" ", "").length == 0) {
            $(".ok").prop("disabled", true)
        } else {
            $(".ok").prop("disabled", false)
        }

    })

    $(".title").on("input", () => {
        if($(".title").val().replaceAll(" ", "").length == 0) {
            $(".add").prop("disabled", true)
        } else {
            $(".add").prop("disabled", false)
        }

    })
})

