<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Logowanie - {{ config('app.name', 'Laravel') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="panel-body">
        <div class="d-flex min-vh-100 align-items-center justify-content-center px-3">
            <div class="card cardopacity" style="max-width: 420px; width: 100%;">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bg-dark text-white" style="width: 48px; height: 48px;">ZH</div>
                        <h1 class="h5 mb-1">Panel administracyjny</h1>
                        <p class="text-muted mb-0">Zaloguj sie, aby przejsc do panelu.</p>
                    </div>

                    <form method="POST" action="{{ url('/login') }}" class="vstack gap-3">
                        @csrf

                        <div>
                            <label class="form-label" for="name">Nazwa uzytkownika</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}"
                                required
                                autofocus
                            />
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label" for="password">Haslo</label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                required
                            />
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Zapamietaj mnie</label>
                        </div>

                        <button class="btn btn-primary w-100" type="submit">Zaloguj</button>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>
