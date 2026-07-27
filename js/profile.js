function isValidEmail(email) {
    var emailRegex = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;
    return emailRegex.test(email);
}

function updateData() {
    $(".form-editPassword").on("submit", async (e) => {
        e.preventDefault();

        toastr.options = {
            "closeButton": false,
            "debug": false,
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

        const headers = {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": $("meta[name='csrf-token']").attr("content")
        }

        const data = {
            name: $(".name").val(),
            email: isValidEmail($('.email').val()) ? $('.email').val() : '',
            current_password: $('.current_password').val(),
            new_pass: $('.new_pass').val(),
            conf_new_pass: $('.conf_new_pass').val()
        }

        const row = {
            method: "POST",
            headers: headers,
            body: JSON.stringify(data)
        }

        try {
            const request = await fetch("/profile/edit_password", row);
            const response = await request.json();

            if(request.ok && response.status === 200) {
                toastr["success"](response.message, "SUCCESS")
                setTimeout(() => { window.location.reload(); }, 2500);
            } else {
                const validationMessage = response.errors
                    ? Object.values(response.errors).flat()[0]
                    : response.message;
                toastr["warning"](validationMessage || 'Unable to update your profile.', "WARNING")
            }
        } catch(error) {
            toastr["error"](error, "ERROR")
            console.error(error);
        }
    })
}

function form_validation() {
    const companyTitle = $(".company_title");
    const name = $(".name");
    const email = $(".email");
    const current_password = $(".current_password");
    const new_pass = $(".new_pass");
    const conf_new_pass = $(".conf_new_pass");
    const buttonForConfirm = $(".form-confirm-pass-btn");

    function disabledForm() {
        const new_pass_value = new_pass.val() || '';
        const conf_new_pass_value = conf_new_pass.val() || '';
        const current_password_value = current_password.val() || '';
        const name_value = name.val() || '';
        const email_value = email.val() || '';


        if(companyTitle.length > 0) {
            const companyTitle_value = companyTitle.val() || '';

            if (companyTitle_value.replace(/\s/g, "").length === 0 && name_value.replace(/\s/g, "").length === 0 && email_value.replace(/\s/g, "").length === 0 && current_password_value.replace(/\s/g, "").length === 0 && new_pass_value.replace(/\s/g, "").length === 0 && conf_new_pass_value.replace(/\s/g, "").length === 0) {
                buttonForConfirm.prop("disabled", true);
            } else {
                buttonForConfirm.prop("disabled", false);
            }
        } else {
            if (name_value.replace(/\s/g, "").length === 0 && email_value.replace(/\s/g, "").length === 0 && current_password_value.replace(/\s/g, "").length === 0 && new_pass_value.replace(/\s/g, "").length === 0 && conf_new_pass_value.replace(/\s/g, "").length === 0) {
                buttonForConfirm.prop("disabled", true);
            } else {
                buttonForConfirm.prop("disabled", false);
            }
        }
    }

    conf_new_pass.on("input", disabledForm);
    new_pass.on("input", disabledForm);
    current_password.on("input", disabledForm);
    name.on("input", disabledForm);
    email.on("input", disabledForm);
    if (companyTitle.length > 0) { companyTitle.on("input", disabledForm); }
}

function emptyFields() {
    const new_pass = $(".new_pass");
    const conf_new_pass = $(".conf_new_pass");
    const current_password = $(".current_password");

    new_pass.val('');
    conf_new_pass.val('');
    current_password.val('');
}

form_validation();
updateData();
emptyFields();
