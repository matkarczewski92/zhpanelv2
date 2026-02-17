<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" type="image/png" href="{{ asset('src/logo_black.png') }}">
        <link rel="shortcut icon" href="{{ asset('src/logo_black.png') }}">
        <title>@yield('title', 'Panel admina') - {{ config('app.name', 'Laravel') }}</title>

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="panel-body">
        @include('layouts.navbar')

        <main class="panel-content">
            <div class="panel-container-wide">
                @yield('content')
            </div>
        </main>

        @include('components.toasts')
        @stack('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((el) => {
                    new bootstrap.Dropdown(el);
                });
            });
        </script>
    </body>
</html>
