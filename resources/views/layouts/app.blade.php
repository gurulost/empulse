<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>

    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    
    <!-- Scripts & Styles -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    
    <!-- Chart.js (Required for some legacy views, but moving to Vue) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
</head>
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
        
        <!-- Guest Content Area -->
        @yield('content')
    @else
        <div class="d-flex">
            <!-- Sidebar Component -->
            <app-sidebar 
                :user="{{ Auth::user() }}" 
                current-route="{{ Route::currentRouteName() }}"
            ></app-sidebar>
            
            <!-- Main Content Area -->
            <div class="flex-grow-1 main-content-wrapper">
                <main class="py-4 px-3 px-md-4">
                    @yield('content')
                </main>
                
                <footer class="text-center py-4 text-muted small mt-auto">
                    © 2015-<span class="get-year">2025</span> Workfitdxr® Global Inc. | 
                    <a href="https://workfitdx.com/terms-and-conditions/" target="_blank" class="text-decoration-none text-muted ms-2">Terms</a> | 
                    <a href="https://workfitdx.com/privacy-policy-2/" target="_blank" class="text-decoration-none text-muted ms-2">Privacy</a>
                </footer>
            </div>
        </div>
        
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    @endguest
    
    <toast-container></toast-container>
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
    if(setYear) {
        const currentYear = new Date().getFullYear().toString();
        setYear.textContent = currentYear;
    }
</script>
    <style>
        .main-content-wrapper {
            min-height: 100vh;
            background-color: #f1f5f9; /* Slate 100 */
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Desktop: Sidebar is 280px */
        @media (min-width: 768px) {
            .main-content-wrapper {
                margin-left: 280px;
            }
        }
        
        /* Mobile: Sidebar is hidden/off-canvas */
        @media (max-width: 767.98px) {
            .main-content-wrapper {
                margin-left: 0;
                padding-top: 60px; /* Space for the toggle button */
            }
        }
    </style>
</body>
</html>
