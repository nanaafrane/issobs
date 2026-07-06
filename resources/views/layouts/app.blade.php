<!doctype html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title></title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('img/favicon/favicon.ico')}}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('vendor/fonts/iconify-icons.css')}}" />

    <!-- Core CSS -->
    <!-- build:css assets/vendor/css/theme.css  -->

    <link rel="stylesheet" href="{{ asset('vendor/css/core.css')}}" />
    <link rel="stylesheet" href="{{ asset('css/demo.css')}}" />

    <!-- Vendors CSS -->

    <link rel="stylesheet" href="{{ asset('vendor/libs/perfect-scrollbar/perfect-scrollbar.css')}}" />

    <!-- endbuild -->

    <!-- Page CSS -->
    <!-- Page -->
    <link rel="stylesheet" href="{{ asset('vendor/css/pages/page-auth.css')}}" />

    <style>
        .btn.loading,
        button.loading,
        input.loading,
        a.loading {
            pointer-events: none;
            opacity: 0.75;
            cursor: progress;
        }

        .btn.loading .spinner-border,
        button.loading .spinner-border,
        a.loading .spinner-border {
            margin-right: 0.4rem;
            vertical-align: text-bottom;
        }

        .btn.loading .btn-loading-text,
        a.loading .btn-loading-text {
            display: inline-block;
            margin-left: 0.25rem;
        }

        a.loading {
            text-decoration: none;
        }

        .loading-progress {
            display: inline-flex;
            align-items: center;
        }
    </style>

    <!-- Helpers -->
    <script src="{{ asset('vendor/js/helpers.js')}}"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->

    <script src="{{ asset('js/config.js')}}"></script>

</head>

<body>
    <div id="app">
        <!-- Content -->

        @yield('logincard')

        <!-- / Content -->

        <!-- Core JS -->

        <script src="{{ asset('vendor/libs/jquery/jquery.js')}}"></script>

        <script src="{{ asset('vendor/libs/popper/popper.js')}}"></script>
        <script src="{{ asset('vendor/js/bootstrap.js')}}"></script>

        <script src="{{ asset('vendor/libs/perfect-scrollbar/perfect-scrollbar.js')}}"></script>

        <script src="{{ asset('vendor/js/menu.js')}}"></script>

        <!-- endbuild -->

        <!-- Vendors JS -->

        <!-- Main JS -->

        <script src="{{ asset('js/main.js')}}"></script>


    </div>
</body>

</html>