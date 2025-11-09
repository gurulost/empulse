<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>

    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{asset('/css/bootstrap/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('/css/style.css')}}" />
    <link rel="stylesheet" type="text/css" href="{{asset('/css/admin.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('/css/modal.css')}}">

    @if(Route::currentRouteName() == 'company_staff')
        <link rel="stylesheet" type="text/css" href="{{asset('/css/companyStaff.css')}}">
    @endif
    @if(Route::currentRouteName() == 'payment-success')
        <link rel="stylesheet" type="text/css" href="{{asset('/css/payment_response.css')}}">
    @endif
    @if(Route::currentRouteName() == 'payment')
        <link rel="stylesheet" type="text/css" href="{{asset('/css/payment.css')}}">
    @endif
    @if(Route::currentRouteName() == 'payment_error')
        <link rel="stylesheet" type="text/css" href="{{asset('/css/payment_error.css')}}">
    @endif
    @if(Route::currentRouteName() == 'home')
        <link rel="stylesheet" type="text/css" href="{{asset('/css/chartsModalWindow.css')}}">
    @endif
    @if(Route::currentRouteName() == 'profile')
        <link rel="stylesheet" type="text/css" href="{{asset('/css/profile.css')}}">
    @endif
    @if(Route::currentRouteName() == 'departments')
        <link rel="stylesheet" type="text/css" href="{{asset('/css/departments.css')}}">
    @endif
    @if(Route::currentRouteName() == 'contuctUs')
        <link rel="stylesheet" type="text/css" href="{{asset('/css/contuctUs.css')}}">
    @endif
    @if(Route::currentRouteName() == 'welcome')
        <link rel="stylesheet" type="text/css" href="{{asset('/css/auth/auth.css')}}">
    @endif

    <script type="text/javascript" src="{{asset('/js/jQuery.js')}}"></script>

    @if(Route::currentRouteName() === 'company_staff')
        <script src="{{asset('/js/adminPanel.js')}}"></script>
    @endif

    @if(Route::currentRouteName() !== 'login' && Route::currentRouteName() !== 'register' && Route::currentRouteName() !== 'welcome')
        <script src="{{asset('/js/theme.js')}}"></script>
    @endif

    <script type="text/javascript" src="{{asset('/js/bootstrap/bootstrap.bundle.min.js')}}"></script>
    <script src="{{asset('/js/script.js')}}"></script>
    <script src="{{asset('/js/popup.js')}}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
</head>
<body>
<div id="app">
    @guest
        @if (!Route::has('login') || (Route::has('register')))
            <div class="container-nav">
                <nav class="navbar-main">
                    <div class="navbar-content">
                        <a class="navbar-link" href="https://workfitdx.com/about/"  target="_blank">About Us</a>
{{--                        <a class="navbar-link" href="#" target="_blank">FAQ</a>--}}
                        <a class="navbar-link" href="https://workfitdx.com/contact/" target="_blank">Help</a>
{{--                        <a class="navbar-link" href="#" target="_blank">Contact Us</a>--}}
                    </div>
                </nav>
            </div>
        @endif
    @else
        <div class="sidebar-menu-main" id="popupBurger">
            <div class="sidebar-menu-content bg-black">
                <div class="sidebar-menu-wrapper">
                    <div class="side-menu-cart1">
                        <div class="side-menu-workforce">
                            <svg width="36" height="16" viewBox="0 0 36 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8.27644 15.8781C12.6758 15.8781 16.255 12.4253 16.255 8.18152C16.255 3.93772 12.6758 0.484985 8.27644 0.484985C3.87713 0.484985 0.297852 3.93772 0.297852 8.18152C0.297852 12.4253 3.87684 15.8781 8.27644 15.8781ZM8.27615 2.40933C11.5761 2.40933 14.2602 4.99846 14.2602 8.18152C14.2602 8.42739 14.2389 8.66828 14.2076 8.90612L9.02071 8.15632L5.7721 2.94593C6.53474 2.60481 7.38148 2.40933 8.27615 2.40933ZM4.1234 4.03491L7.5537 9.53631C7.70697 9.78246 7.96616 9.94887 8.26037 9.99206L13.6189 10.7671C12.6336 12.6537 10.6117 13.954 8.27644 13.954C4.97674 13.954 2.29271 11.3648 2.29271 8.1818C2.29214 6.55427 2.9968 5.08513 4.1234 4.03491Z" fill="#AFAFAF"/>
                                <path d="M35.0659 3.28577H20.1802V5.20983H35.0659V3.28577Z" fill="#AFAFAF"/>
                                <path d="M30.0915 8.09637H20.1802V10.0204H30.0915V8.09637Z" fill="#AFAFAF"/>
                                <path d="M32.9232 12.9065H20.1802V14.8306H32.9232V12.9065Z" fill="#AFAFAF"/>
                            </svg>
                            <p class="side-menu-workforce-text">Workforce Monitor</p>
                        </div>
                        <div class="side-menu-trends-proj">
                            <svg width="30"
                                 height="31"
                                 viewBox="0 0 30 31"
                                 style="margin: 20px 0 10px;"
                                 fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M5.95796 24.4658H1.9624V30.6007H5.95796V24.4658Z" fill="#747474"/>
                                <path d="M28.4497 13.9109H24.4541V30.6006H28.4497V13.9109Z" fill="#747474"/>
                                <path d="M20.8662 17.124H16.8706V30.6006H20.8662V17.124Z" fill="#747474"/>
                                <path d="M13.3579 21.3276H9.3623V30.5923H13.3579V21.3276Z" fill="#747474"/>
                                <path d="M12.0181 9.0829L1.53809 18.4808L2.1291 19.1384L12.3011 10.0152L16.3966 13.9941L26.8682 3.98857L26.2523 6.57736L27.118 6.78546L28.2251 2.07404L23.4803 2.98136L23.6468 3.84706L26.2606 3.34762L16.4049 12.7705L12.3178 8.80821L12.0181 9.0829Z" fill="#747474"/>
                                <path d="M0.955453 17.8232L12.3428 7.60123L16.4049 11.5468L23.497 4.77104L21.0331 2.56516L29.3988 0.958618L27.5592 8.7L25.32 6.68558L16.3966 15.2177L12.2762 11.2222L2.06255 20.3787L0.297852 18.4142L0.955453 17.8232Z" fill="#747474"/>
                            </svg>
                            <p class="side-menu-trends-text">Trends & Projections</p>
                            <ul class="side-menu-top-list">
                                <li><a href="/home" class="side-menu-companies-link">Dashboard</a></li>
                                <li><a href="/profile" class="side-menu-companies-link">Profile</a></li>
                                @if(Auth::user()->role == 0)<li><a class="side-menu-companies-link" href="/companies">Companies</a></li>@endif
                                <li><a class="side-menu-companies-link" href="/users">{{ __('Сompany staff') }}</a></li>
                                @if(Auth::user()->role == 1 && Auth::user()->company_title !== null)<li><a class="side-menu-companies-link" href="/departments">Departments</a></li>@endif
                                {{-- @if(Auth::user()->admin !== "yes") <a href="#" class="show-test-results side-menu-companies-link" style="cursor:pointer;">Save test results</a><br />@endif--}}
                                @if(Auth::user()->tariff !== "1" && Auth::user()->company == 1)<li><a href="/payment" class="side-menu-companies-link">Our offers</a></li>@endif
                            </ul>
                        </div>
                        <div class="side-menu-hide-menu">
                            <svg width="12" height="9" viewBox="0 0 12 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0.942833 4.83585C0.747571 4.64059 0.747571 4.32401 0.942833 4.12875L4.12481 0.946767C4.32008 0.751504 4.63666 0.751504 4.83192 0.946766C5.02718 1.14203 5.02718 1.45861 4.83192 1.65387L2.00349 4.4823L4.83192 7.31073C5.02718 7.50599 5.02718 7.82257 4.83192 8.01783C4.63666 8.2131 4.32008 8.2131 4.12481 8.01783L0.942833 4.83585ZM11.2964 4.9823L1.29639 4.9823L1.29639 3.9823L11.2964 3.9823L11.2964 4.9823Z" fill="#A6A6A6"/>
                            </svg>
                            <a class="side-menu-hide-menu-button" id="hambClose">Hide Menu</a>
                        </div>
                    </div>
                    <div class="side-menu-cart2">
                        <div>
                            <a href="{{ route('profile') }}">
                                <div class="side-menu-avatar-image" style="margin-left: 25px;">
                                    <img xmlns="http://www.w3.org/2000/svg"
                                         class="sidebar-avatar-image"
                                         viewBox="0 -100 448 612" src="{{ (!empty(Auth::user()->image))?url('upload/'.Auth::user()->image):url('upload/no_image.jpg') }}" alt="User Avatar">
                                    <!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. -->
                                </div>
                            </a>
                            <div class="side-menu-main-name">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                   data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    @php
                                        $sidebar_name = Auth::user()->name;
                                        if(strlen($sidebar_name) > 10) {
                                            $sidebar_name = substr($sidebar_name, 0, 7).'...';
                                        }
                                    @endphp
                                    <p class="sidebar-name-text-hi">Hi, <span class="sidebar-name-text" title="{{ Auth::user()->name }}">{{ $sidebar_name }}</span></p>
                                </a>
                            </div>
                        </div>
                        <div class="side-menu-nav-links">
                            <a href="https://workfitdx.com/contact/" target="_blank" class="side-menu-nav-link">Help</a>
                            <a href="https://workfitdx.com/about/" target="_blank" class="side-menu-nav-link">About us</a>
{{--                            <a class="side-menu-nav-link">Subscription</a>--}}
                            <div class="side-menu-nav-exit">
                                <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10.2865 4.02262C10.1307 4.02262 9.9812 3.96072 9.871 3.85052C9.7608 3.74032 9.69889 3.59086 9.69889 3.43502C9.69889 3.27918 9.7608 3.12972 9.871 3.01952C9.9812 2.90932 10.1307 2.84741 10.2865 2.84741H11.4617C11.6176 2.84741 11.767 2.90932 11.8772 3.01952C11.9874 3.12972 12.0493 3.27918 12.0493 3.43502V11.6615C12.0493 11.8173 11.9874 11.9668 11.8772 12.077C11.767 12.1872 11.6176 12.2491 11.4617 12.2491H10.2865C10.1307 12.2491 9.9812 12.1872 9.871 12.077C9.7608 11.9668 9.69889 11.8173 9.69889 11.6615C9.69889 11.5057 9.7608 11.3562 9.871 11.246C9.9812 11.1358 10.1307 11.0739 10.2865 11.0739H10.8741V4.02262H10.2865Z" fill="#666666"/>
                                    <path d="M2.16583 7.20755L3.82288 4.85712C3.91274 4.73044 4.04911 4.64449 4.20216 4.61807C4.35521 4.59164 4.51251 4.62689 4.63965 4.7161C4.7032 4.76063 4.7573 4.81731 4.79883 4.88286C4.84035 4.94842 4.86849 5.02155 4.8816 5.09803C4.89471 5.17452 4.89254 5.25284 4.87522 5.32848C4.8579 5.40413 4.82576 5.47559 4.78067 5.53875L3.76999 6.96075H8.52372C8.67957 6.96075 8.82902 7.02266 8.93922 7.13286C9.04942 7.24306 9.11133 7.39252 9.11133 7.54836C9.11133 7.7042 9.04942 7.85366 8.93922 7.96386C8.82902 8.07406 8.67957 8.13596 8.52372 8.13596H3.82288L4.88057 9.54622C4.92687 9.60795 4.96055 9.6782 4.9797 9.75295C4.99885 9.8277 5.00309 9.90549 4.99218 9.98188C4.98127 10.0583 4.95542 10.1318 4.9161 10.1982C4.87679 10.2646 4.82478 10.3226 4.76305 10.3689C4.66133 10.4451 4.53762 10.4864 4.41048 10.4864C4.31926 10.4864 4.22929 10.4651 4.1477 10.4244C4.0661 10.3836 3.99513 10.3243 3.9404 10.2513L2.17758 7.90092C2.10232 7.80154 2.06063 7.6808 2.05851 7.55615C2.0564 7.43151 2.09398 7.30942 2.16583 7.20755Z" fill="#666666"/>
                                </svg>
                                <a href="/logout" class="side-menu-nav-exit-text" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Exit</a>
                                <form id="logout-form" action="/logout" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </div>
                        <div class="side-change-theme-main">
                            <div class="side-d-change-theme">
                                <input id="xxx2" onclick="bg()" type="checkbox" style="cursor: pointer">
                                <!-- switcher -->
                            </div>
                            <div id="sideTextTheme" class="side-d-text-theme-s">White theme</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="sidebar-main">
            <div class="sidebar-wrapper">
                <div class="sidebar-content">
                    <div class="sidebar-cont-trends">
                        <svg width="18"
                             height="19"
                             viewBox="0 0 18 19"
                             fill="none"
                             style="transform: rotate(-270deg); position: relative; left: 20px;"
                             xmlns="http://www.w3.org/2000/svg">
                            <path d="M14.1665 14.7646V17.1129H17.7722V14.7646H14.1665Z" fill="#747474"/>
                            <path d="M7.96289 1.54547V3.8938L17.772 3.8938V1.54547L7.96289 1.54547Z" fill="#747474"/>
                            <path d="M9.85156 6.00238V8.35071H17.7723V6.00238H9.85156Z" fill="#747474"/>
                            <path d="M12.3223 10.4153V12.7637H17.7674V10.4153H12.3223Z" fill="#747474"/>
                            <path d="M5.1257 11.2028L10.6492 17.3623L11.0356 17.0149L5.67364 11.0365L8.01218 8.62947L2.13159 2.4749L3.6531 2.83694L3.77541 2.32813L1.00635 1.67745L1.53961 4.46609L2.04842 4.36824L1.75488 2.83205L7.29301 8.62458L4.96425 11.0267L5.1257 11.2028Z" fill="#747474"/>
                            <path d="M10.2625 17.7048L4.25467 11.0121L6.57364 8.62463L2.59128 4.45635L1.29481 5.90448L0.350586 0.987679L4.90046 2.06889L3.71652 3.38493L8.73117 8.62952L6.38284 11.0512L11.7644 17.0541L10.6098 18.0913L10.2625 17.7048Z" fill="#747474"/>
                        </svg>
                        <p class="sidebar-trends-text">Trends and Projections</p>
                    </div>
                    <div class="sidebar-cont-workforce">
                        <svg width="16"
                             height="36"
                             style="transform: rotate(-270deg); position: relative; left: 20px;"
                             viewBox="0 0 16 36"
                             fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M15.7437 27.3883C15.7437 22.989 12.2909 19.4097 8.04712 19.4097C3.80332 19.4097 0.350586 22.989 0.350586 27.3883C0.350586 31.7876 3.80332 35.3669 8.04712 35.3669C12.2909 35.3669 15.7437 31.7879 15.7437 27.3883ZM2.27493 27.3886C2.27493 24.0886 4.86407 21.4046 8.04712 21.4046C8.29299 21.4046 8.53388 21.4258 8.77172 21.4571L8.02192 26.644L2.81153 29.8926C2.47041 29.13 2.27493 28.2833 2.27493 27.3886ZM3.90051 31.5413L9.40191 28.111C9.64806 27.9578 9.81447 27.6986 9.85766 27.4044L10.6327 22.0458C12.5193 23.0312 13.8196 25.053 13.8196 27.3883C13.8196 30.688 11.2304 33.372 8.0474 33.372C6.41987 33.3726 4.95073 32.6679 3.90051 31.5413Z" fill="#AFAFAF"/>
                            <path d="M3.15137 0.599086L3.15137 15.4848H5.07543L5.07543 0.599086H3.15137Z" fill="#AFAFAF"/>
                            <path d="M7.96191 5.57349L7.96191 15.4848H9.88598V5.57349H7.96191Z" fill="#AFAFAF"/>
                            <path d="M12.772 2.74174V15.4848H14.696V2.74174H12.772Z" fill="#AFAFAF"/>
                        </svg>
                        <p class="sidebar-workforce-text">Workforce Monitor</p>
                    </div>
                </div>
            </div>

            <div class="sidebar-btn-on">
                <div class="sidebar-btn-on-content">
                    <button class="sidebar-button-on">
                        <a class="sidebar-button-on-text" id="hamb" >All Menu</a>
                        <svg width="11" height="9" viewBox="0 0 11 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path class="path-satisfaction" d="M0.359825 4.74821C0.164563 4.55295 0.164563 4.23636 0.359825 4.0411L3.54181 0.85912C3.73707 0.663858 4.05365 0.663858 4.24891 0.85912C4.44417 1.05438 4.44417 1.37096 4.24891 1.56623L1.42049 4.39465L4.24891 7.22308C4.44418 7.41834 4.44418 7.73493 4.24891 7.93019C4.05365 8.12545 3.73707 8.12545 3.54181 7.93019L0.359825 4.74821ZM10.7134 4.89465L0.713379 4.89465L0.713379 3.89465L10.7134 3.89465L10.7134 4.89465Z" fill="black"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <nav class="navbar-main-dashboard">
            <div class="nav-dashboard-container">
                <div class="nav-dashboard-content">
                    <div class="nav-dashboard-logo align-items-center">
                        <a class="nav-d-logo" href="{{route('home')}}">
                            <img id="main-logo-img" class="nav-d-logo-img" src="/materials/images/workfitdxr_logo_1.png">
                        </a>
                        <div class="nav-dashboard-title">
                            <p class="nav-dashboard-title-text">A better way to address the gap in employee satisfaction</p>
                        </div>
                    </div>
                    <div class="nav-dashboard-options">
                        @if (auth()->user()->is_admin == 1)
                            <div>
                                <a href="{{ route('admin.company.list') }}" style="background: #F1C82D; color: black; border-radius: 5px; padding: 5px 7px; text-decoration: none; font-size: 14px; margin-right: 20px;">Go to Admin Panel</a>
                            </div>
                        @endif
                        @if ((int)auth()->user()->company === 1)
                            <div style="margin-right: 16px; display: flex; align-items: center; gap: 8px;">
                                @php $isActive = (int)auth()->user()->tariff === 1; @endphp
                                <span class="badge {{ $isActive ? 'bg-success' : 'bg-danger' }}">
                                    {{ $isActive ? 'Subscription: Active' : 'Subscription: Not active' }}
                                </span>
                                @if(!$isActive && (int)auth()->user()->role === 1)
                                    <a href="{{ route('plans.index') }}" class="btn btn-sm btn-primary">Upgrade</a>
                                @endif
                            </div>
                        @endif
                        <div class="nav-d-theme">
                            <div class="nav-d-text-theme-w">White theme</div>
                            <div class="nav-d-change-theme">
                                <input id="xxx" name="xxx" type="checkbox" onclick="bg()" style="cursor: pointer">
                                <!-- switcher -->
                            </div>
                            <div class="nav-d-text-theme-d">Dark theme</div>
                        </div>

                        <div id="nav-item-info">
                            <div class="nav-item dropdown">
                                <div class="m-2">
                                    <a href="{{ route('profile') }}">
                                        <img xmlns="http://www.w3.org/2000/svg"
                                             class="sidebar-avatar-image"
                                             viewBox="0 -100 448 612"
                                             src="{{ (!empty(Auth::user()->image))?url('upload/'.Auth::user()->image):url('upload/no_image.jpg') }}"
                                             alt="User Avatar"
                                             width="50px" height="50px">
                                    </a>
                                    <!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. -->
                                </div>

                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                   data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    <p class="nav-name-text-hi ">Hi, </p>
                                    <p class="nav-name-text" title="{{ Auth::user()->name }}">{{ Auth::user()->name }}</p>
                                </a>

                                @if(Auth::user()->role == 0)
                                    <div class="nav-profile-status"></div>
                                @endif

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('home') }}">
                                        {{ __('Home') }}
                                    </a>
                                    <a class="dropdown-item" href="{{ route('profile') }}">
                                        {{ __('Profile') }}
                                    </a>
                                    @if((int)Auth::user()->role === 1 && (int)Auth::user()->company === 1)
                                        <a class="dropdown-item" href="{{ route('billing.index') }}">Account & Billing</a>
                                    @endif
                                    @if(Auth::user()->role == 1 || Auth::user()->role == 2 || Auth::user()->role == 3 || Auth::user()->role == 0)
                                        <a class="dropdown-item" href="/users" id="update-coworkers" style="cursor:pointer;">
                                            @if(Auth::user()->role == 1 || Auth::user()->role == 2 || Auth::user()->role == 3){{ __('Сompany staff') }}@elseif(Auth::user()->role == 0){{ __('Admin panel') }}@endif
                                        </a>
                                    @endif
                                    @if(Auth::user()->role == 0)<a class="dropdown-item" href="/companies">Companies</a>@endif
                                    @if(Auth::user()->role == 1 && Auth::user()->company_title !== null)<a class="dropdown-item" href="/departments">Departments</a>@endif
                                    @if((int)Auth::user()->company === 1 && (int)Auth::user()->role === 1 && (int)Auth::user()->tariff !== 1)
                                        <a class="dropdown-item" href="{{ route('plans.index') }}">Upgrade plan</a>
                                    @endif
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                             document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>

                            </div>
                        </div>

                        <div class="nav-menu-dots" style="display: none">
                            @if((int)Auth::user()->company === 1 && (int)Auth::user()->role === 1 && (int)Auth::user()->tariff !== 1)
                                <a class="btn btn-primary" href="{{ route('plans.index') }}" style="margin-right: 30px">Upgrade plan</a>
                            @endif
                            <a>
                                <svg version="1.1"
                                     id="Capa_1"
                                     xmlns="http://www.w3.org/2000/svg"
                                     xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                     width="23px"
                                     height="23px"
                                     viewBox="0 0 408 408" style="enable-background:new 0 0 408 408;"
                                     xml:space="preserve">
                                    <path d="M51,153c-28.05,0-51,22.95-51,51s22.95,51,51,51s51-22.95,51-51S79.05,153,51,153z M357,153c-28.05,0-51,22.95-51,51
                                        s22.95,51,51,51s51-22.95,51-51S385.05,153,357,153z M204,153c-28.05,0-51,22.95-51,51s22.95,51,51,51s51-22.95,51-51
                                        S232.05,153,204,153z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    @endguest

    <main>
        @yield('content')
    </main>
    @guest
        @if (!Route::has('login') || (Route::has('register')))
            <footer class="footer-main">
                <div class="footer-content">
{{--                    <div class="f-list">--}}
                    <div>
                        <a href="https://workfitdx.com/terms-and-conditions/" target="_blank" class="f-text">Terms of Service</a>
                        <a href="https://workfitdx.com/privacy-policy-2/" target="_blank" class="f-text">Privacy Policy</a>
{{--                        <p class="f-text">Notice at Collection</p>--}}
{{--                        <p class="f-text">Cookie Settings</p>--}}
{{--                        <p class="f-text">Accessibility</p>--}}
                    </div>
                    <div class="f-sublist">
                        <p class="f-sub-text">© 2015-<label class="get-year">2000</label> Workfitdxr® Global Inc.</p>
                    </div>
                </div>
            </footer>
        @endif
    @else
        <footer class="footer-dashboard-main">
            <div class="footer-content">
                <div class="footer-dashboard-content">
                    <div class="f-d-content-1">
                        © 2015-<label class="get-year">2000</label> Workfitdxr® Global Inc.
                    </div>
                    <div class="f-d-content-2">
                        <a class="f-d-content-2-link" href="https://workfitdx.com/terms-and-conditions/" target="_blank" class="f-d-content-2-link footer-dashboard-content-block">Terms of Service</a>
                        <a class="f-d-content-2-link" href="https://workfitdx.com/privacy-policy-2/" target="_blank" class="f-d-content-2-link footer-dashboard-content-block">Privacy Policy</a>
                    </div>
                </div>
            </div>
        </footer>
    @endguest
</div>

<div class="modal fade d-none" id="bootModal" tabindex="-1" aria-labelledby="bootModalLabel" aria-hidden="true" style="z-index: 10000 !important;">
    <div class="modal-dialog">
        <div class="modal-content" style="height: 235px;">
            <div class="modal-header">
                <h5 class="modal-title" id="bootModalLabel">Modal title</h5>
                <button type="button" class="btn-close closeModal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary closeModal" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger confirmModal">Save changes</button>
            </div>
        </div>
    </div>
</div>
@yield('script')
<script>
    let setYear = document.querySelector('.get-year');
    const currentYear = new Date().getFullYear().toString();
    setYear.textContent = currentYear;
</script>
@if(Route::currentRouteName() !== 'login' && Route::currentRouteName() !== 'register' && Route::currentRouteName() !== 'welcome')
    <script src="{{asset('/js/theme.js')}}"></script>
@endif
</body>
</html>
