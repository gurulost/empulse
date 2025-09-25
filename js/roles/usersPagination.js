var deleteWorkerEmail = null;
$(document).on('click', '.confirmDeleteWorker', function (){
    setTimeout(function (){$('#bootModal').removeClass('d-none').css('z-index', 10000);}, 250);
    deleteWorkerEmail = $(this).attr('item_value');
    parent = $(this).parent().parent();
    // $(".modal-content").css("margin-left", "100px");
    if (deleteWorkerEmail) {
        $('#bootModal .modal-title').text('Delete employee');
        $('#bootModal .confirmModal').text('Delete');
        $('#bootModal .modal-body').text('Do you really want to delete the worker?');
        $('#bootModal .modal-content').css('width', '100%');
        onCloseModal = function () {
            deleteWorkerEmail = null;
            $('#bootModal').addClass('d-none').css('z-index', 0);
        }
        $(".confirmModal").off('click');
        $(".confirmModal").click(async function () {
            $(this).prop('disabled', true);
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

            if (deleteWorkerEmail !== null && deleteWorkerEmail != '') {
                console.log(deleteWorkerEmail)
                try {
                    const request = await fetch(`/users/delete/${deleteWorkerEmail}`);
                    const response = await request.json();
                    if(response.status === 200) {
                        window.location.reload();
                    } else {
                        alert(response.message)
                        toastr["error"](response.message)
                    }
                } catch(error) {
                    toastr["error"](error)
                }

                $(this).prop('disabled', false);
            }
        })
        // setModalListeners();
    } else {
        deleteWorkerEmail = null;
        $('#bootModal').addClass('d-none').css('z-index', 0);
    }
});

$(document).on('click', '.inputNameEmailEdit', function (){
    console.log('hello');
    $('.inputNameEmailEdit').parent().show();
    $('.inputNameEmailEdit').parents('td').find('form').hide();
    $(this).parents('td').find('form').css('display', 'block');
    $(this).parent().hide();
});
$(document).on('click', '.inputEmailEdit', function () {

});

function isValidEmail(email) {
    var emailRegex = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;
    return emailRegex.test(email);
}

function form_validation() {
    const name = $("form > input[name='name']");
    const email = $("form > input[name='email']");
    const role = $("form > input[type='radio']");
    const buttonForConfirm = $("form > .updateUserData");
    const department = $("#department");

    function updateButtonState() {
        const name_value = name.val();
        const email_value = email.val();
        const role_value = role ? role.is(":checked") : true;

        if(name_value && email_value) {
            if (name_value.length > 5 && isValidEmail(email_value) && role_value) {
                buttonForConfirm.prop("disabled", false);
            } else {
                buttonForConfirm.prop("disabled", true);
            }
        }
    }

    name.on("input", updateButtonState);
    email.on("input", updateButtonState);
    if(department) {department.on("change", updateButtonState)};
    if(role) { role.on("change", updateButtonState); };
}

form_validation();

function clear_update_form() {
    $(".update").click(() => {
        $("form > input[name='name']").attr("placeholder", "Min. 5 symbols");
        $("form > input[name='email']").attr("placeholder", "Use only email");

        $("form > input[type='radio']").prop("checked", false);
        $("form > .departments").css({"display": "none"});
        $("form > .updateUserData").prop("disabled", true);
    })
}

clear_update_form();

function cutTextIfTooLong(className) {
    var cells = $('.' + className);

    cells.each(function () {
        var $cell = $(this);
        var text = $cell.text();

        if (text.length > 25) {
            text = text.substring(0, 22) + '...';
            $cell.text(text);
        }
    });

    requestAnimationFrame(function () {
        cutTextIfTooLong(className);
    });
}

cutTextIfTooLong('p-name');
cutTextIfTooLong('p-email');

window.onload = () => {
    $('.table-responsive').removeClass('d-none').addClass('d-block');
}

