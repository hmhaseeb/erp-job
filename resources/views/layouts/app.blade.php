<!DOCTYPE html>
<html lang="{{ erp_setting('language', 'en') }}" dir="{{ erp_setting('is_rtl', false) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? ($pageTitle ?? 'Dashboard') }} | {{ erp_setting('trade_name', 'Dreams ERP') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ erp_setting('tagline', 'Complete Integrated ERP Software') }}">

    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="{{ erp_logo('favicon') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/apple-icon.png') }}">

    <!-- Default Theme Config & Early Theme Script -->
    <script>
        window.__ERP_DEFAULT_THEME__ = @json(erp_setting('default_theme', 'light'));
    </script>
    <script src="{{ asset('assets/js/theme-script.js') }}"></script>

    <!-- Phosphor CSS -->
    <link rel="stylesheet" href="{{ asset('assets/libs/@phosphor-icons/web/duotone/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/libs/@phosphor-icons/web/regular/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/libs/@phosphor-icons/web/fill/style.css') }}">

    <!-- Lucide CSS -->
    <link rel="stylesheet" href="{{ asset('assets/libs/lucide-static/font/lucide.css') }}">

    <!-- Simplebar CSS -->
    <link rel="stylesheet" href="{{ asset('assets/libs/simplebar/simplebar.min.css') }}">

    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="{{ asset('assets/libs/flatpickr/flatpickr.min.css') }}">

    <!-- Fontawesome CSS -->
    <link rel="stylesheet" href="{{ asset('assets/libs/@fortawesome/fontawesome-free/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/libs/@fortawesome/fontawesome-free/css/all.min.css') }}">

    <!-- Vite Tailwind & App Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Dreams ERP Master Theme Styles -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    @livewireStyles
    @stack('styles')

    <style>
        @media print {
            .sidebar, .navbar-header, .footer, .no-print { display: none !important; }
            .page-wrapper { margin: 0 !important; padding: 0 !important; }
            body { background: #fff !important; }
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body>
    <!-- Main Wrapper -->
    <div class="main-wrapper">
        @include('partials.topbar')
        @include('partials.search-modal')
        @include('partials.sidebar')

        <!-- Page Content -->
        <div class="page-wrapper">
            <main>
                <div class="p-3 lg:py-6 lg:px-0">
                    @if (session('success'))
                        <div class="mb-4 flex items-center justify-between gap-2 rounded-md border border-success/30 bg-success-transparent px-4 py-3 text-sm text-success no-print" role="alert">
                            <div class="flex items-center gap-2">
                                <i class="ph-duotone ph-check-circle text-lg"></i>
                                <span>{{ session('success') }}</span>
                            </div>
                            <button type="button" class="close-alert-btn cursor-pointer"><i class="ph ph-x"></i></button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="mb-4 flex items-center justify-between gap-2 rounded-md border border-danger/30 bg-danger-transparent px-4 py-3 text-sm text-danger no-print" role="alert">
                            <div class="flex items-center gap-2">
                                <i class="ph-duotone ph-warning-circle text-lg"></i>
                                <span>{{ session('error') }}</span>
                            </div>
                            <button type="button" class="close-alert-btn cursor-pointer"><i class="ph ph-x"></i></button>
                        </div>
                    @endif

                    {{ $slot ?? '' }}
                    @yield('content')
                </div>
            </main>

            @include('partials.footer')
        </div>
        <!-- End Page Content -->
    </div>
    <!-- End Main Wrapper -->

    <!-- Global Livewire Toast Banner -->
    <div
        x-data="{ show: false, message: '', type: 'success' }"
        x-on:toast.window="
            message = $event.detail.message || $event.detail[0]?.message || 'Saved successfully';
            type = $event.detail.type || $event.detail[0]?.type || 'success';
            show = true;
            setTimeout(() => show = false, 4000);
        "
        x-show="show"
        x-cloak
        x-transition
        class="fixed bottom-5 right-5 z-[9999] max-w-sm rounded-md border px-4 py-3 shadow-lg text-sm flex items-center gap-2 no-print"
        :class="type === 'error' ? 'bg-white border-danger text-danger' : 'bg-white border-success text-success'"
    >
        <i class="ph-duotone text-lg" :class="type === 'error' ? 'ph-warning-circle' : 'ph-check-circle'"></i>
        <span x-text="message" class="font-medium text-title"></span>
        <button type="button" @click="show = false" class="ms-2 text-default hover:text-title cursor-pointer"><i class="ph ph-x"></i></button>
    </div>

    <!-- Vendor JS -->
    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/preline/preline.js') }}"></script>
    <script src="{{ asset('assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/js/script.js') }}"></script>

    @livewireScripts
    @stack('scripts')
</body>
</html>
