@extends('layouts.app')
@section('title')
    Welcome!
@endsection
@section('content')

    <style type="text/css">
        a {
            text-decoration: none;
        }
        .content-main {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 90vh;
            padding: 0 8vh;
            width: 100%;
        }
        .content-title {
            position: relative;
            left: 0.9vh;
            top: 0.1vh;
            width: 36vh;
            height: 11.7vh;

            font-family: 'Proxima Nova', sans-serif;
            font-style: normal;
            font-weight: 700;
            font-size: 3.2vh;
            text-align: center;

            color: #000000;
        }
        .content-auth {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            top: 6vh;
            left: 11vh;
            width: 50%;
            /*position: relative;*/
            /*right: -90px;*/
            border-left: 0.1vh solid rgba(255, 255, 255, 0.63);
            max-width: 53.2vh;
            height: 34.5vh;
        }
        .cont-auth-title {
            margin: 0 0 4.677vh;
            height: 5vh;
        }
        .auth-title {
            position: relative;
            top: 1vh;
            font-family: 'Proxima Nova', sans-serif;
            font-style: normal;
            font-weight: 700;
            font-size: 3.6vh;
            text-align: center;
            color: #FFFFFF;
        }
        .content-logo {
            width: 50%;
            position: relative;
            left: 3.7vh;
            top: 4.6vh;
        }
        .content-logo-block {
            max-width: 37.9vh;
            height: 5.5vh;
            margin: 0 0 5.4vh;
        }
        .cont-log {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            position: relative;
            top: 1vh;
            margin: 0 0 1.79vh;
            text-align: center;
            height: 7vh;
        }
        .c-log-btn {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            height: 7vh;
            max-width: 30vh;
            padding: 2.55vh 11.95vh;
            background-color: rgba(0, 0, 0, 0.3);
            border-radius: 1vh;

            transition: background-color .3s;
        }
        .c-log-btn:hover {
            background-color: rgba(0, 0, 0, 1);
        }
        .c-log-btn span {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            font-family: 'Proxima Nova', sans-serif;
            text-decoration: none;
            font-style: normal;
            font-weight: 700;
            font-size: 1.6vh;
            text-align: center;
            text-transform: uppercase;
            color: #FFFFFF;
        }
        .cont-sing {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            position: relative;
            top: 1vh;
            margin: 0 0 6vh;
            text-align: center;
            height: 7vh;
        }
        .c-sing-btn {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            height: 7vh;
            max-width: 30vh;
            padding: 2.55vh 11.5vh;
            background: #F1C82D;
            border-radius: 1vh;

            transition: background .3s;
        }
        .c-sing-btn:hover {
            background: #ffde5f;
        }
        .c-sing-btn span {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            font-family: 'Proxima Nova', sans-serif;
            text-decoration: none;
            font-style: normal;
            font-weight: 700;
            font-size: 1.6vh;
            text-align: center;
            text-transform: uppercase;
            color: #000000;
        }
        .cont-referal {
            font-family: 'Proxima Nova', sans-serif;
            font-style: normal;
            font-weight: 700;
            font-size: 2vh;
            text-align: center;
            text-decoration-line: none;

        }
        .cont-referal a {
            color: #FFFFFF;
            border-bottom: 0.1vh solid rgba(255, 255, 255, 0.63);
        }
        .content-main-logo {
            height: 5vh;
            margin-bottom: 3vh;
        }
        /*ADAPTIVE WELCOME PAGE*/
        @media (min-width: 2560px) {
            .navbar-link {
                font-size: 40px;
            }
            .f-list {
                display: flex;
                justify-content: left;
                align-items: center;
                width: 100%;
            }
            .f-text {
                font-size: 40px;
                margin-right: 70px;
            }
            .f-sub-text {
                font-size: 30px;
            }
        }
        @media (min-width: 3840px) {

        }
        /*@media (max-width: 1641px) {*/
        /*    .content-auth {*/
        /*        max-width: 482px;*/
        /*        height: 345px;*/
        /*    }*/
        /*}*/
        /*@media (max-width: 1440px) {*/
        /*    .content-auth {*/
        /*        max-width: 462px;*/
        /*        height: 345px;*/
        /*    }*/
        /*}*/
        /*@media (max-width: 1281px) {*/
        /*    .auth-title {*/
        /*        font-size: 26px;*/
        /*    }*/
        /*    .c-log-btn {*/
        /*        height: 56px;*/
        /*        max-width: 235px;*/
        /*        padding: 20px 96px;*/
        /*    }*/
        /*    .cont-sing {*/
        /*        margin: 0 0 48px;*/
        /*        height: 56px;*/
        /*    }*/
        /*    .c-sing-btn {*/
        /*        height: 56px;*/
        /*        max-width: 235px;*/
        /*        padding: 20px 96px;*/
        /*    }*/
        /*    .content-logo-block {*/
        /*        margin: 0 0 38px;*/
        /*    }*/
        /*    .content-main-logo {*/
        /*        position: relative;*/
        /*        left: 32px;*/
        /*        width: 303px;*/
        /*        height: 44px;*/
        /*    }*/
        /*    .content-title {*/
        /*        height: 100px;*/
        /*        width: 350px;*/
        /*        font-size: 26px;*/
        /*    }*/
        /*    .content-auth {*/
        /*        position: relative;*/
        /*        left: 50px;*/
        /*        max-width: 402px;*/
        /*        height: 345px;*/
        /*    }*/
        /*}*/
        /*@media (max-width: 1081px) {*/
        /*    .content-title {*/
        /*        height: 85px;*/
        /*        font-size: 26px;*/
        /*    }*/
        /*    .content-auth {*/
        /*        max-width: 362px;*/
        /*        height: 345px;*/
        /*    }*/
        /*}*/
        /*@media (max-width: 1010px) {*/
        /*    .content-auth {*/
        /*        margin-top: 20px;*/
        /*    }*/
        /*    .content-title {*/
        /*        height: 70px;*/
        /*        width: 320px;*/
        /*        font-size: 24px;*/
        /*    }*/
        /*    .cont-auth-title {*/
        /*        height: 20px;*/
        /*    }*/

        /*}*/
        /*!*MOBILE*!*/
        /*@media (max-width: 910px) {*/
        /*    .content-logo {*/
        /*        width: 362px;*/
        /*        position: relative;*/
        /*        top: 0;*/
        /*        right: 0;*/
        /*        left: 0;*/
        /*        bottom: 0;*/
        /*    }*/
        /*    .content-auth {*/
        /*        position: relative;*/
        /*        top: 0;*/
        /*        right: 0;*/
        /*        left: 0;*/
        /*        bottom: 0;*/
        /*        border-left: 0;*/
        /*        border-top: 1px solid rgba(255, 255, 255, 0.63);*/
        /*        padding: 20px 0 0;*/
        /*    }*/
        /*    .content-main {*/
        /*        position: relative;*/
        /*        top: 0;*/
        /*        right: 0;*/
        /*        left: 0;*/
        /*        bottom: 0;*/
        /*        display: flex;*/
        /*        align-items: center;*/
        /*        justify-content: center;*/
        /*        flex-direction: column;*/
        /*        height: 90vh;*/
        /*        padding: 60px 0 0;*/
        /*        width: 100%;*/
        /*    }*/
        /*    .content-logo {*/
        /*    }*/
        /*}*/
    </style>

{{--    <div class="content">--}}
{{--        <a href="/login" class="btn btn-warning" class="welcome_button">Welcome!</a>--}}
{{--    </div>--}}

    <div class="content-main">
        <div class="content-logo">
            <div class="content-logo-block">
                <img class="content-main-logo" src="../../materials/images/workfitdxr_logo_1.png">
            </div>
            <div class="content-title-block">
                <h1 class="content-title">
                    A better way to address the gap in employee satisfaction
                </h1>
            </div>
        </div>
        <div class="content-auth">
           <div class="cont-auth-title">
               <h2 class="auth-title">Get Started!</h2>
           </div>
            <div class="cont-log">
                <a href="/login" class="c-log-btn"><span>LOG IN</span></a>
            </div>
            <div class="cont-sing">
                <a href="/register" class="c-sing-btn"><span>SING UP</span></a>
            </div>
{{--           <div class="cont-referal">--}}
{{--               <a href="#">Referal link</a>--}}
{{--           </div>--}}
        </div>
    </div>

@endsection
