<!DOCTYPE html>
<html lang="{{ erp_setting('language', 'en') }}" dir="{{ erp_setting('is_rtl', false) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Authentication' }} | {{ erp_setting('trade_name', 'Dreams ERP') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon" type="image/x-icon" href="{{ erp_logo('favicon') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/apple-icon.png') }}">

    <script>
        window.__ERP_DEFAULT_THEME__ = @json(erp_setting('default_theme', 'light'));
    </script>
    <script src="{{ asset('assets/js/theme-script.js') }}"></script>

    <link rel="stylesheet" href="{{ asset('assets/libs/@phosphor-icons/web/duotone/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/libs/@phosphor-icons/web/regular/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/libs/@phosphor-icons/web/fill/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/libs/lucide-static/font/lucide.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/libs/@fortawesome/fontawesome-free/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/libs/@fortawesome/fontawesome-free/css/all.min.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>
<body class="bg-light min-h-screen flex items-center justify-center p-4">
    {{ $slot ?? '' }}
    @yield('content')

    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/preline/preline.js') }}"></script>
</body>
</html>
