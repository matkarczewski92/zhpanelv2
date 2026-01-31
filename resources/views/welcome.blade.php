<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="panel-body">
        <div class="d-flex min-vh-100 align-items-center justify-content-center px-4">
            <div class="text-center" style="max-width: 640px;">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-4 bg-dark text-white" style="width: 56px; height: 56px;">ZH</div>
                <h1 class="display-6 fw-semibold">Starter dla Laravel + Bootstrap</h1>
                <p class="text-muted mt-3">
                    Projekt gotowy pod panel hodowlany. Wejdź do panelu i zarządzaj zwierzętami, karmieniami oraz wagami.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                    <a class="btn btn-primary" href="{{ route('panel.home') }}">Przejdz do panelu</a>
                    <a class="btn btn-outline-light" href="https://laravel.com/docs">Dokumentacja Laravel</a>
                </div>
            </div>
        </div>
    </body>
</html>
