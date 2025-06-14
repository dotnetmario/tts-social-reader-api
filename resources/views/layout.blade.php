<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'TTS Social Reader') }}</title>


    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="assets/images/favicon.svg" type="image/x-icon">

    <link rel="stylesheet" href="{{ asset('css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('css/vendor/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/perfect-scrollbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">


    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">


    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="bg-light d-flex flex-column min-vh-100">


    <div id="app">
        <div id="main" class="m-0 layout-navbar">
            <header class="mb-3 bg-grey">

                <nav class="navbar navbar-expand navbar-light ">
                    <div class="container-fluid">
                        <div class="d-flex align-items-center">
                            <a href="{{ route('dashboard') }}">
                                <img src="https://cdn.iconscout.com/icon/free/png-256/free-logo-icon-download-in-svg-png-gif-file-formats--emblem-label-round-arrows-elements-pack-sign-symbols-icons-2882300.png"
                                    alt="Logo" class="img-fluid" style="max-width: 50px; height: auto;">
                            </a>
                        </div>

                        <div class="collapse navbar-collapse" id="navbarSupportedContent">
                            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">

                            </ul>
                            @if (Auth::check())
                                <div class="dropdown">
                                    <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                        <div class="user-menu d-flex">
                                            <div class="user-name text-end me-3">
                                                <h6 class="mb-0 text-gray-600">{{ auth()->user()->fullname }}</h6>
                                                <p class="mb-0 text-sm text-gray-600">{{ __('app.common.suckup') }}</p>
                                            </div>
                                            <div class="user-img d-flex align-items-center">
                                                <div class="avatar avatar-md">
                                                    <div class="rounded-circle bg-primary text-white text-center d-inline-flex align-items-center justify-content-center"
                                                        style="width: 40px; height: 40px; font-weight: bold;">
                                                        AB
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                        <li>
                                            <h6 class="dropdown-header">{{ __('app.common.greeting') }},
                                                {{ auth()->user()->firstname }}!</h6>
                                        </li>
                                        <hr class="dropdown-divider">
                                        

                                        <li>
                                            <form method="POST" action="{{ route('logout') }}"
                                                class="d-inline dropdown-item no-active-color">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i
                                                        class="mx-1 icon-mid bi bi-box-arrow-left h5"></i>{{ __('app.logout.logout') }}</button>
                                            </form>
                                        </li>



                                        {{-- <li>
    <form method="POST" action="{{ route('logout') }}" class="dropdown-item p-0 m-0">
        @csrf
        <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2">
            <i class="bi bi-box-arrow-left h5 m-0"></i>
            {{ __('app.logout.logout') }}
        </button>
    </form>
</li> --}}
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </nav>
            </header>
            <div id="main-content">

                {{-- Success Message --}}
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Error Message --}}
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif


                @yield('content')
            </div>
        </div>
    </div>


    <script src="{{ asset('js/perfect-scrollbar.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/main.js') }}"></script>
</body>

</html>
