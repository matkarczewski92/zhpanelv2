<!doctype html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Usługa jest chwilowo niedostępna.">
    <link rel="icon" type="image/png" href="{{ asset('src/logo_black.png') }}">
    <link rel="shortcut icon" href="{{ asset('src/logo_black.png') }}">
    <title>503 | MaksSnake</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="landing-body">
    <main class="landing-404">
        <div class="landing-404-stage">
            <img
                src="{{ asset('images/landing/503.png') }}"
                alt="Ilustracja błędu 503"
                class="landing-404-image"
            >

            <div class="landing-404-card">
                <span class="landing-404-code">503</span>
                <h1 class="landing-404-title">Serwis chwilowo niedostępny</h1>
                <p class="landing-404-text">
                    Trwają prace techniczne albo serwer potrzebuje chwili. Spróbuj ponownie za moment.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ url('/') }}" class="btn btn-primary">Strona główna</a>
                    <a href="{{ url()->current() }}" class="btn btn-outline-light">Odśwież</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
