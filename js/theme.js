function checkingBackColor() {
    const btn = localStorage.getItem('checked');
    const img = localStorage.getItem("img");

    if(img !== null) {
        if(document.getElementById("main-logo-img")) {
            document.getElementById("main-logo-img").src = img
        }
    }

    if (btn == 'true') {
        $('.avatar-main').addClass('bg-black');
        $('.avatar-content').addClass(['bg-dark', 'text-white']);
        $('.payment-card').addClass('border-white').removeClass('border-dark');
        $('.contact-us-window').addClass(['bg-dark', 'text-white', 'border-white']);
        $('.contact-us-window').removeClass(['bg-white', 'text-dark', 'border-dark']);
        $('.contact-us-window .modal-body').addClass(['text-dark']);
        $('.avatar-header-title').addClass(['bg-dark', 'text-white']);
        $('.avatar-card').addClass(['border', 'border-white', 'rounded-0']);

        $(".card").addClass(['bg-dark', 'text-white']).removeClass(['bg-white', 'text-dark']);
        $("html").attr("data-bs-theme", "dark");
        $(".departmentsMainBlock").css("background-color", "black");
        $("#app").addClass('bg-black').removeClass('bg-white');

        $(".table").addClass("table-dark");
        $(".companyStaffBlock").addClass('bg-black').removeClass('bg-white');
        // $(".f-d-content-1").css({"color": "black"});
        // $(".f-d-content-2 > div > a").css({"color": "black"})
        // $(".footer-dashboard-content").css({"background-color": "white", "color": "black"});
        $(".footer-dashboard-content").css({"background-color": "black"})
        $(".footer-dashboard-main").css("background-color", "black");
        $(".profile-main").css({
            "background-color": "black"
        });
        $(".modalChangePassword > .modal-content").css("background-color", "rgba(0, 0, 0, 0.95)");
        $(".modalChangePassword > .modal-content").css("color", "white");
        $(".home-container").css("background-color", "rgb(0, 0, 0)");
        $(".navbar-main-dashboard").css("background-color", "#292929");
        $(".nav-dashboard-title-text").css("color", "#FFFFFF");
        $(".nav-name-text-hi").css("color", "#FFFFFF");
        $(".nav-name-text").css("color", "#FFFFFF");
        $(".nav-d-text-theme-w").html("Light theme");
        $(".nav-d-text-theme-w").css(
            {
                "color": "white",
                "font-weight": "normal"
            });
        $(".nav-d-text-theme-d").html("Dark theme");
        $(".nav-d-text-theme-d").css(
            {
                "color": "white",
                "font-weight": "bold"
            });
        $(".side-d-text-theme-s").html("Dark theme");
        $(".side-d-text-theme-s").css(
            {
                "color": "white",
                "font-weight": "bold"
            });
        $(".home-h-title").css("color", "#FFFFFF");
        $(".path-satisfaction").css("fill", "#FFFFFF");
        $(".box1-left-text").css("color", "#FFFFFF");
        $(".box1-right-text").css("color", "#FFFFFF");
        $(".box2-title").css("color", "#FFFFFF");
        $(".box3-title").css("color", "#FFFFFF");
        $(".sidebar-button-on-text").css("color", "#FFFFFF");
    } else {
        $('.payment-card').addClass('border-dark').removeClass('border-white');
        $('.contact-us-window').removeClass(['bg-dark', 'text-white', 'border-white']);
        $('.contact-us-window').addClass(['bg-white', 'text-dark', 'border-dark']);
        $('.contact-us-window .modal-body').addClass(['text-dark']);
        $('.avatar-header-title').removeClass(['bg-dark', 'text-white']);
        $('.avatar-card').removeClass(['border', 'border-white', 'rounded-0']);
        $('.avatar-content').removeClass(['bg-dark', 'text-white']);
        $('.avatar-main').removeClass('bg-black');
        $(".card").addClass(['bg-white', 'text-dark']).removeClass(['bg-dark', 'text-white']);

        $(".departmentsMainBlock").css("background-color", "white");
        $("#app").addClass('bg-white').removeClass('bg-black');
        $(".table").removeClass("table-dark");
        $(".companyStaffBlock").removeClass('bg-black').addClass('bg-white');
        $(".f-d-content-1").css({"color": "white"});
        $(".f-d-content-2 > div > a").css({"color": "white"})
        $(".footer-dashboard-content").css({"background-color": "black", "color": "white"});
        $(".footer-dashboard-main").css("background-color", "black");
        $(".profile-main").css({
            "background-color": "#E1E1E1"
        });
        $(".modalChangePassword > .modal-content").css("background-color", "rgba(254, 254, 254, 0.95)");
        $(".modalChangePassword > .modal-content").css("color", "black");
        $(".home-container").css("background-color", "#f1efef");
        $(".navbar-main-dashboard").css("background-color", "#FFFFFF");
        $(".nav-dashboard-title-text").css("color", "black");
        $(".nav-name-text-hi").css("color", "black");
        $(".nav-name-text").css("color", "black");
        $(".nav-d-text-theme-w").html("Light theme");
        $(".nav-d-text-theme-w").css(
            {
                "color": "black",
                "font-weight": "bold"
            });
        $(".nav-d-text-theme-d").html("Dark theme");
        $(".nav-d-text-theme-d").css(
            {
                "color": "black",
                "font-weight": "normal"
            });
        $(".side-d-text-theme-s").html("Light theme");
        $(".side-d-text-theme-s").css(
            {
                "color": "white",
                "font-weight": "normal"
            });
        $(".home-h-title").css("color", "black");
        $(".path-satisfaction").css("fill", "black");
        $(".box1-left-text").css("color", "black");
        $(".box1-right-text").css("color", "black");
        $(".box2-title").css("color", "black");
        $(".box3-title").css("color", "black");
        $(".sidebar-button-on-text").css("color", "black");
    }
}

window.addEventListener('storage', function(e) {
    if (e.key === 'checked' || e.key === 'img') {
        checkingBackColor();
    }
});

$(document).on('change', 'input[name="xxx"], #xxx2', function() {
    setTimeout(checkingBackColor, 50);
});

$(document).ready(function() {
    checkingBackColor();
    
    var pendingThemeUpdate = null;
    var observer = new MutationObserver(function(mutations) {
        if (pendingThemeUpdate) {
            clearTimeout(pendingThemeUpdate);
        }
        pendingThemeUpdate = setTimeout(function() {
            checkingBackColor();
            pendingThemeUpdate = null;
        }, 100);
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['class', 'style', 'data-bs-theme']
    });
    
    $(document).ajaxComplete(function() {
        checkingBackColor();
    });
    
    $(document).on('shown.bs.modal hidden.bs.modal shown.bs.collapse hidden.bs.collapse', function() {
        checkingBackColor();
    });
});

    // if (btn !== null) {
    //     $('head link[href="/css/theme-light.css"]').remove();
    //     $('head').append(`<link rel="stylesheet" href="/css/theme-dark.css" />`);
    // } else {
    //     $('head link[href="/css/theme-dark.css"]').remove();
    //     $('head').append(`<link rel="stylesheet" href="/css/theme-light.css" />`);
    // }
}

function bg() {
    let img = document.getElementById("main-logo-img");
    let src1 = "/materials/images/workfitdxr_logo_1.png"
    let src2 = "/materials/images/workfitdxr_logo_2.png"
    let groupCheck = Array.from(document.getElementsByName('xxx'))
    let sepCheck = document.getElementById('xxx2');
    let sideText = document.getElementById('sideTextTheme');

    groupCheck[0].addEventListener('change', syncButtons1);
    sepCheck.addEventListener('change', syncButtons2);

    function syncButtons1() {
        if (groupCheck[0].checked) {
            sepCheck.checked = true;
            darkTheme();
        } else {
            sepCheck.checked = false;
            lightTheme();
        }
    }
    function syncButtons2() {
        if (sepCheck.checked) {
            groupCheck[0].checked = true;
            darkTheme();
        } else {
            groupCheck[0].checked = false;
            lightTheme();
        }
    }

    function lightTheme() {
        localStorage.removeItem("checked");
        img.src = src1;
        localStorage.setItem("img", "/materials/images/workfitdxr_logo_1.png");
        checkingBackColor();
    }

    function darkTheme() {
        localStorage.setItem("checked", true);
        img.src = src2;
        localStorage.setItem("img", "../../materials/images/workfitdxr_logo_2.png");
        checkingBackColor();
    }
}

// $(document).ready(() => {
    checkingBackColor();
// });

