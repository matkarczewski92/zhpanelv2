<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil publiczny</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="panel-body">
    <div class="profile-hero" style="--profile-hero-image: url('{{ $profile->bannerUrl }}'); margin-top:-45px"></div>

    <div class="panel-content animal-profile">
        <div class="panel-container-wide">
            <div class="profile-header-wrap mb-3" style="margin-top: -30px">
                <div class="card cardopacity profile-header">
                    <div class="card-body d-flex flex-column flex-lg-row align-items-start align-items-lg-center gap-3">
                        <div class="profile-avatar">
                            <img src="{{ $profile->avatarUrl }}" alt="Avatar" />
                        </div>
                        <div class="flex-grow-1">
                            <h1 class="h4 mb-1 profile-name">
                                @if ($profile->secondNameText)
                                    <span class="profile-second-name">"{{ $profile->secondNameText }}"</span>
                                @endif
                                <span class="profile-name-text">{!! $profile->nameDisplayHtml !!}</span>
                            </h1>
                            <div class="profile-meta text-muted">
                                <span>{{ $profile->animalTypeName }}</span>
                                <span>{{ $profile->sexLabel }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card cardopacity mb-3" id="gallery">
                <div class="card-header">Galeria</div>
                <div class="card-body">
                    @if (count($profile->galleryPhotos))
                        <div class="row g-3">
                            @foreach ($profile->galleryPhotos as $index => $photo)
                                <div class="col-6 col-md-3 col-lg-2">
                                    <button
                                        type="button"
                                        class="btn p-0 w-100"
                                        data-gallery-index="{{ $index }}"
                                        aria-label="Powiększ zdjęcie"
                                    >
                                        <img src="{{ $photo['url'] }}" alt="{{ $photo['title'] }}" class="img-fluid rounded" style="object-fit: cover; height: 120px; width: 100%;">
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-muted">Brak zdjęć.</div>
                    @endif
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-3">
                    <div class="card cardopacity mb-3">
                        <div class="card-header">Szczegóły</div>
                        <div class="card-body">
                            <dl class="row mb-0 small">
                                @foreach ($profile->details as $row)
                                    <dt class="col-6 text-muted">{{ $row['label'] }}</dt>
                                    <dd class="col-6">{{ $row['value'] }}</dd>
                                @endforeach
                            </dl>
                        </div>
                    </div>

                    <div class="card cardopacity mb-3" id="genetyka">
                        <div class="card-header">Genetyka</div>
                        <div class="card-body">
                            <div id="genotypeChips">
                                @if (count($profile->genotypeChips))
                                    <div class="genotype-chips">
                                        @foreach ($profile->genotypeChips as $chip)
                                            <span class="genotype-chip genotype-chip--{{ $chip['type_code'] ?? 'v' }}">
                                                {{ $chip['label'] }}
                                                <small class="text-muted">{{ $chip['type_label'] }}</small>
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted small">Brak danych genetycznych.</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card cardopacity mb-3 feedings-accordion" id="feedings">
                        <div class="card-header">Karmienia</div>
                        <div class="card-body">
                            @if (count($profile->feedingsTree))
                                <div class="accordion accordion-flush feedings-accordion" id="feedingsAccordionPublic">
                                    @foreach ($profile->feedingsTree as $yearGroup)
                                        @php $yearId = 'public-year-'.$yearGroup['year']; @endphp
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $yearId }}">
                                                    {{ $yearGroup['year'] }}
                                                </button>
                                            </h2>
                                            <div id="{{ $yearId }}" class="accordion-collapse collapse" data-bs-parent="#feedingsAccordionPublic">
                                                <div class="accordion-body p-2">
                                                    <div class="accordion accordion-flush feedings-accordion" id="feedings-{{ $yearGroup['year'] }}">
                                                        @foreach ($yearGroup['months'] as $monthGroup)
                                                            @php $monthId = 'public-month-'.$yearGroup['year'].'-'.$monthGroup['month']; @endphp
                                                            <div class="accordion-item border-0">
                                                                <h2 class="accordion-header">
                                                                    <button class="accordion-button py-2 px-3 collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $monthId }}">
                                                                        <span class="form-label mb-0 fs-6">{{ $monthGroup['month_label_full'] ?? ('Miesiąc ' . $monthGroup['month_label']) }}</span>
                                                                    </button>
                                                                </h2>
                                                                <div id="{{ $monthId }}" class="accordion-collapse collapse" data-bs-parent="#feedings-{{ $yearGroup['year'] }}">
                                                                    <div class="accordion-body py-2 px-3">
                                                                        <ul class="list-group list-group-flush">
                                                                            @foreach ($monthGroup['entries'] as $entry)
                                                                                <li class="list-group-item d-flex align-items-center justify-content-between px-0 py-2">
                                                                                    <div class="d-flex flex-column flex-md-row gap-2">
                                                                                        <span class="text-muted">{{ $entry['date_display'] }}</span>
                                                                                        <span class="fw-semibold">{{ $entry['feed_name'] }}</span>
                                                                                        <span class="text-muted">Ilość: {{ $entry['quantity'] }}</span>
                                                                                    </div>
                                                                                </li>
                                                                            @endforeach
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted small">Brak danych o karmieniach.</span>
                            @endif
                        </div>
                    </div>

                    <div class="card cardopacity mb-3" id="weights">
                        <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
                            <span>Wagi</span>
                            <span class="text-muted small">{{ $weightsPage['pagination']['total'] ?? 0 }} wpisów</span>
                        </div>
                        <div class="card-body">
                            <canvas
                                id="weightsChart"
                                height="220"
                                class="mb-3"
                                data-series='@json($profile->weightsSeries)'
                            ></canvas>
                            <div id="public-weights" data-url="{{ route('profile.weights', $publicCode) }}">
                                @include('public.profile.partials.weights', [
                                    'items' => $weightsPage['items'] ?? [],
                                    'pagination' => $weightsPage['pagination'] ?? ['current_page'=>1,'last_page'=>1]
                                ])
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="card cardopacity mb-3">
                        <div class="card-header">Oferta</div>
                        <div class="card-body">
                            <div class="d-flex flex-column gap-2">
                                <div><span class="text-muted">Wartość hodowlana:</span> <span class="fw-semibold">{{ $profile->offerValue ?? '-' }}</span></div>
                                <div><span class="text-muted">Status:</span> <span class="fw-semibold">{{ $profile->hasReservation ? 'Rezerwacja' : 'Dostępne' }}</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="card cardopacity mb-3">
                        <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
                            <span>Wylinki</span>
                            <span class="text-muted small">{{ $moltsPage['pagination']['total'] ?? 0 }} wpisów</span>
                        </div>
                        <div class="card-body">
                            <div id="public-molts" data-url="{{ route('profile.molts', $publicCode) }}">
                                @include('public.profile.partials.molts', [
                                    'items' => $moltsPage['items'] ?? [],
                                    'pagination' => $moltsPage['pagination'] ?? ['current_page'=>1,'last_page'=>1]
                                ])
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="public-gallery-lightbox d-none" id="publicGalleryLightbox" aria-hidden="true">
        <div class="public-gallery-backdrop"></div>
        <div class="public-gallery-content">
            <button class="public-gallery-close" id="publicGalleryClose" aria-label="Zamknij">&times;</button>
            <button class="public-gallery-nav public-gallery-prev" id="publicGalleryPrev" aria-label="Poprzednie">&#10094;</button>
            <img id="publicGalleryImage" src="" alt="Podgląd" />
            <button class="public-gallery-nav public-gallery-next" id="publicGalleryNext" aria-label="Następne">&#10095;</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Feeding collapse ensure toggle
            document.querySelectorAll('#feedingsAccordionPublic .accordion-button').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    const targetSelector = btn.getAttribute('data-bs-target');
                    if (!targetSelector) return;
                const target = document.querySelector(targetSelector);
                if (!target) return;
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(target, { toggle: false });
                if (target.classList.contains('show')) {
                    bsCollapse.hide();
                } else {
                    bsCollapse.show();
                }
            });
        });

        const chartCanvas = document.getElementById('weightsChart');
        if (chartCanvas) {
            const series = JSON.parse(chartCanvas.getAttribute('data-series') || '[]');
            const ctx = chartCanvas.getContext('2d');
            if (series.length) {
                const labels = series.map((p) => p.date);
                const data = series.map((p) => p.value);
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: 'Waga',
                                data,
                                fill: true,
                                borderColor: '#6ea8fe',
                                backgroundColor: 'rgba(110, 168, 254, 0.25)',
                                tension: 0.35,
                                pointRadius: 3,
                                pointBackgroundColor: '#fff',
                            },
                        ],
                    },
                    options: {
                        plugins: { legend: { labels: { color: '#fff' } } },
                        scales: {
                            x: { ticks: { color: '#ccc' }, grid: { color: 'rgba(255,255,255,0.08)' } },
                            y: { ticks: { color: '#ccc' }, grid: { color: 'rgba(255,255,255,0.08)' } },
                        },
                    },
                });
            }
        }

        const ajaxPaginate = (wrapperId) => {
            const wrapper = document.getElementById(wrapperId);
            if (!wrapper) return;
            const baseUrl = wrapper.getAttribute('data-url');
            const attach = () => {
                wrapper.querySelectorAll('button[data-page]').forEach((btn) => {
                    btn.addEventListener('click', async () => {
                        const page = btn.getAttribute('data-page');
                        if (!page) return;
                        const res = await fetch(`${baseUrl}?page=${page}`);
                        if (!res.ok) return;
                        const html = await res.text();
                        wrapper.innerHTML = html;
                        attach();
                    });
                });
            };
            attach();
        };

        ajaxPaginate('public-weights');
        ajaxPaginate('public-molts');

        // Gallery lightbox
        const photos = @json($profile->galleryPhotos);
        const lightbox = document.getElementById('publicGalleryLightbox');
        const img = document.getElementById('publicGalleryImage');
        const btnClose = document.getElementById('publicGalleryClose');
        const btnPrev = document.getElementById('publicGalleryPrev');
        const btnNext = document.getElementById('publicGalleryNext');
        let current = 0;

        const openLightbox = (index) => {
            if (!photos.length) return;
            current = ((index % photos.length) + photos.length) % photos.length;
            img.src = photos[current].url;
            lightbox.classList.remove('d-none');
            lightbox.classList.add('show');
            lightbox.setAttribute('aria-hidden', 'false');
        };

        const closeLightbox = () => {
            lightbox.classList.add('d-none');
            lightbox.classList.remove('show');
            lightbox.setAttribute('aria-hidden', 'true');
        };

        document.querySelectorAll('[data-gallery-index]').forEach((btn) => {
            btn.addEventListener('click', () => {
                openLightbox(parseInt(btn.getAttribute('data-gallery-index') || '0', 10));
            });
        });

        btnClose?.addEventListener('click', closeLightbox);
        lightbox?.addEventListener('click', (e) => {
            if (e.target === lightbox || e.target.classList.contains('public-gallery-backdrop')) {
                closeLightbox();
            }
        });
        img?.addEventListener('click', closeLightbox);
        btnPrev?.addEventListener('click', () => openLightbox(current - 1));
        btnNext?.addEventListener('click', () => openLightbox(current + 1));
    });
</script>
</body>
</html>

