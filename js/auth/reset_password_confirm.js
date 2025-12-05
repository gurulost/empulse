function isValidEmail(email) {
    var emailRegex = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;
    return emailRegex.test(email);
}

function form_validation() {
    const email = $("#email");
    const pass = $('#password');
    const confirm_pass = $('#password-confirm');
    const buttonForConfirm = $(".reset");

    function updateButtonState() {
        const email_value = email.val();
        const pass_value = pass.val();
        const confirm_pass_value = confirm_pass.val();

        if (!isValidEmail(email_value) || pass_value.replace(/\s/g, '').length < 8 || pass_value !== confirm_pass_value) {
            buttonForConfirm.prop("disabled", true);
        } else {
            buttonForConfirm.prop("disabled", false);
        }
    }

    email.on("input", updateButtonState);
    pass.on("input", updateButtonState);
    confirm_pass.on("input", updateButtonState);
}

function resetOldPassword() {
    $("#reset_old_password").submit((e) => {
        e.preventDefault();

        toastr.options = {
            "closeButton": true,
            "debug": true,
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
        }

        const email = $("#email").val();
        const pass = $('#password').val();
        const confirm_pass = $('#password-confirm').val();

        const token = window.location.pathname.split('/').pop();
        fetch('/password/reset', {
            method: 'POST',
            headers: {
                'Content-Type': "application/json",
                'X-CSRF-TOKEN': $("meta[name='csrf-token']").attr('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                email: email,
                password: pass,
                password_confirmation: confirm_pass,
                token: token
            })
        })
            .then(response => {
                const isSuccess = response.ok;
                return response.json().then(data => ({ isSuccess, data }));
            })
            .then(({ isSuccess, data }) => {
                if (isSuccess || data.status === 'passwords.reset') {
                    toastr["success"]("Your password has been reset!", "SUCCESS!")
                    setTimeout(() => { window.location = '/login'; }, 1500);
                } else {
                    const errorMsg = data.message || data.errors?.password?.[0] || data.errors?.email?.[0] || "Failed to reset password";
                    toastr["error"](errorMsg, "ERROR!")
                }
            })
            .catch(error => {
                toastr["error"]("An error occurred. Please try again.", "ERROR!")
                console.error(error)
            })
    })
}

function token_() {
    const token = window.location.href.split('/')[6];
    if(localStorage.getItem('token') !== null && localStorage.getItem('token') === token) {
        window.location = '/login';
    } else {
        localStorage.setItem('token', token);
    }
}

$(document).ready(function () {
    form_validation();
    resetOldPassword();
    token_();
});
