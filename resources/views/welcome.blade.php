<!doctype html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.7">
    <meta name="description" content="Hodowla gadów MaksSnake - profile, oferta i plany hodowlane.">
    <title>Hodowla Gadów MaksSnake</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="landing-body">
    @php
        $gallery = $page->gallery ?? [];
        $offerGroups = $page->offerGroups ?? [];
        $offerColorGroups = $page->offerColorGroups ?? [];
        $breedingPlans = $page->breedingPlans ?? [];
    @endphp

    <header class="landing-nav-wrap">
        <nav class="navbar navbar-expand-lg navbar-dark landing-nav">
            <div class="container">
                <a class="navbar-brand fw-semibold" href="#top">MAKS SNAKE</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#landingNav" aria-controls="landingNav" aria-expanded="false" aria-label="Przełącz nawigację">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="landingNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item"><a class="nav-link" href="#gallery">Galeria</a></li>
                        <li class="nav-item"><a class="nav-link" href="#offer">Nadwyżki hodowlane</a></li>
                        <li class="nav-item"><a class="nav-link" href="#plans">Plany hodowlane</a></li>
                        <li class="nav-item"><a class="nav-link" href="#profile">Profil węża</a></li>
                        <li class="nav-item"><a class="nav-link" href="#about-us">O nas</a></li>
                    </ul>
                    <div class="d-flex gap-2">
                        <a href="https://dziennik.makssnake.pl/" class="btn btn-sm btn-outline-light" target="_blank" rel="noopener noreferrer">Dziennik hodowlany</a>
                        @auth
                            <a href="{{ route('panel.home') }}" class="btn btn-sm btn-primary">Panel</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-sm btn-outline-light">ZH</a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <section id="top" class="landing-hero landing-hero-main">
        <div class="landing-overlay"></div>
        <div class="container position-relative z-2 text-center">
            <img src="{{ asset('images/landing/logo_white.png') }}" alt="Logo MaksSnake" class="landing-logo mb-4">
            <h1 class="display-5 fw-bold mb-2">Hodowla Gadów MaksSnake</h1>
            <p class="lead mb-0">Węże właściwe, pytony królewskie, agamy brodate</p>
        </div>
    </section>

    <main>
        <section id="gallery" class="landing-section">
            <div class="container">
                <h2 class="landing-section-title">Nasze węże</h2>
                <div id="landingGalleryCarousel" class="carousel slide landing-carousel" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <img src="{{ asset('images/landing/x2a.jpg') }}" class="d-block w-100" alt="Galeria hodowli MaksSnake">
                            <div class="carousel-caption landing-gallery-caption">
                                <span>MaksSnake</span>
                            </div>
                        </div>
                        @foreach ($gallery as $item)
                            <div class="carousel-item">
                                <img src="{{ $item['url'] }}" class="d-block w-100" alt="{{ $item['title'] }}">
                                <div class="carousel-caption landing-gallery-caption">
                                    <span>{{ $item['title'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#landingGalleryCarousel" data-bs-slide="prev" aria-label="Poprzedni slajd">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#landingGalleryCarousel" data-bs-slide="next" aria-label="Następny slajd">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </section>

        <section class="landing-hero landing-hero-offer" id="offer">
            <div class="landing-overlay"></div>
            <div class="container position-relative z-2 text-center">
                <h2 class="display-6 fw-bold mb-0">Nadwyżki hodowlane</h2>
            </div>
        </section>

        <section class="landing-section">
            <div class="container">
                <h2 class="landing-section-title">NADWYŻKI HODOWLANE</h2>
                <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
                    <button type="button" class="btn btn-outline-light btn-sm landing-type-filter is-active" data-offer-type="all">
                        Wszystkie typy
                    </button>
                    @foreach ($offerGroups as $group)
                        <button type="button" class="btn btn-outline-light btn-sm landing-type-filter" data-offer-type="{{ $group['type_id'] }}">
                            {{ $group['type_name'] }}
                        </button>
                    @endforeach
                </div>
                @if (count($offerColorGroups))
                    @php
                        $primaryColorGroups = collect($offerColorGroups)->filter(fn ($group) => (int) ($group['sort_order'] ?? 0) <= 90)->values()->all();
                        $secondaryColorGroups = collect($offerColorGroups)->filter(fn ($group) => (int) ($group['sort_order'] ?? 0) > 90)->values()->all();
                    @endphp
                    @if (count($primaryColorGroups))
                        <div class="d-flex flex-wrap justify-content-center gap-2 mb-2">
                            @foreach ($primaryColorGroups as $group)
                                <button type="button" class="btn btn-outline-light btn-sm landing-type-filter" data-offer-color="{{ $group['id'] }}">
                                    {{ $group['name'] }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                    @if (count($secondaryColorGroups))
                        <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
                            @foreach ($secondaryColorGroups as $group)
                                <button type="button" class="btn btn-outline-light btn-sm landing-type-filter" data-offer-color="{{ $group['id'] }}">
                                    {{ $group['name'] }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                @endif
                @forelse ($offerGroups as $group)
                    <div class="mb-4 landing-offer-group" data-offer-type-group="{{ $group['type_id'] }}">
                        <h3 class="h5 mb-2">{{ $group['title'] }}</h3>
                        @if ($group['male_name'] || $group['female_name'])
                            <p class="text-light-emphasis mb-3">
                                @if ($group['male_name']) {{ $group['male_name'] }} @endif
                                @if ($group['male_name'] && $group['female_name']) <span class="mx-1">x</span> @endif
                                @if ($group['female_name']) {{ $group['female_name'] }} @endif
                            </p>
                        @endif
                        <div class="row g-3">
                            @foreach ($group['offers'] as $offer)
                                <div
                                    class="col-12 col-md-6 col-xl-3 landing-offer-card-wrap"
                                    data-offer-type-item="{{ $group['type_id'] }}"
                                    data-offer-colors="{{ implode(',', $offer['color_group_ids'] ?? []) }}"
                                >
                                    <article class="card landing-offer-card h-100">
                                        <img
                                            src="{{ $offer['photo_url'] ?: asset('images/landing/x1a.jpg') }}"
                                            alt="{{ strip_tags($offer['name_html']) }}"
                                            class="card-img-top"
                                        >
                                        <div class="card-body">
                                            <h4 class="h6 mb-1">#{{ $offer['id'] }} {!! $offer['name_html'] !!}</h4>
                                            <div class="small text-light-emphasis">{{ $offer['sex_label'] }} @if($offer['date_of_birth']) · ur. {{ $offer['date_of_birth'] }} @endif</div>
                                        </div>
                                        <div class="card-footer d-flex justify-content-between align-items-center">
                                            <span class="fw-semibold">{{ $offer['price_label'] }}</span>
                                            @if ($offer['profile_url'])
                                                <a href="{{ $offer['profile_url'] }}" class="btn btn-sm btn-outline-light">Profil</a>
                                            @endif
                                        </div>
                                    </article>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-center text-light-emphasis mb-0">Aktualnie nie posiadamy nadwyżek hodowlanych.</p>
                @endforelse

                <div class="landing-legal text-light-emphasis">
                    <p>Nie wydajemy zwierząt osobom nieletnim.</p>
                    <p>Nie wysyłamy węży kurierem ani przesyłką konduktorską.</p>
                    <p>Prezentowane informacje mają charakter dokumentacyjny i nie stanowią oferty handlowej.</p>
                </div>
            </div>
        </section>

        <section class="landing-hero landing-hero-plans" id="plans">
            <div class="landing-overlay"></div>
            <div class="container position-relative z-2 text-center">
                <h2 class="display-6 fw-bold mb-0">Plany hodowlane</h2>
            </div>
        </section>

        <section class="landing-section">
            <div class="container">
                <h2 class="landing-section-title">Plany hodowlane - projekty na bieżący rok</h2>
                <div class="list-group landing-plan-list">
                    @forelse ($breedingPlans as $plan)
                        <div class="list-group-item">
                            <div class="fw-semibold">{{ $plan['title'] }} <span class="text-light-emphasis">({{ $plan['status_label'] }})</span></div>
                            @if ($plan['male_name'] || $plan['female_name'])
                                <div class="small text-light-emphasis mt-1">
                                    {{ $plan['male_name'] ?: 'Brak samca' }} x {{ $plan['female_name'] ?: 'Brak samicy' }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="list-group-item text-light-emphasis">Brak planów hodowlanych na bieżący rok.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="landing-hero landing-hero-profile" id="profile">
            <div class="landing-overlay"></div>
            <div class="container position-relative z-2 text-center">
                <h2 class="display-6 fw-bold mb-0">Profil węża</h2>
            </div>
        </section>

        <section class="landing-section">
            <div class="container text-center" style="max-width: 760px;">
                <h2 class="landing-section-title">Historia Twojego węża</h2>
                <p class="text-light-emphasis">
                    Każdy wąż z naszej hodowli posiada publiczny profil z historią karmień, wylinek i ważeń.
                    Wpisz kod z etykiety i przejdź do profilu.
                </p>
                @if (session('profile_lookup_error'))
                    <div class="alert alert-warning py-2 mt-3 mb-0">
                        {{ session('profile_lookup_error') }}
                    </div>
                @endif
                <form id="publicProfileLookupForm" class="row g-2 justify-content-center mt-3" method="POST" action="{{ route('profile.lookup') }}">
                    @csrf
                    <div class="col-12 col-md-7">
                        <input
                            type="text"
                            class="form-control"
                            id="publicProfileCode"
                            name="code"
                            value="{{ old('code') }}"
                            placeholder="Wprowadź Kod zwierzęcia"
                            aria-label="Kod profilu"
                        >
                        @error('code')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Pokaż profil</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="landing-hero landing-hero-about" id="about-us">
            <div class="landing-overlay"></div>
            <div class="container position-relative z-2 text-center">
                <h2 class="display-6 fw-bold mb-0">O nas</h2>
            </div>
        </section>

        <section class="landing-section">
            <div class="container text-center">
                <p class="mb-1">Email: <a href="mailto:snake@makssnake.pl" class="link-light">snake@makssnake.pl</a></p>
                <p class="mb-3">Telefon: <a href="tel:+48698328234" class="link-light">698 328 234</a></p>
                <p class="text-light-emphasis mb-0">Najszybciej odpowiadamy na Facebooku i Messengerze.</p>
            </div>
        </section>

        <section class="landing-hero landing-hero-quote">
            <div class="landing-overlay"></div>
            <div class="container position-relative z-2 text-center">
                <p class="landing-quote mb-0">"Twoja pasja czeka, aż dogoni ją odwaga."</p>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filterButtons = document.querySelectorAll('.landing-type-filter');
            const groups = document.querySelectorAll('.landing-offer-group');
            const cards = document.querySelectorAll('.landing-offer-card-wrap');

            let selectedType = 'all';
            const selectedColors = new Set();

            const applyOfferFilters = () => {
                cards.forEach((card) => {
                    const cardType = card.getAttribute('data-offer-type-item');
                    const rawColors = card.getAttribute('data-offer-colors') || '';
                    const cardColors = rawColors === '' ? [] : rawColors.split(',').filter(Boolean);

                    const typeMatch = selectedType === 'all' || selectedType === cardType;
                    const colorMatch = selectedColors.size === 0
                        || Array.from(selectedColors).every((id) => cardColors.includes(id));

                    card.classList.toggle('d-none', !(typeMatch && colorMatch));
                });

                groups.forEach((group) => {
                    const visibleCards = group.querySelectorAll('.landing-offer-card-wrap:not(.d-none)');
                    group.classList.toggle('d-none', visibleCards.length === 0);
                });
            };

            filterButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const typeValue = button.getAttribute('data-offer-type');
                    const colorValue = button.getAttribute('data-offer-color');

                    if (typeValue !== null) {
                        selectedType = typeValue;
                        document.querySelectorAll('[data-offer-type]').forEach((item) => item.classList.remove('is-active'));
                        button.classList.add('is-active');
                    }

                    if (colorValue !== null) {
                        if (selectedColors.has(colorValue)) {
                            selectedColors.delete(colorValue);
                            button.classList.remove('is-active');
                        } else {
                            selectedColors.add(colorValue);
                            button.classList.add('is-active');
                        }
                    }

                    applyOfferFilters();
                });
            });
        });
    </script>
</body>
</html>

