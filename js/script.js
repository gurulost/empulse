var onCloseModal = function (){}
var onConfirmModal = function (){}
function setModalListeners(){
    $(document).on('click', '#bootModal .closeModal', onCloseModal);
    $(document).on('click', '#bootModal .confirmModal', onConfirmModal);
}
function checkSidebar(){
    if (window.location.pathname !== '/home'){
        $('.side-menu-workforce').css('background', 'none');
        $('.side-menu-workforce-text').css('color', 'white').css('font-weight', '400');
    }

    if (window.location.pathname === '/users' || window.location.pathname === '/companies'){
        $('main').css({
            'margin-left': '15%',
            'margin-right': '5%',
        });
    }
    if (window.location.pathname === '/payment'){
        $('main').css({
            'margin-left': '15%',
        });
    }
}
function sideMenu() {
    $(".side-menu-hide-menu-button").on("click", () =>
    {
        $(".sidebar-menu-main").css("display", "none");
        $(".sidebar-main").css({"display": "block", "z-index": "10000000000"});
        $("#nav-item-info").css("display", "block")
    })

    $(".sidebar-button-on-text").on("click", () =>
    {
        $(".sidebar-menu-main").css("display", "block");
        $(".sidebar-main").css("display", "none");
        $("#nav-item-info").css("display", "none")
    })
}

function actuallityDate() {
    const currentyYear = new Date().getFullYear();
    $('.footer-dashboard-content > .f-d-content-1 > span').text(`${currentyYear}`);
    $('.f-sublist > .f-sub-text > span').text(`${currentyYear}`);
}

$(document).ready(() => {
    $(".sidebar-name-text").click(() => {
        window.location.href = "/home"
    })

    sideMenu();
    checkSidebar();
    actuallityDate();
    theme();
})

function theme() {
    var fBlock = $('.side-d-change-theme');
    var sBlock = $('.nav-d-change-theme');
    if(localStorage.getItem('checked')) {
        $('#xxx2, #xxx').attr('checked', 'checked').css('transition', 'none');
        // fBlock.append(
        //     `
        //         <input id="xxx2" onclick="bg()" checked type="checkbox" style="cursor: pointer">
        //     `
        // )
        // sBlock.append(
        //     `
        //         <input id="xxx" name="xxx" type="checkbox" checked onclick="bg()" style="cursor: pointer">
        //     `
        // )
    } else {
        // fBlock.append(
        //     `
        //         <input id="xxx2" onclick="bg()" type="checkbox" style="cursor: pointer">
        //     `
        // )
        // sBlock.append(
        //     `
        //         <input id="xxx" name="xxx" type="checkbox" onclick="bg()" style="cursor: pointer">
        //     `
        // )
    }
}
