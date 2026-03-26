<!doctype html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Strona nie została znaleziona.">
    <link rel="icon" type="image/png" href="{{ asset('src/logo_black.png') }}">
    <link rel="shortcut icon" href="{{ asset('src/logo_black.png') }}">
    <title>404 | MaksSnake</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="landing-body">
    <main class="landing-404">
        <div class="landing-404-stage">
            <img
                src="{{ asset('images/landing/404.png') }}"
                alt="Ilustracja błędu 404"
                class="landing-404-image"
            >

            <div class="landing-404-card">
                <span class="landing-404-code">404</span>
                <h1 class="landing-404-title">Nie znaleziono strony</h1>
                <p class="landing-404-text">
                    Adres jest nieaktualny albo strona została przeniesiona.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ url('/') }}" class="btn btn-primary">Strona główna</a>
                    <a href="{{ url()->previous() }}" class="btn btn-outline-light">Cofnij</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
