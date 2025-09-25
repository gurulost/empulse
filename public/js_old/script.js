var onCloseModal = function (){}
var onConfirmModal = function (){}
function setModalListeners(){
    $(document).on('click', '#bootModal .closeModal', onCloseModal);
    $(document).on('click', '#bootModal .confirmModal', onConfirmModal);
}