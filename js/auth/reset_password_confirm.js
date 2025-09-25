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

        fetch(`https://empulse.workfitdx.com/users/resetPassword/confirm`, {
            method: 'POST',
            headers: {
                'Content-Type': "application/json",
                'X-CSRF-TOKEN': $("meta[name='csrf-token']").attr('content')
            },
            body: JSON.stringify({
                email: email,
                password: pass
            })
        })
            .then(response => response.json())
            .then(result => {
                if(result.status === 200) {
                    toastr["success"]("Your password reset!", "SUCCESS!")
                    setTimeout(() => { window.location = '/login'; }, 1500);
                } else {
                    toastr["error"](result.message, "ERROR!")
                }
            })
            .catch(error => {
                toastr["error"](error, "ERROR!")
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
