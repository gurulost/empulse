//burger popup
const hamb = document.querySelector("#hamb");
const close = document.querySelector("#hambClose")
const popupBurger = document.querySelector("#popupBurger");
const main = document.querySelector("#main");
const test = document.getElementById("nav-item-info");
let select = document.querySelector(".side-select-company");
let arrow = document.querySelector(".bi-caret-down-fill");

if (window.location.href.indexOf('home') > -1) {
    // test.style.display = 'none'
} else {
    $('.nav-d-theme').hide();
}
hamb.addEventListener("click", hambClose);
close.addEventListener("click", hambHandler);

function hambHandler(e) {
    e.preventDefault();
    popupBurger.classList.toggle("open");
    let openClass = document.querySelector(".open")
    console.log('Hello!');

    test.style.display = 'block'
}

function hambClose(e) {
    e.preventDefault();
    popupBurger.classList.remove("open");

    test.style.display = 'none'
}

if(select !== null) {
    select.addEventListener('click', function () {
        arrow.style.transform = 'rotate(180deg)';
    });
    select.addEventListener("blur", function() {
        arrow.style.transform = 'rotate(0deg)';
    });
}

