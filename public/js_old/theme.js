function bg()
{
    let img = document.getElementById("main-logo-img");
    let src1 = "/materials/images/workfitdxr_logo_1.png"
    let src2 = "/materials/images/workfitdxr_logo_2.png"
    let groupCheck = Array.from(document.getElementsByName('xxx'))
    let sepCheck = document.getElementById('xxx2');
    let sideText = document.getElementById('sideTextTheme');

    // groupCheck[0].onchange = () => {
    //     if (groupCheck[0].checked) {
    //         sepCheck.checked = true;
    //     } else {
    //         sepCheck.checked = false;
    //     }
    // }
    //
    // sepCheck.onchange = () => {
    //     if (sepCheck.checked) {
    //             groupCheck[0].checked = true;
    //     } else {
    //             groupCheck[0].checked = false;
    //     }
    // }
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


    console.log('SideBar: ', sepCheck.checked);
    console.log('NavBar: ', groupCheck[0].checked);

    function lightTheme() {
        sideText.textContent = 'White theme'

        localStorage.removeItem("footer-dashboard-content")
        localStorage.removeItem("footer-dashboard-main")
        localStorage.removeItem("modalChangePassword")
        localStorage.removeItem("home-container")
        localStorage.removeItem("navbar-main-dashboard")
        localStorage.removeItem("nav-dashboard-title-text")
        localStorage.removeItem("nav-name-text-hi")
        localStorage.removeItem("nav-name-text")
        localStorage.removeItem("nav-d-text-theme-w-color")
        localStorage.removeItem("nav-d-text-theme-w-weight")
        localStorage.removeItem("nav-d-text-theme-d-color")
        localStorage.removeItem("nav-d-text-theme-d-weight")
        localStorage.removeItem("home-h-title")
        localStorage.removeItem("path-satisfaction")
        localStorage.removeItem("box1-left-text")
        localStorage.removeItem("box1-right-text")
        localStorage.removeItem("box2-title")
        localStorage.removeItem("box3-title")
        localStorage.removeItem("sidebar-button-on-text")
        localStorage.removeItem("checked")
        localStorage.removeItem("img")

        $(".footer-dashboard-content").css("background-color", "black");
        localStorage.setItem("footer-dashboard-content", document.querySelector(".footer-dashboard-content").style.backgroundColor);
        $(".footer-dashboard-main").css("background-color", "black");
        localStorage.setItem("footer-dashboard-main", document.querySelector(".footer-dashboard-main").style.backgroundColor);
        $(".modalChangePassword > .modal-content").css("background-color", "rgba(254, 254, 254, 0.95)");
        localStorage.setItem("modalChangePassword", document.querySelector(".modalChangePassword > .modal-content").style.backgroundColor);
        $(".home-container").css("background-color", "#f1efef");
        localStorage.setItem("home-container", document.querySelector(".home-container").style.backgroundColor);
        $(".navbar-main-dashboard").css("background-color", "#FFFFFF");
        localStorage.setItem("navbar-main-dashboard", document.querySelector(".navbar-main-dashboard").style.backgroundColor);
        $(".nav-dashboard-title-text").css("color", "black");
        localStorage.setItem("nav-dashboard-title-text", document.querySelector(".nav-dashboard-title-text").style.color);
        $(".nav-name-text-hi").css("color", "black");
        localStorage.setItem("nav-name-text-hi", document.querySelector(".nav-name-text-hi").style.color);
        $(".nav-name-text").css("color", "black");
        localStorage.setItem("nav-name-text", document.querySelector(".nav-name-text").style.color);
        $(".nav-d-text-theme-w").css(
            {
                "color": "black",
                "font-weight": "bold"
            });
        localStorage.setItem("nav-d-text-theme-w-color", document.querySelector(".nav-d-text-theme-w").style.color);
        localStorage.setItem("nav-d-text-theme-w-weight", document.querySelector(".nav-d-text-theme-w").style.fontWeight);
        $(".nav-d-text-theme-d").css(
            {
                "color": "black",
                "font-weight": "normal"
            });
        document.querySelector(".nav-d-text-theme-d") !== null ? localStorage.setItem("nav-d-text-theme-d-color", document.querySelector(".nav-d-text-theme-d").style.color) : false;
        localStorage.setItem("nav-d-text-theme-d-weight", document.querySelector(".nav-d-text-theme-d").style.fontWeight);
        $(".home-h-title").css("color", "black");
        localStorage.setItem("home-h-title", document.querySelector(".home-h-title").style.color);
        $(".path-satisfaction").css("fill", "black");
        localStorage.setItem("path-satisfaction", document.querySelector(".path-satisfaction").style.fill);
        $(".box1-left-text").css("color", "black");
        localStorage.setItem("box1-left-text", document.querySelector(".box1-left-text").style.color);
        $(".box1-right-text").css("color", "black");
        localStorage.setItem("box1-right-text", document.querySelector(".box1-right-text").style.color);
        $(".box2-title").css("color", "black");
        localStorage.setItem("box2-title", document.querySelector(".box2-title").style.color);
        $(".box3-title").css("color", "black");
        localStorage.setItem("box3-title", document.querySelector(".box3-title").style.color);
        $(".sidebar-button-on-text").css("color", "black");
        localStorage.setItem("sidebar-button-on-text", document.querySelector(".sidebar-button-on-text").style.color);
        $("#xxx").attr("checked", false);
        $("#xxx2").attr("checked", false);
        img.src = src1;
        localStorage.setItem("img", "/materials/images/workfitdxr_logo_1.png")
    }

    function darkTheme() {
        sideText.textContent = 'Dark theme'

        localStorage.removeItem("footer-dashboard-content")
        localStorage.removeItem("footer-dashboard-main")
        localStorage.removeItem("modalChangePassword")
        localStorage.removeItem("home-container")
        localStorage.removeItem("navbar-main-dashboard")
        localStorage.removeItem("nav-dashboard-title-text")
        localStorage.removeItem("nav-name-text-hi")
        localStorage.removeItem("nav-name-text")
        localStorage.removeItem("nav-d-text-theme-w-color")
        localStorage.removeItem("nav-d-text-theme-w-weight")
        localStorage.removeItem("nav-d-text-theme-d-color")
        localStorage.removeItem("nav-d-text-theme-d-weight")
        localStorage.removeItem("home-h-title")
        localStorage.removeItem("path-satisfaction")
        localStorage.removeItem("box1-left-text")
        localStorage.removeItem("box1-right-text")
        localStorage.removeItem("box2-title")
        localStorage.removeItem("box3-title")
        localStorage.removeItem("sidebar-button-on-text")
        localStorage.removeItem("checked")
        localStorage.removeItem("img")

        $(".footer-dashboard-content").css("background-color", "white");
        localStorage.setItem("footer-dashboard-content", document.querySelector(".footer-dashboard-content").style.backgroundColor);
        $(".footer-dashboard-main").css("background-color", "white");
        localStorage.setItem("footer-dashboard-main", document.querySelector(".footer-dashboard-main").style.backgroundColor);
        $(".modalChangePassword > .modal-content").css("background-color", "rgba(0, 0, 0, 0.95)");
        localStorage.setItem("modalChangePassword", document.querySelector(".modalChangePassword > .modal-content").style.backgroundColor);
        $(".home-container").css("background-color", "black");
        localStorage.setItem("home-container", document.querySelector(".home-container").style.backgroundColor);
        $(".navbar-main-dashboard").css("background-color", "#292929");
        localStorage.setItem("navbar-main-dashboard", document.querySelector(".navbar-main-dashboard").style.backgroundColor);
        $(".nav-dashboard-title-text").css("color", "#FFFFFF");
        localStorage.setItem("nav-dashboard-title-text", document.querySelector(".nav-dashboard-title-text").style.color);
        $(".nav-name-text-hi").css("color", "#FFFFFF");
        localStorage.setItem("nav-name-text-hi", document.querySelector(".nav-name-text-hi").style.color);
        $(".nav-name-text").css("color", "#FFFFFF");
        localStorage.setItem("nav-name-text", document.querySelector(".nav-name-text").style.color);
        $(".nav-d-text-theme-w").css(
            {
                "color": "#FFFFFF",
                "font-weight": "normal"
            });
        localStorage.setItem("nav-d-text-theme-w-color", document.querySelector(".nav-d-text-theme-w").style.color);
        localStorage.setItem("nav-d-text-theme-w-weight", document.querySelector(".nav-d-text-theme-w").style.fontWeight);
        $(".nav-d-text-theme-d").css(
            {
                "color": "#FFFFFF",
                "font-weight": "bold"
            });
        document.querySelector(".nav-d-text-theme-d") !== null ? localStorage.setItem("nav-d-text-theme-d-color", document.querySelector(".nav-d-text-theme-d").style.color) : false;
        localStorage.setItem("nav-d-text-theme-d-weight", document.querySelector(".nav-d-text-theme-d").style.fontWeight);
        $(".home-h-title").css("color", "#FFFFFF");
        localStorage.setItem("home-h-title", document.querySelector(".home-h-title").style.color);
        $(".path-satisfaction").css("fill", "#FFFFFF");
        localStorage.setItem("path-satisfaction", document.querySelector(".path-satisfaction").style.fill);
        $(".box1-left-text").css("color", "#FFFFFF");
        localStorage.setItem("box1-left-text", document.querySelector(".box1-left-text").style.color);
        $(".box1-right-text").css("color", "#FFFFFF");
        localStorage.setItem("box1-right-text", document.querySelector(".box1-right-text").style.color);
        $(".box2-title").css("color", "#FFFFFF");
        localStorage.setItem("box2-title", document.querySelector(".box2-title").style.color);
        $(".box3-title").css("color", "#FFFFFF");
        localStorage.setItem("box3-title", document.querySelector(".box3-title").style.color);
        $(".sidebar-button-on-text").css("color", "#FFFFFF");
        localStorage.setItem("sidebar-button-on-text", document.querySelector(".sidebar-button-on-text").style.color);
        localStorage.setItem("checked", true);
        img.src = src2;
        localStorage.setItem("img", "../../materials/images/workfitdxr_logo_2.png");
    }
}
