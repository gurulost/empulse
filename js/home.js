function validatePasswordFunc() {
    var password = document.getElementById("newPasswordInput"),
        password_confirm = document.getElementById("confirmNewPasswordInput");
    if(password && password_confirm) {
        function validatePassword() {
            if(password.value != password_confirm.value) {
                password_confirm.setCustomValidity("Passwords Don't Match");
            } else {
                password_confirm.setCustomValidity('');
            }
        }

        $(password).on("change", validatePassword);
        $(password_confirm).on("change", validatePassword);
    }
}
function first_step_validation() {
    const companyTitleInput = $(".first_step > input[name='company_title']");
    const newPasswordInput = $(".first_step > input[name='new_password']");
    const confirmPasswordInput = $(".first_step > input[name='confirm_password']");
    const buttonForConfirm = $(".buttonForConfirm");

    function disabledForm() {
        const companyTitleValue = companyTitleInput.val();
        const newPasswordValue = newPasswordInput.val();
        const confirmPasswordValue = confirmPasswordInput.val();

        if (companyTitleValue.length < 5 || newPasswordValue.length < 8 || confirmPasswordValue !== newPasswordValue) {
            buttonForConfirm.prop("disabled", true);
        } else {
            buttonForConfirm.prop("disabled", false);
        }
    }

    companyTitleInput.on("input", disabledForm);
    newPasswordInput.on("input", disabledForm);
    confirmPasswordInput.on("input", disabledForm);

    requestAnimationFrame(first_step_validation);
}
function action_clicks() {
    $(".departments-dropdown").on("click", function(e) {
        e.preventDefault();
        $(".dropdown-departments-modal").css({
            "display": "flex",
            "z-index": "10"
        })
        $(".box1-right-text").css({
            "z-index": "0"
        })
        $(".box-4").css({
            "z-index": "0"
        })
        $(".box1-btn-cards").css({
            "z-index": "1"
        })
        $(".satisfaction").css({
            "z-index": "0"
        })
    })

    $(".teams-dropdown").on("click", function(e) {
        e.preventDefault();
        $(".dropdown-teams-modal").css({
            "display": "flex",
            "z-index": "10"
        })
        $(".box1-right-text").css({
            "z-index": "0"
        })
        $(".box-4").css({
            "z-index": "0"
        })
        $(".box1-btn-cards").css({
            "z-index": "1"
        })
        $(".satisfaction").css({
            "z-index": "0"
        })
    })

    $('.departments-dropdown-iTemperature').on("click", function(e) {
        e.preventDefault();

        $(".dropdown-departments-modal-iTemperature").css({
            "display": "flex",
            "z-index": "10"
        })
        $(".box1-right-text").css({
            "z-index": "0"
        })
        $(".box-4").css({
            "z-index": "0"
        })
        $(".box1-btn-cards").css({
            "z-index": "1"
        })
        $(".satisfaction").css({
            "z-index": "0"
        })
    })

    $('.teams-dropdown-iTemperature').on("click", function(e) {
        e.preventDefault();
        $(".dropdown-teams-modal-iTemperature").css({
            "display": "flex",
            "z-index": "10"
        })
        $(".box1-right-text").css({
            "z-index": "0"
        })
        $(".box-4").css({
            "z-index": "0"
        })
        $(".box1-btn-cards").css({
            "z-index": "1"
        })
        $(".satisfaction").css({
            "z-index": "0"
        })
    })

    $(".close-dropdown").on("click", function(e) {
        e.preventDefault();
        $(".dropdown-departments-modal").css({
            "display": "none"
        })
        $(".dropdown-teams-modal").css({
            "display": "none"
        })
        $(".dropdown-departments-modal-iTemperature").css({
            "display": "none"
        })
        $(".dropdown-teams-modal-iTemperature").css({
            "display": "none"
        })
    })

    $(".btn-gapReport").on("click", (e) => {
        e.preventDefault();
        $("body").css("overflow-y", "hidden")
        $(".modal-gapReport").show(300, function()
        {
            /* ... */
        })
    })

    $(".btn-satisfactionIndicatorReport").on("click", (e) => {
        e.preventDefault();
        $("body").css("overflow-y", "hidden");
        $(".modal-satisfactionIndicatorReport").show(300, function()
        {
            /* ... */
        })
    })

    $(".btn-team").on("click", (e) => {
        e.preventDefault();
        $("body").css("overflow-y", "hidden")
        $(".modal-team").show(300, function()
        {
            /* ... */
        })
    })

    $(".close").on("click", function() {
        $("body").css("overflow-y", "auto")
        $(".modal-gapReport").hide(300, function()
        {
            /* ... */
        })
        $(".modal-satisfactionIndicatorReport").hide(300, function()
        {
            /* ... */
        })
        $(".modal-team").hide(300, function()
        {
            /* ... */
        })
    })

    $(".btn-satisfactionITemperatureIndex").on("click", function(e) {
        e.preventDefault();
        $(".modal-satisfactionITemperatureIndex").show(300, function()
        {
            $("body").css("overflow-y", "hidden");
            $(".company-modal").on("click", e => {
                e.preventDefault();

                $(".company-modal").css({
                    "background-color": "#ffff",
                    "color": "black",
                    "font-weight": "700",
                    "height": "60px",
                    "border": "1px solid #D1D1D1"
                });
                $(".department-modal").css({
                    "background-color": "#ECECEC",
                    "height": "48px",
                    "font-weight": "400",
                    "border": "1px solid #E3E3E3",
                    "color": "black"
                });
                $(".teams-modal").css({
                    "background-color": "#ECECEC",
                    "height": "48px",
                    "font-weight": "400",
                    "border": "1px solid #E3E3E3",
                    "color": "black"
                });

                $(".satisfaction-depatment-modal").css("display", "none");
                $(".satisfaction-company-modal").css("display", "block");
                $(".satisfaction-team-modal").css("display", "none");
            })

            $(".department-modal").on("click", e => {
                e.preventDefault();

                $(".company-modal").css({
                    "background-color": "#ECECEC",
                    "height": "48px",
                    "font-weight": "400",
                    "border": "1px solid #E3E3E3",
                    "color": "black"
                });
                $(".department-modal").css({
                    "background-color": "#ffff",
                    "color": "black",
                    "font-weight": "700",
                    "height": "60px",
                    "border": "1px solid #D1D1D1"
                });
                $(".teams-modal").css({
                    "background-color": "#ECECEC",
                    "height": "48px",
                    "font-weight": "400",
                    "border": "1px solid #E3E3E3",
                    "color": "black"
                });

                $(".satisfaction-depatment-modal").css("display", "block");
                $(".satisfaction-company-modal").css("display", "none");
                $(".satisfaction-team-modal").css("display", "none");
            })

            $(".teams-modal").on("click", e => {
                e.preventDefault();

                $(".company-modal").css({
                    "background-color": "#ECECEC",
                    "height": "48px",
                    "font-weight": "400",
                    "border": "1px solid #E3E3E3",
                    "color": "black"
                });
                $(".department-modal").css({
                    "background-color": "#ECECEC",
                    "height": "48px",
                    "font-weight": "400",
                    "border": "1px solid #E3E3E3",
                    "color": "black"
                });
                $(".teams-modal").css({
                    "background-color": "#ffff",
                    "color": "black",
                    "font-weight": "700",
                    "height": "60px",
                    "border": "1px solid #D1D1D1"
                });

                $(".satisfaction-team-modal").css("display", "block");
                $(".satisfaction-depatment-modal").css("display", "none");
                $(".satisfaction-company-modal").css("display", "none");
            })
            $(".close").on("click", function(e)
            {
                e.preventDefault();
                $(".modal-satisfactionITemperatureIndex").hide(300, function()
                {
                    $("body").css("overflow-y", "auto");
                })
            })
        })
    })

    $(".companyBubble-modal").on("click", e => {
        e.preventDefault();

        $(".companyBubble-modal").css({
            "background-color": "#ffff",
            "color": "black",
            "font-weight": "700",
            "height": "60px",
            "border": "1px solid #D1D1D1"
        });
        $(".departmentBubble-modal").css({
            "background-color": "#ECECEC",
            "height": "48px",
            "font-weight": "400",
            "border": "1px solid #E3E3E3",
            "color": "black"
        });
        $(".teamsBubble-modal").css({
            "background-color": "#ECECEC",
            "height": "48px",
            "font-weight": "400",
            "border": "1px solid #E3E3E3",
            "color": "black"
        });

        $(".teams-dropdown").css({
            "pointer-events": "none"
        })

        $(".departments-dropdown").css({
            "pointer-events": "none"
        })

        $(".bubble-department-modal").css("display", "none");
        $(".bubble-company-modal").css("display", "block");
        $(".bubble-team-modal").css("display", "none");
    })

    $(".departmentBubble-modal").on("click", e => {
        e.preventDefault();

        $(".departments-dropdown").css({
            "pointer-events": "auto"
        });

        $(".companyBubble-modal").css({
            "background-color": "#ECECEC",
            "height": "48px",
            "font-weight": "400",
            "border": "1px solid #E3E3E3",
            "color": "black"
        });
        $(".departmentBubble-modal").css({
            "background-color": "#ffff",
            "color": "black",
            "font-weight": "700",
            "height": "60px",
            "border": "1px solid #D1D1D1"
        });
        $(".teamsBubble-modal").css({
            "background-color": "#ECECEC",
            "height": "48px",
            "font-weight": "400",
            "border": "1px solid #E3E3E3",
            "color": "black"
        });

        $(".bubble-department-modal").css("display", "block");
        $(".bubble-company-modal").css("display", "none");
        $(".bubble-team-modal").css("display", "none");
    })

    $(".teamsBubble-modal").on("click", e => {
        e.preventDefault();

        $(".teams-dropdown").css({
            "pointer-events": "auto"
        });

        $(".companyBubble-modal").css({
            "background-color": "#ECECEC",
            "height": "48px",
            "font-weight": "400",
            "border": "1px solid #E3E3E3",
            "color": "black"
        });
        $(".departmentBubble-modal").css({
            "background-color": "#ECECEC",
            "height": "48px",
            "font-weight": "400",
            "border": "1px solid #E3E3E3",
            "color": "black"
        });
        $(".teamsBubble-modal").css({
            "background-color": "#ffff",
            "color": "black",
            "font-weight": "700",
            "height": "60px",
            "border": "1px solid #D1D1D1"
        });

        $(".bubble-team-modal").css("display", "block");
        $(".bubble-department-modal").css("display", "none");
        $(".bubble-company-modal").css("display", "none");
    })

    $(".companyBubble").on("click", e => {
        e.preventDefault();

        $(".companyBubble").addClass('active');
        $(".departmentBubble").removeClass('active');
        $(".teamsBubble").removeClass('active');

        $(".departments-dropdown").css({
            "pointer-events": "none"
        });

        $(".teams-dropdown").css({
            "pointer-events": "none"
        })

        // $(".companyBubble").css({
        //     "background-color": "#ffff",
        //     "color": "black",
        //     "font-weight": "700",
        //     "height": "60px",
        //     "border": "1px solid #D1D1D1"
        // });
        // $(".departmentBubble").css({
        //     "background-color": "#ECECEC",
        //     "height": "48px",
        //     "font-weight": "400",
        //     "border": "1px solid #E3E3E3",
        //     "color": "black"
        // });
        // $(".teamsBubble").css({
        //     "background-color": "#ECECEC",
        //     "height": "48px",
        //     "font-weight": "400",
        //     "border": "1px solid #E3E3E3",
        //     "color": "black"
        // });

        $(".bubble-department").css("display", "none");
        $(".bubble-company").css("display", "block");
        $(".bubble-team").css("display", "none");
    })

    $(".departmentBubble").on("click", e => {
        e.preventDefault();

        $(".companyBubble").removeClass('active');
        $(".departmentBubble").addClass('active');
        $(".teamsBubble").removeClass('active');

        $(".departments-dropdown").css({
            "pointer-events": "auto"
        });

        $(".teams-dropdown").css({
            "pointer-events": "none"
        })

        // $(".companyBubble").css({
        //     "background-color": "#ECECEC",
        //     "height": "48px",
        //     "font-weight": "400",
        //     "border": "1px solid #E3E3E3",
        //     "color": "black"
        // });
        // $(".departmentBubble").css({
        //     "background-color": "#ffff",
        //     "color": "black",
        //     "font-weight": "700",
        //     "height": "60px",
        //     "border": "1px solid #D1D1D1"
        // });
        // $(".teamsBubble").css({
        //     "background-color": "#ECECEC",
        //     "height": "48px",
        //     "font-weight": "400",
        //     "border": "1px solid #E3E3E3",
        //     "color": "black"
        // });

        $(".bubble-department").css("display", "block");
        $(".bubble-company").css("display", "none");
        $(".bubble-team").css("display", "none");
    })

    $(".teamsBubble").on("click", e => {
        e.preventDefault();

        $(".companyBubble").removeClass('active');
        $(".departmentBubble").removeClass('active');
        $(".teamsBubble").addClass('active');

        $(".teams-dropdown").css({
            "pointer-events": "auto"
        });

        $(".departments-dropdown").css({
            "pointer-events": "none"
        });

        $(".departments-dropdown").css({
            "pointer-events": "none"
        });

        // $(".companyBubble").css({
        //     "background-color": "#ECECEC",
        //     "height": "48px",
        //     "font-weight": "400",
        //     "border": "1px solid #E3E3E3",
        //     "color": "black"
        // });
        // $(".departmentBubble").css({
        //     "background-color": "#ECECEC",
        //     "height": "48px",
        //     "font-weight": "400",
        //     "border": "1px solid #E3E3E3",
        //     "color": "black"
        // });
        // $(".teamsBubble").css({
        //     "background-color": "#ffff",
        //     "color": "black",
        //     "font-weight": "700",
        //     "height": "60px",
        //     "border": "1px solid #D1D1D1"
        // });

        $(".bubble-team").css("display", "block");
        $(".bubble-department").css("display", "none");
        $(".bubble-company").css("display", "none");
    })

    $(".company").on("click", e => {
        e.preventDefault();

        $(".company").addClass('active');
        $(".department").removeClass('active');
        $(".teams").removeClass('active');

        // $(".company").css({
        //     "background-color": "#ffff",
        //     "color": "black",
        //     "font-weight": "700",
        //     "height": "60px",
        //     "border": "1px solid #D1D1D1"
        // });
        // $(".department").css({
        //     "background-color": "#ECECEC",
        //     "height": "48px",
        //     "font-weight": "400",
        //     "border": "1px solid #E3E3E3",
        //     "color": "black"
        // });
        // $(".teams").css({
        //     "background-color": "#ECECEC",
        //     "height": "48px",
        //     "font-weight": "400",
        //     "border": "1px solid #E3E3E3",
        //     "color": "black"
        // });

        $(".departments-dropdown-iTemperature").css({
            "pointer-events": "none"
        })

        $(".teams-dropdown-iTemperature").css({
            "pointer-events": "none"
        })

        $(".satisfaction-depatment").css("display", "none");
        $(".satisfaction-company").css("display", "block");
        $(".satisfaction-team").css("display", "none");
    })

    $(".department").on("click", e => {
        e.preventDefault();

        $(".company").removeClass('active');
        $(".department").addClass('active');
        $(".teams").removeClass('active');

        // $(".company").css({
        //     "background-color": "#ECECEC",
        //     "height": "48px",
        //     "font-weight": "400",
        //     "border": "1px solid #E3E3E3",
        //     "color": "black"
        // });
        // $(".department").css({
        //     "background-color": "#ffff",
        //     "color": "black",
        //     "font-weight": "700",
        //     "height": "60px",
        //     "border": "1px solid #D1D1D1"
        // });
        // $(".teams").css({
        //     "background-color": "#ECECEC",
        //     "height": "48px",
        //     "font-weight": "400",
        //     "border": "1px solid #E3E3E3",
        //     "color": "black"
        // });

        $(".departments-dropdown-iTemperature").css({
            "pointer-events": "auto"
        })

        $(".satisfaction-depatment").css("display", "block");
        $(".satisfaction-company").css("display", "none");
        $(".satisfaction-team").css("display", "none");
    })
        $(".teams-dropdown-iTemperature").css({
            "pointer-events": "none"
        })

            $(".teams-modal").on("click", e => {
                e.preventDefault();

                $(".company-modal").css({
                    "background-color": "#ECECEC",
                    "height": "48px",
                    "font-weight": "400",
                    "border": "1px solid #E3E3E3",
                    "color": "black"
                });
                $(".department-modal").css({
                    "background-color": "#ECECEC",
                    "height": "48px",
                    "font-weight": "400",
                    "border": "1px solid #E3E3E3",
                    "color": "black"
                });
                $(".teams-modal").css({
                    "background-color": "#ffff",
                    "color": "black",
                    "font-weight": "700",
                    "height": "60px",
                    "border": "1px solid #D1D1D1"
                });
        $(".satisfaction-depatment").css("display", "block");
        $(".satisfaction-company").css("display", "none");
        $(".satisfaction-team").css("display", "none");
    })

    $(".teams").on("click", e => {
        e.preventDefault();

        $(".company").removeClass('active');
        $(".department").removeClass('active');
        $(".teams").addClass('active');

        // $(".company").css({
        //     "background-color": "#ECECEC",
        //     "height": "48px",
        //     "font-weight": "400",
        //     "border": "1px solid #E3E3E3",
        //     "color": "black"
        // });
        // $(".department").css({
        //     "background-color": "#ECECEC",
        //     "height": "48px",
        //     "font-weight": "400",
        //     "border": "1px solid #E3E3E3",
        //     "color": "black"
        // });
        // $(".teams").css({
        //     "background-color": "#ffff",
        //     "color": "black",
        //     "font-weight": "700",
        //     "height": "60px",
        //     "border": "1px solid #D1D1D1"
        // });

        $(".departments-dropdown-iTemperature").css({
            "pointer-events": "none"
        })

        $(".teams-dropdown-iTemperature").css({
            "pointer-events": "auto"
        })

        $(".satisfaction-team").css("display", "block");
        $(".satisfaction-depatment").css("display", "none");
        $(".satisfaction-company").css("display", "none");
    })
}

$(document).ready(function () {
    action_clicks();
    first_step_validation();
    validatePasswordFunc();

    if(sessionStorage.getItem("satisfactionITemperature")) {
        $('#loaderGapReport').css("display", "none");
        $('#loaderITemperature').css("display", "none");
        $('#loaderIndicator').css("display", "none");
        $('#loaderTeam').css("display", "none");
    }
})



