$(".sendMessageButton").on("click", () => {
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": false,
        "progressBar": true,
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
    };

    toastr["success"]("Your message sent to us!", "Success!");
})

function isValidPhoneNumber(input) {
    var phoneNumberPattern = /^\d{3}-\d{3}-\d{4}$/;
    return phoneNumberPattern.test(input);
}

function isValidEmail(email) {
    var emailRegex = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;
    return emailRegex.test(email);
}

function form_validation() {
    const name = $("input[name='name']");
    const email = $("input[name='email']");
    const phone = $("input[name='phone']");
    const buttonForConfirm = $(".sendMessageButton");

    function updateButtonState() {
        const name_value = name.val();
        const email_value = email.val();
        const phone_value = phone.val();

        if (name_value.length === 0 || !isValidEmail(email_value) || !isValidPhoneNumber(phone_value)) {
            buttonForConfirm.prop("disabled", true);
        } else {
            buttonForConfirm.prop("disabled", false);
        }
    }

    name.on("input", updateButtonState);
    email.on("input", updateButtonState);
    phone.on("input", updateButtonState);
}

form_validation();
