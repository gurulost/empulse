document.addEventListener('DOMContentLoaded', function () {
// const btns = document.querySelectorAll('.btn-test');
    const btn1 = document.getElementById('btn-test-1');
    const btn2 = document.getElementById('btn-test-2');
    const btn3 = document.getElementById('btn-test-3');
    const btn4 = document.getElementById('btn-test-4');
    const modalOverlay1 = document.querySelector('.modal-overlay-test');
    const modalOverlay2 = document.querySelector('.modal-overlay-test-2');
    const modalOverlay3 = document.querySelector('.modal-overlay-test-3');
    const modalOverlay4 = document.querySelector('.modal-overlay-test-1');

    const modals1 = document.querySelectorAll('.modal-test');
    const modals2 = document.querySelectorAll('.modal-test-2');
    const modals3 = document.querySelectorAll('.modal-test-3');
    const modals4 = document.querySelectorAll('.modal-test-1');

    const exitBtn1 = document.getElementById('modal-exit-1')
    const exitBtn2 = document.getElementById('modal-exit-2')
    const exitBtn3 = document.getElementById('modal-exit-3')
    const exitBtn4 = document.getElementById('modal-exit-4')

//BTN1
    btn1.addEventListener('click', (e) => {
        let path = e.currentTarget.getAttribute('data-path');

        modals1.forEach((el) => {
            el.classList.remove('modal--visible-test');
        });

        document.querySelector(`[data-target="${path}"]`).classList.add('modal--visible-test');
        modalOverlay1.classList.add('modal-overlay--visible-test');
    });
    exitBtn1.addEventListener('click', (e) => {
        modalOverlay1.classList.remove('modal-overlay--visible-test');
        modals1.classList.remove('modal--1-test');
        modals1.classList.remove('modal--visible-test');
    });
//BTN2
    btn2.addEventListener('click', (e) => {
        let path = e.currentTarget.getAttribute('data-path');

        modals2.forEach((el) => {
            el.classList.remove('modal--visible-test-2');
        });

        document.querySelector(`[data-target="${path}"]`).classList.add('modal--visible-test-2');
        modalOverlay2.classList.add('modal-overlay--visible-test-2');
    });
    exitBtn2.addEventListener('click', (e) => {
        modalOverlay2.classList.remove('modal-overlay--visible-test-2');
    });
//BTN3
    btn3.addEventListener('click', (e) => {
        let path = e.currentTarget.getAttribute('data-path');

        modals3.forEach((el) => {
            el.classList.remove('modal--visible-test-3');
        });

        document.querySelector(`[data-target="${path}"]`).classList.add('modal--visible-test-3');
        modalOverlay3.classList.add('modal-overlay--visible-test-3');
    });
    exitBtn3.addEventListener('click', (e) => {
        modalOverlay3.classList.remove('modal-overlay--visible-test-3');
    });
//BTN4
    btn4.addEventListener('click', (e) => {
        let path = e.currentTarget.getAttribute('data-path');

        modals4.forEach((el) => {
            el.classList.remove('modal--visible-test-1');
        });

        document.querySelector(`[data-target="${path}"]`).classList.add('modal--visible-test-1');
        modalOverlay4.classList.add('modal-overlay--visible-test-1');
    });
    exitBtn4.addEventListener('click', (e) => {
        modalOverlay4.classList.remove('modal-overlay--visible-test-1');
    });

//modal1
    modalOverlay1.addEventListener('click', (e) => {
        console.log(e.target);

        if (e.target == modalOverlay1) {
            modalOverlay1.classList.remove('modal-overlay--visible-test');
            modals1.forEach((el) => {
                el.classList.remove('modal--visible-test');
            });
        }
    });
//modal2
    modalOverlay2.addEventListener('click', (e) => {
        console.log(e.target);

        if (e.target == modalOverlay2) {
            modalOverlay2.classList.remove('modal-overlay--visible-test-2');
            modals2.forEach((el) => {
                el.classList.remove('modal--visible-test-2');
            });
        }
    });
//modal3
    modalOverlay3.addEventListener('click', (e) => {
        console.log(e.target);

        if (e.target == modalOverlay3) {
            modalOverlay3.classList.remove('modal-overlay--visible-test-3');
            modals3.forEach((el) => {
                el.classList.remove('modal--visible-test-3');
            });
        }
    });
//modal4
    modalOverlay4.addEventListener('click', (e) => {
        console.log(e.target);

        if (e.target == modalOverlay4) {
            modalOverlay4.classList.remove('modal-overlay--visible-test-1');
            modals4.forEach((el) => {
                el.classList.remove('modal--visible-test-1');
            });
        }
    });
});