$(document).on("click", ".addNewCompany", function(e) {
    $('#bootModal').addClass('d-none').css('z-index', 0);
    e.preventDefault();
    $(".modal").css("display", "flex");
});
$(document).on("click", ".close", function(e) {
    e.preventDefault();
    $(".modal").css("display", "none");
});
var deleteCompanyName = null;
var deleteCompanyManagerName = null;
$(document).on('click', '.confirmDeleteCompany', function (){
    setTimeout(function (){$('#bootModal').removeClass('d-none').css('z-index', 10000);}, 250);
    deleteCompanyName = $(this).attr('item_value');

    if (deleteCompanyName) {
        $('#bootModal .modal-body').text('Do you really want to delete the company?');
        onCloseModal = function () {
            deleteCompanyName = null;
            $('#bootModal').addClass('d-none').css('z-index', 0);
        }
        onConfirmModal = function () {
            if (deleteCompanyName !== null && deleteCompanyName != '') {
                window.location.href = `/companies/delete/${deleteCompanyName}`;
            }
        }
        setModalListeners();
    } else {
        deleteCompanyName = null;
        $('#bootModal').addClass('d-none').css('z-index', 0);
    }
});
$(document).on('click', '.confirmDeleteManager', function (){
    setTimeout(function (){$('#bootModal').removeClass('d-none').css('z-index', 10000);}, 250);
    deleteCompanyManagerName = $(this).attr('item_value');

    if (deleteCompanyManagerName) {
        $('#bootModal .modal-body').text('Do you really want to delete the company manager?');
        onCloseModal = function () {
            deleteCompanyManagerName = null;
            $('#bootModal').addClass('d-none').css('z-index', 0);
        }
        onConfirmModal = function () {
            if (deleteCompanyManagerName !== null && deleteCompanyManagerName != '') {
                window.location.href = `/companies/delete/manager/${deleteCompanyManagerName}`;
            }
        }
        setModalListeners();
    } else {
        deleteCompanyManagerName = null;
        $('#bootModal').addClass('d-none').css('z-index', 0);
    }
});
$(document).on('click', '.sendAddNewCompanyForm', function () {
    let data = {
        title: $('[name="title"]').val().trim(),
        chief: $('[name="chief"]').val().trim(),
        email: $('[name="email"]').val().trim(),
    };

    if (data.title == ''){
        $('.addNewCompanyForm .alert').removeClass('d-none').text('Input title');
        return false;
    }
    if (data.chief == ''){
        $('.addNewCompanyForm .alert').removeClass('d-none').text('Input company manager name');
        return false;
    }
    if (data.email == '' || !validateEmail(data.email)){
        $('.addNewCompanyForm .alert').removeClass('d-none').text('Input correct email');
        return false;
    }

    $('.addNewCompanyForm').submit();
});

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
}