let button = document.querySelector(".resetPasswordButton");
button.disabled = true;
document.addEventListener("input", ifEmpty);

function ifEmail(email) {
    var emailRegex = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;
    return emailRegex.test(email);
}

function ifEmpty() {
    if(!ifEmail(document.getElementById("email").value)) {
        button.disabled = true;
        button.style.background = "grey";
        button.style.border = "0px solid grey";
        button.style.color = "white";
    } else {
        button.disabled = false;
        button.style.background = "#F2DE4C";
        button.style.border = "0px solid #F2DE4C";
        button.style.color = "black";
    }
}

$("form").submit(async (e) => {
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

    var email = document.getElementById("email").value;
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    var request = await fetch('/password/email', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ email: email })
    });
    var response = await request.json();

    if(request.ok || response.status === 'We have emailed your password reset link.') {
        toastr["success"]("Message sent to your email!", "SUCCESS!")
        setTimeout(() => { window.location = '/login'; }, 1500);
    } else {
        toastr["error"](response.message || response.email?.[0] || "Failed to send reset email", "WARNING!")
    }
})
