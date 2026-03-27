@extends('layouts.panel')

@section('title', $page['title'])

@section('content')
    <div class="qr-scanner-page" data-qr-scanner>
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">{{ $page['title'] }}</h1>
                <p class="text-muted mb-0">{{ $page['subtitle'] }}</p>
            </div>
            <div class="small text-muted">Telefon: aparat tylny, szybkie wpisy i brak przeladowan.</div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-xl-8">
                <div class="card cardopacity qr-scanner-shell">
                    <div class="card-body">
                        <div class="qr-mode-switch mb-3" role="tablist" aria-label="Tryb skanera">
                            @foreach ($page['modes'] as $mode)
                                <button
                                    type="button"
                                    class="btn btn-outline-light qr-mode-button"
                                    data-role="qr-mode-button"
                                    data-mode="{{ $mode['key'] }}"
                                >
                                    {{ $mode['label'] }}
                                </button>
                            @endforeach
                        </div>

                        <div class="qr-status-panel mb-3">
                            <div>
                                <div class="qr-status-caption">Status</div>
                                <div class="fw-semibold" data-role="qr-status-label">Uruchamianie kamery...</div>
                            </div>
                            <span class="badge text-bg-secondary qr-state-badge" data-role="qr-state-badge">Start</span>
                        </div>

                        <div class="qr-camera-frame mb-3">
                            <video class="qr-camera-video" data-role="qr-video" playsinline muted autoplay></video>
                            <div class="qr-camera-overlay">
                                <div class="qr-camera-target"></div>
                                <div class="qr-camera-hint" data-role="qr-camera-hint">Ustaw kod QR w ramce.</div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="button" class="btn btn-outline-light" data-role="qr-camera-restart">
                                <i class="bi bi-camera-video me-1"></i> Ponow kamere
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-role="qr-clear-feedback">
                                <i class="bi bi-x-circle me-1"></i> Wyczyść komunikat
                            </button>
                        </div>

                        <div class="alert d-none mb-3" data-role="qr-feedback"></div>

                        <div class="card glass-card d-none qr-confirmation-panel" data-role="qr-confirmation-panel">
                            <div class="card-body">
                                <div class="fw-semibold mb-1">Potwierdzenie wymagane</div>
                                <div class="text-muted small mb-3" data-role="qr-confirmation-text"></div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-warning" data-role="qr-confirm-button">Dodaj mimo duplikatu</button>
                                    <button type="button" class="btn btn-outline-light" data-role="qr-confirm-cancel">Anuluj</button>
                                </div>
                            </div>
                        </div>

                        <div class="card glass-card d-none qr-weight-panel" data-role="qr-weight-sheet" aria-hidden="true">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                                    <div>
                                        <div class="fw-semibold">Szybki wpis wagi</div>
                                        <div class="text-muted small" data-role="qr-weight-animal">-</div>
                                    </div>
                                    <button type="button" class="btn btn-outline-light btn-sm" data-role="qr-weight-cancel">Zamknij</button>
                                </div>

                                <div class="alert alert-warning d-none py-2 mb-3" data-role="qr-weight-confirmation"></div>

                                <form data-role="qr-weight-form" class="d-flex flex-column gap-3">
                                    <div>
                                        <label class="form-label mb-1" for="qrWeightValue">Waga</label>
                                        <div class="input-group input-group-lg">
                                            <input
                                                id="qrWeightValue"
                                                type="number"
                                                step="0.01"
                                                min="0.01"
                                                inputmode="decimal"
                                                class="form-control"
                                                placeholder="np. 542.5"
                                                data-role="qr-weight-input"
                                            >
                                            <span class="input-group-text">g</span>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary flex-grow-1" data-role="qr-weight-submit">Zapisz wage</button>
                                        <button type="button" class="btn btn-outline-light" data-role="qr-weight-cancel">Anuluj</button>
                                    </div>

                                    <div class="d-none" data-role="qr-weight-duplicate-actions">
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-warning flex-grow-1" data-role="qr-weight-confirm-submit">Dodaj mimo duplikatu</button>
                                            <button type="button" class="btn btn-outline-light" data-role="qr-weight-duplicate-cancel">Wroc do wpisu</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card cardopacity h-100">
                    <div class="card-body d-flex flex-column gap-3">
                        <div>
                            <div class="fw-semibold mb-1" data-role="qr-mode-title">Karmienie</div>
                            <div class="text-muted small mb-0" data-role="qr-mode-description"></div>
                        </div>

                        <form data-role="qr-manual-form" class="d-flex flex-column gap-2">
                            <label class="form-label mb-0" for="qrManualPayload">Reczny kod / URL</label>
                            <input
                                id="qrManualPayload"
                                type="text"
                                class="form-control"
                                placeholder="https://www.makssnake.pl/profile/ebe5 lub ebe5"
                                autocomplete="off"
                                data-role="qr-manual-input"
                            >
                            <button type="submit" class="btn btn-outline-light" data-role="qr-manual-submit">Dodaj karmienie</button>
                        </form>

                        <div class="small text-muted">
                            Akceptowane: pelny URL profilu `makssnake.pl/profile/{tag}` lub sam bezpieczny tag.
                        </div>

                        <div>
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <div class="fw-semibold mb-0">Ostatnie akcje</div>
                                <button type="button" class="btn btn-outline-light btn-sm d-none" data-role="qr-session-summary-button">
                                    Wygeneruj podsumowanie
                                </button>
                            </div>
                            <div class="list-group list-group-flush qr-activity-list" data-role="qr-activity-list">
                                <div class="list-group-item text-muted small">Brak akcji w tej sesji.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script type="application/json" data-role="qr-scanner-config">@json($page['config'])</script>
    </div>
@endsection
