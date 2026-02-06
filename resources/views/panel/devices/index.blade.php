@extends('layouts.panel')

@section('title', 'Urzadzenia')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Urzadzenia</h1>
            <p class="text-muted mb-0">Podglad danych z eWeLink dla urzadzen skonfigurowanych w Ustawieniach portalu.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <form method="POST" action="{{ route('panel.devices.refresh') }}">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">Odswiez dane</button>
            </form>
        </div>
    </div>

    <div class="glass-card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-3 small">
            <div>
                <span class="text-muted">Stan autoryzacji:</span>
                <strong id="devicesAuthState">{{ $hasToken ? 'polaczono' : 'brak tokenu' }}</strong>
            </div>
            <div>
                <span class="text-muted">Region:</span>
                <strong id="devicesRegion">{{ $savedRegion ?: '-' }}</strong>
            </div>
            <div class="w-100 text-muted">
                Redirect URL (musi byc 1:1 w eWeLink): <code>{{ config('services.ewelink.redirect_url') ?: route('panel.devices.callback') }}</code>
            </div>
            <div class="w-100 text-muted">
                Auto-odswiezanie: co 10s. Ostatnia aktualizacja: <strong id="devicesAutoRefreshTime">-</strong>
            </div>
            <div id="devicesAutoRefreshError" class="w-100 text-warning d-none"></div>
        </div>
    </div>

    <div class="glass-card glass-table-wrapper">
        <div class="card-header">
            <div class="strike"><span>Lista urzadzen</span></div>
        </div>
        <div class="table-responsive">
            <table class="table glass-table table-sm align-middle mb-0">
                <thead>
                    <tr class="text-muted small">
                        <th>ID urzadzenia</th>
                        <th>Nazwa</th>
                        <th>Typ</th>
                        <th>Online</th>
                        <th>Temperatura</th>
                        <th>Wilgotnosc</th>
                        <th>Stan ON/OFF</th>
                        <th>Przelaczniki</th>
                        <th>Zakres / cel</th>
                        <th>Harmonogram (Warszawa)</th>
                        <th>Ostatnia synchronizacja</th>
                    </tr>
                </thead>
                <tbody id="devicesTableBody">
                    @include('panel.devices._rows', ['rows' => $rows])
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const pollUrl = @json(route('panel.devices.data'));
            const tableBody = document.getElementById('devicesTableBody');
            const authState = document.getElementById('devicesAuthState');
            const region = document.getElementById('devicesRegion');
            const refreshTime = document.getElementById('devicesAutoRefreshTime');
            const refreshError = document.getElementById('devicesAutoRefreshError');
            const intervalMs = 10000;

            if (!tableBody) return;

            let inFlight = false;

            const showError = (message) => {
                if (!refreshError) return;

                if (!message) {
                    refreshError.textContent = '';
                    refreshError.classList.add('d-none');
                    return;
                }

                refreshError.textContent = message;
                refreshError.classList.remove('d-none');
            };

            const applyPayload = (payload) => {
                if (typeof payload.rows_html === 'string') {
                    tableBody.innerHTML = payload.rows_html;
                }

                if (authState) {
                    authState.textContent = payload.has_token ? 'polaczono' : 'brak tokenu';
                }

                if (region) {
                    region.textContent = payload.saved_region || '-';
                }

                if (refreshTime) {
                    refreshTime.textContent = payload.server_time || new Date().toLocaleString();
                }

                showError(payload.warning || '');
            };

            const poll = async () => {
                if (inFlight) return;
                inFlight = true;

                try {
                    const response = await fetch(pollUrl, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        cache: 'no-store',
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const payload = await response.json();
                    applyPayload(payload);
                } catch (error) {
                    const message = error && error.message ? error.message : 'blad polaczenia';
                    showError(`Auto-odswiezanie nieudane: ${message}`);
                } finally {
                    inFlight = false;
                }
            };

            if (refreshTime) {
                refreshTime.textContent = new Date().toLocaleString();
            }

            setInterval(poll, intervalMs);
            poll();
        })();
    </script>
@endpush
