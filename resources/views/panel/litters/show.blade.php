@extends('layouts.panel')

@section('title', 'Profil miotu')

@section('content')
    @php
        $litter = $page->litter;
        $sales = $page->salesSummary;
        $timeline = $page->timeline;
        $planning = $timeline['planning'] ?? [];
        $galleryPhotos = $litter['gallery_photos'] ?? [];
        $maleAvatar = $litter['parent_male']['avatar_url'] ?? null;
        $femaleAvatar = $litter['parent_female']['avatar_url'] ?? null;
        $offspringEditMode = $offspringEditMode ?? false;
        $openGalleryModal = $openGalleryModal ?? false;
        $avatarFallback = static function (?string $name): string {
            $clean = trim(strip_tags((string) $name));
            return $clean !== '' ? strtoupper(substr($clean, 0, 1)) : '?';
        };
    @endphp

    <div class="profile-hero mb-0" style="--profile-hero-image: url('{{ $litter['banner_image_url'] }}'); margin-top: -20px">
        <div class="h-100 d-flex align-items-end" style="margin-top: -50px;">
            <div class="w-100 p-4" style="">
                <div class="d-flex justify-content-between align-items-start gap-2" >
                    <div>
                        <h1 class="display-6 fw-bold mb-1">{{ $litter['code'] }}</h1>
                        <p class="h5 mb-2">Miot @if($litter['season']) - Sezon {{ $litter['season'] }}@endif</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light btn-sm rounded-circle" data-bs-toggle="modal" data-bs-target="#litterGalleryModal" aria-label="Galeria">
                            <i class="bi bi-image"></i>
                        </button>
                        <a href="{{ route('panel.litters.index') }}" class="btn btn-light btn-sm rounded-circle" aria-label="Powrot">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="glass-card mb-3" style="margin-top: -40px; position: relative; z-index: 3;">
        <div class="card-body py-3 px-3 px-lg-4">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-lg-4 d-flex align-items-center gap-3">
                    @if ($maleAvatar)
                        <img src="{{ $maleAvatar }}" alt="Samiec" class="rounded-circle border border-2 border-light" style="width: 84px; height: 84px; object-fit: cover;">
                    @else
                        <div class="rounded-circle border border-2 border-light d-flex align-items-center justify-content-center" style="width: 84px; height: 84px;">
                            {{ $avatarFallback($litter['parent_male']['name'] ?? null) }}
                        </div>
                    @endif
                    <div>
                        <div class="small text-muted text-uppercase">Samiec</div>
                        @if ($litter['parent_male']['id'])
                            <a href="{{ $litter['parent_male']['url'] }}" class="link-light text-decoration-none fw-semibold">
                                {{ $litter['parent_male']['name'] }}
                            </a>
                        @else
                            <span class="text-muted">Brak przypisania</span>
                        @endif
                    </div>
                </div>

                <div class="col-12 col-lg-4 text-center">
                    <div class="fw-semibold">MALUCHY W MIOCIE {{ $sales['offspring_count'] }}</div>
                    <div class="text-muted small">Sprzedane / Na sprzedaz: {{ $sales['sold_count'] }} / {{ $sales['for_sale_count'] }}</div>
                </div>

                <div class="col-12 col-lg-4 d-flex align-items-center justify-content-lg-end gap-3">
                    <div class="text-lg-end order-2 order-lg-1">
                        <div class="small text-muted text-uppercase">Samica</div>
                        @if ($litter['parent_female']['id'])
                            <a href="{{ $litter['parent_female']['url'] }}" class="link-light text-decoration-none fw-semibold">
                                {{ $litter['parent_female']['name'] }}
                            </a>
                        @else
                            <span class="text-muted">Brak przypisania</span>
                        @endif
                    </div>
                    @if ($femaleAvatar)
                        <img src="{{ $femaleAvatar }}" alt="Samica" class="rounded-circle border border-2 border-light order-1 order-lg-2" style="width: 84px; height: 84px; object-fit: cover;">
                    @else
                        <div class="rounded-circle border border-2 border-light d-flex align-items-center justify-content-center order-1 order-lg-2" style="width: 84px; height: 84px;">
                            {{ $avatarFallback($litter['parent_female']['name'] ?? null) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-8">
            <div class="glass-card glass-table-wrapper">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="strike flex-grow-1"><span>Dane miotu {{ $litter['code'] }}</span></div>
                    <a href="{{ route('panel.litters.edit', $litter['id']) }}" class="btn btn-link text-light p-0 ms-2" aria-label="Edytuj">
                        <i class="bi bi-pencil-square"></i>
                    </a>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="small text-muted">Kategoria</div>
                            <div>{{ $litter['category_label'] }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="small text-muted">Status</div>
                            <div>{{ $litter['status_label'] }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="small text-muted">Kod miotu</div>
                            <div>{{ $litter['code'] }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="small text-muted">Sezon</div>
                            <div>{{ $litter['season'] ?: '-' }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="small text-muted">Samiec</div>
                            <div>
                                @if (!empty($litter['parent_male']['url']))
                                    <a href="{{ $litter['parent_male']['url'] }}" class="link-light text-decoration-none">
                                        {{ $litter['parent_male']['name'] }}
                                    </a>
                                @else
                                    {{ $litter['parent_male']['name'] }}
                                @endif
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="small text-muted">Samica</div>
                            <div>
                                @if (!empty($litter['parent_female']['url']))
                                    <a href="{{ $litter['parent_female']['url'] }}" class="link-light text-decoration-none">
                                        {{ $litter['parent_female']['name'] }}
                                    </a>
                                @else
                                    {{ $litter['parent_female']['name'] }}
                                @endif
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="small text-muted">Planowana data laczenia</div>
                            <div>{{ $litter['planned_connection_date'] ?: '-' }}</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="small text-muted">Data laczenia</div>
                            <div>{{ $litter['connection_date'] ?: '-' }}</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="small text-muted">Data zniosu</div>
                            <div>{{ $litter['laying_date'] ?: ($timeline['estimated_laying_date'] ? 'plan. ' . $timeline['estimated_laying_date'] : '-') }}</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="small text-muted">Data wyklucia</div>
                            <div>{{ $litter['hatching_date'] ?: ($timeline['estimated_hatching_date'] ? 'plan. ' . $timeline['estimated_hatching_date'] : '-') }}</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="small text-muted">Ilosc zniesionych jaj</div>
                            <div>{{ $litter['laying_eggs_total'] }} szt.</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="small text-muted">Ilosc jaj do inkubacji</div>
                            <div>{{ $litter['laying_eggs_ok'] }} szt.</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="small text-muted">Ilosc wyklutych</div>
                            <div>{{ $litter['hatching_eggs'] }} szt.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="glass-card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="strike flex-grow-1"><span>Adnotacje</span></div>
                    <button type="button" class="btn btn-link text-light p-0 ms-2" data-bs-toggle="modal" data-bs-target="#editAdnotationModal" aria-label="Edytuj adnotacje">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                </div>
                <div class="card-body">
                    @if ($litter['adnotation'])
                        <p class="mb-0 text-break">{!! nl2br(e($litter['adnotation'])) !!}</p>
                    @else
                        <span class="text-muted">Brak notatek.</span>
                    @endif
                </div>
            </div>

            <div class="glass-card">
                <div class="card-header">
                    <div class="strike"><span>Planowanie</span></div>
                </div>
                <div class="card-body">
                    <form id="litterPlanningForm" method="GET" action="{{ route('panel.litters.show', $litter['id']) }}" class="d-flex flex-column gap-2">
                        <input type="hidden" name="planning_source" id="planningSourceField" value="{{ $planning['source'] ?? 'connection' }}">
                        @if ($offspringEditMode)
                            <input type="hidden" name="edit_offspring" value="1">
                        @endif

                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Data laczenia</span>
                            <input
                                type="date"
                                class="form-control"
                                name="planning_connection_date"
                                data-planning-source="connection"
                                value="{{ $planning['connection_date'] ?? '' }}"
                            >
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Data zniosu</span>
                            <input
                                type="date"
                                class="form-control"
                                name="planning_laying_date"
                                data-planning-source="laying"
                                value="{{ $planning['laying_date'] ?? '' }}"
                            >
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Data wyklucia</span>
                            <input
                                type="date"
                                class="form-control"
                                name="planning_hatching_date"
                                data-planning-source="hatching"
                                value="{{ $planning['hatching_date'] ?? '' }}"
                            >
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-outline-light btn-sm">Aktualizuj</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="offspringSection" class="glass-card glass-table-wrapper mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="strike flex-grow-1"><span>Potomstwo</span></div>
            <div class="d-flex align-items-center gap-2 ms-2">
                @if ($offspringEditMode)
                    <a href="{{ route('panel.litters.show', $litter['id']) }}#offspringSection" class="btn btn-link text-warning p-0" aria-label="Anuluj edycje">
                        <i class="bi bi-x-circle"></i>
                    </a>
                    <button type="submit" form="offspringBulkEditForm" class="btn btn-link text-success p-0" aria-label="Zapisz edycje">
                        <i class="bi bi-floppy"></i>
                    </button>
                @else
                    <a href="{{ route('panel.litters.show', ['litter' => $litter['id'], 'edit_offspring' => 1]) }}#offspringSection" class="btn btn-link text-light p-0" aria-label="Edycja masowa potomstwa">
                        <i class="bi bi-pencil-fill"></i>
                    </a>
                @endif
                <button type="button" class="btn btn-link text-light p-0" data-bs-toggle="modal" data-bs-target="#addOffspringModal" aria-label="Dodaj potomstwo">
                    <i class="bi bi-plus-circle"></i>
                </button>
            </div>
        </div>
        @if ($offspringEditMode)
            <form id="offspringBulkEditForm" method="POST" action="{{ route('panel.litters.offspring.update-batch', $litter['id']) }}">
                @csrf
                @method('PUT')
                <div class="table-responsive">
                    <table class="table glass-table table-sm align-middle mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th>ID</th>
                                <th>Nazwa</th>
                                <th>Plec</th>
                                <th>Waga</th>
                                <th>Karmienia</th>
                                <th>Data wyklucia</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($page->offspring as $index => $row)
                                <tr>
                                    <td>
                                        <a href="{{ $row['animal_profile_url'] }}" class="link-light text-decoration-none">
                                            {{ $row['id'] }}
                                        </a>
                                        <input type="hidden" name="rows[{{ $index }}][id]" value="{{ $row['id'] }}">
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            name="rows[{{ $index }}][name]"
                                            class="form-control form-control-sm @error('rows.' . $index . '.name', 'litterOffspringBatch') is-invalid @enderror"
                                            value="{{ old('rows.' . $index . '.name', $row['name']) }}"
                                        >
                                    </td>
                                    <td>
                                        <select
                                            name="rows[{{ $index }}][sex]"
                                            class="form-select form-select-sm @error('rows.' . $index . '.sex', 'litterOffspringBatch') is-invalid @enderror"
                                        >
                                            <option value="1" @selected((string) old('rows.' . $index . '.sex', $row['sex_value']) === '1')>N/sex</option>
                                            <option value="2" @selected((string) old('rows.' . $index . '.sex', $row['sex_value']) === '2')>Samiec</option>
                                            <option value="3" @selected((string) old('rows.' . $index . '.sex', $row['sex_value']) === '3')>Samica</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            name="rows[{{ $index }}][weight]"
                                            class="form-control form-control-sm @error('rows.' . $index . '.weight', 'litterOffspringBatch') is-invalid @enderror"
                                            value="{{ old('rows.' . $index . '.weight', $row['weight_value']) }}"
                                        >
                                    </td>
                                    <td>{{ $row['feedings_count'] }}</td>
                                    <td>{{ $row['date_of_birth'] }}</td>
                                    <td>{{ $row['status'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">Brak potomstwa w miocie.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
            @if ($errors->getBag('litterOffspringBatch')->any())
                <div class="card-body border-top border-opacity-10 border-light small text-danger">
                    @foreach ($errors->getBag('litterOffspringBatch')->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif
        @else
            <div class="table-responsive">
                <table class="table glass-table table-sm align-middle mb-0">
                    <thead>
                        <tr class="text-muted small">
                            <th>ID</th>
                            <th>Nazwa</th>
                            <th>Plec</th>
                            <th>Waga</th>
                            <th>Karmienia</th>
                            <th>Data wyklucia</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($page->offspring as $row)
                            <tr>
                                <td>
                                    <a href="{{ $row['animal_profile_url'] }}" class="link-light text-decoration-none">
                                        {{ $row['id'] }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ $row['animal_profile_url'] }}" class="link-light text-decoration-none">
                                        {{ $row['name'] }}
                                    </a>
                                </td>
                                <td>{{ $row['sex'] }}</td>
                                <td>{{ $row['weight'] }}</td>
                                <td>{{ $row['feedings_count'] }}</td>
                                <td>{{ $row['date_of_birth'] }}</td>
                                <td>{{ $row['status'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">Brak potomstwa w miocie.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="glass-card glass-table-wrapper">
                <div class="card-header">
                    <div class="strike"><span>Planowane potomstwo - algorytm</span></div>
                </div>
                <div class="table-responsive">
                    <table class="table glass-table table-sm align-middle mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th style="width: 12%;">Procent</th>
                                <th style="width: 20%;">Nazwa</th>
                                <th>Traits</th>
                                <th style="width: 10%;">#Traits</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($page->pairings as $row)
                                <tr>
                                    <td>{{ number_format($row['percentage'], 2, ',', ' ') }}%</td>
                                    <td>
                                        @if (!empty($row['traits_name']))
                                            <span class="badge text-bg-light litter-trait-badge">{{ $row['traits_name'] }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @foreach ($row['visual_traits'] as $trait)
                                            <span class="badge text-bg-success litter-trait-badge">{{ $trait }}</span>
                                        @endforeach
                                        @foreach ($row['carrier_traits'] as $trait)
                                            <span class="badge litter-trait-badge @if(str_starts_with($trait, '50%')) text-bg-secondary @elseif(str_starts_with($trait, '66%')) text-bg-info @else text-bg-primary @endif">{{ $trait }}</span>
                                        @endforeach
                                    </td>
                                    <td>{{ $row['traits_count'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Brak danych o mozliwym potomstwie.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="glass-card glass-table-wrapper">
                <div class="card-header">
                    <div class="strike"><span>Historia sprzedazy miotu ({{ $sales['sold_count'] }} / {{ $sales['for_sale_count'] }})</span></div>
                </div>
                <div class="table-responsive">
                    <table class="table glass-table table-sm align-middle mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th>ID</th>
                                <th>Nazwa</th>
                                <th class="text-center">Cena</th>
                                <th class="text-center">Data sprzedazy</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sales['sold_rows'] as $sold)
                                <tr>
                                    <td>{{ $sold['id'] }}</td>
                                    <td>
                                        <a href="{{ $sold['animal_profile_url'] }}" class="link-light text-decoration-none">
                                            {{ $sold['name'] }}
                                        </a>
                                    </td>
                                    <td class="text-center">{{ $sold['sold_price_label'] ?: '-' }}</td>
                                    <td class="text-center">{{ $sold['sold_date'] ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Brak zrealizowanych sprzedazy dla tego miotu.</td>
                                </tr>
                            @endforelse
                            @if (!empty($sales['sold_rows']))
                                <tr class="border-top">
                                    <td colspan="2" class="text-end text-uppercase small text-white-50">Suma</td>
                                    <td class="text-center fw-semibold">{{ $sales['sold_revenue_label'] }}</td>
                                    <td></td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addOffspringModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Dodaj zwierzeta do miotu</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                </div>
                <form method="POST" action="{{ route('panel.litters.offspring.store', $litter['id']) }}">
                    @csrf
                    <div class="modal-body">
                        <label class="small text-muted mb-1">Ilosc</label>
                        <input type="number" min="1" max="100" name="amount" value="1" class="form-control form-control-sm @error('amount', 'litterOffspring') is-invalid @enderror" required>
                        @if ($errors->getBag('litterOffspring')->any())
                            <div class="small text-danger mt-2">
                                @foreach ($errors->getBag('litterOffspring')->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">Anuluj</button>
                        <button type="submit" class="btn btn-primary btn-sm">Dodaj</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editAdnotationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Edycja adnotacji</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                </div>
                <form method="POST" action="{{ route('panel.litters.adnotation.update', $litter['id']) }}">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        <textarea
                            name="adnotation"
                            rows="8"
                            class="form-control form-control-sm @error('adnotation', 'litterAdnotation') is-invalid @enderror"
                            placeholder="Wpisz adnotacje..."
                        >{{ old('adnotation', $litter['adnotation']) }}</textarea>
                        @if ($errors->getBag('litterAdnotation')->any())
                            <div class="small text-danger mt-2">
                                @foreach ($errors->getBag('litterAdnotation')->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif
                        <div class="small text-muted mt-2">Puste pole usunie adnotacje.</div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">Anuluj</button>
                        <button type="submit" class="btn btn-primary btn-sm">Zapisz</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="litterGalleryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content bg-dark text-light border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Galeria miotu</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('panel.litters.gallery.store', $litter['id']) }}" enctype="multipart/form-data" class="mb-3">
                        @csrf
                        <label class="small text-muted mb-1">Dodaj zdjecie (max 10MB, automatyczne skalowanie do FullHD)</label>
                        <div class="input-group input-group-sm">
                            <input type="file" name="photo" class="form-control @error('photo', 'litterGallery') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp" required>
                            <button class="btn btn-primary" type="submit">Dodaj</button>
                        </div>
                        @if ($errors->getBag('litterGallery')->any())
                            <div class="small text-danger mt-2">
                                @foreach ($errors->getBag('litterGallery')->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif
                    </form>

                    <div class="small text-muted mb-3">Banner glowny jest zawsze ustawiany na najnowsze zdjecie z galerii.</div>
                    <div class="row g-3">
                        @forelse ($galleryPhotos as $photo)
                            <div class="col-12 col-md-4">
                                <div class="glass-card p-2 h-100">
                                    <img src="{{ $photo['url'] }}" class="w-100 rounded" style="height: 220px; object-fit: cover;" alt="Zdjecie miotu">
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        @if ($loop->first)
                                            <div class="small text-success">Najnowsze (banner)</div>
                                        @else
                                            <span></span>
                                        @endif
                                        <form method="POST" action="{{ $photo['delete_url'] }}" onsubmit="return confirm('Usunac to zdjecie?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link text-danger p-0" aria-label="Usun zdjecie">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="text-muted">Brak zdjec w galerii miotu.</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .litter-trait-badge {
            font-size: 0.72rem;
            padding: 0.18rem 0.45rem;
            line-height: 1.1;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sourceField = document.getElementById('planningSourceField');
            const planningForm = document.getElementById('litterPlanningForm');
            if (!sourceField) return;

            const planningInputs = document.querySelectorAll('[data-planning-source]');
            const connectionInput = document.querySelector('input[name="planning_connection_date"]');
            const layingInput = document.querySelector('input[name="planning_laying_date"]');
            const hatchingInput = document.querySelector('input[name="planning_hatching_date"]');
            const layingDuration = {{ (int) ($planning['laying_duration_days'] ?? 0) }};
            const hatchlingDuration = {{ (int) ($planning['hatchling_duration_days'] ?? 0) }};

            const parseYmd = (value) => {
                if (!value || !/^\d{4}-\d{2}-\d{2}$/.test(value)) return null;
                const [y, m, d] = value.split('-').map(Number);
                return new Date(Date.UTC(y, m - 1, d));
            };

            const formatYmd = (date) => {
                if (!date) return '';
                const y = date.getUTCFullYear();
                const m = String(date.getUTCMonth() + 1).padStart(2, '0');
                const d = String(date.getUTCDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            };

            const addDays = (date, days) => {
                if (!date) return null;
                const next = new Date(date.getTime());
                next.setUTCDate(next.getUTCDate() + days);
                return next;
            };

            const recalcPlanning = () => {
                const source = sourceField.value || 'connection';
                let connection = parseYmd(connectionInput?.value || '');
                let laying = parseYmd(layingInput?.value || '');
                let hatching = parseYmd(hatchingInput?.value || '');

                if (source === 'connection' && connection) {
                    laying = addDays(connection, layingDuration);
                    hatching = addDays(laying, hatchlingDuration);
                } else if (source === 'laying' && laying) {
                    connection = addDays(laying, -layingDuration);
                    hatching = addDays(laying, hatchlingDuration);
                } else if (source === 'hatching' && hatching) {
                    laying = addDays(hatching, -hatchlingDuration);
                    connection = addDays(laying, -layingDuration);
                } else {
                    if (!laying && connection) {
                        laying = addDays(connection, layingDuration);
                    }
                    if (!hatching && laying) {
                        hatching = addDays(laying, hatchlingDuration);
                    }
                }

                if (connectionInput) connectionInput.value = formatYmd(connection);
                if (layingInput) layingInput.value = formatYmd(laying);
                if (hatchingInput) hatchingInput.value = formatYmd(hatching);
            };

            planningInputs.forEach((input) => {
                input.addEventListener('change', function () {
                    sourceField.value = this.getAttribute('data-planning-source') || 'connection';
                });
            });

            if (planningForm) {
                planningForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    recalcPlanning();
                });
            }
        });
    </script>

    @if ($errors->getBag('litterAdnotation')->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalElement = document.getElementById('editAdnotationModal');
                if (!modalElement || !window.bootstrap) return;
                const modal = new window.bootstrap.Modal(modalElement);
                modal.show();
            });
        </script>
    @endif
    @if ($openGalleryModal || $errors->getBag('litterGallery')->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalElement = document.getElementById('litterGalleryModal');
                if (!modalElement || !window.bootstrap) return;
                const modal = new window.bootstrap.Modal(modalElement);
                modal.show();
            });
        </script>
    @endif
@endpush
