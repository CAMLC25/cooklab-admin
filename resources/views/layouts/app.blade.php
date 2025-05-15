<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>CookLab</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'CookLab') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <!-- plugins:css -->
    <link rel="stylesheet" href="{{ asset('admin-assets/assets/feather/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('admin-assets/assets/ti-icons/css/themify-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('admin-assets/assets/css/vendor.bundle.base.css') }}">
    <!-- endinject -->

    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="{{ asset('admin-assets/assets/datatables.net-bs4/dataTables.bootstrap4.css') }}">
    <link rel="stylesheet" href="{{ asset('admin-assets/assets/ti-icons/css/themify-icons.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin-assets/js/select.dataTables.min.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="{{ asset('admin-assets/css/vertical-layout-light/style.css') }}">
    <!-- endinject -->
    <link rel="shortcut icon" href="{{ asset('admin-assets/images/favicon.png') }}" />

</head>
<body>

            @yield('content')

    <!-- JS -->
    <script src="{{ asset('admin-assets/assets/js/vendor.bundle.base.js') }}"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="{{ asset('admin-assets/assets/chart.js/Chart.min.js') }}"></script>
    <script src="{{ asset('admin-assets/assets/datatables.net/jquery.dataTables.js') }}"></script>
    <script src="{{ asset('admin-assets/assets/datatables.net-bs4/dataTables.bootstrap4.js') }}"></script>
    <script src="{{ asset('admin-assets/js/dataTables.select.min.js') }}"></script>

    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="{{ asset('admin-assets/js/off-canvas.js') }}"></script>
    <script src="{{ asset('admin-assets/js/hoverable-collapse.js') }}"></script>
    <script src="{{ asset('admin-assets/js/template.js') }}"></script>
    <script src="{{ asset('admin-assets/js/settings.js') }}"></script>
    <script src="{{ asset('admin-assets/js/todolist.js') }}"></script>
    <!-- endinject -->
    <!-- Custom js for this page-->
    <script src="{{ asset('admin-assets/js/dashboard.js') }}"></script>
    <script src="{{ asset('admin-assets/js/Chart.roundedBarCharts.js') }}"></script>
    <!-- End custom js for this page-->
</body>
</html>
