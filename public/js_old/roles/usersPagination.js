var deleteWorkerEmail = null;
$(document).on('click', '.confirmDeleteWorker', function (){
    setTimeout(function (){$('#bootModal').removeClass('d-none').css('z-index', 10000);}, 250);
    deleteWorkerEmail = $(this).attr('item_value');
    // $(".modal-content").css("margin-left", "100px");
    if (deleteWorkerEmail) {
        $('#bootModal .modal-title').text('Delete employee');
        $('#bootModal .confirmModal').text('Delete');
        $('#bootModal .modal-body').text('Do you really want to delete the worker?');
        onCloseModal = function () {
            deleteWorkerEmail = null;
            $('#bootModal').addClass('d-none').css('z-index', 0);
        }
        onConfirmModal = function () {
            if (deleteWorkerEmail !== null && deleteWorkerEmail != '') {
                window.location.href = `/users/delete/${deleteWorkerEmail}`;
            }
        }
        setModalListeners();
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
