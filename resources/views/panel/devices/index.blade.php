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
                        <th>Sterowanie</th>
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
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const intervalMs = 10000;

            if (!tableBody) return;

            let inFlight = false;
            let actionInFlight = false;

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

            const parseJsonResponse = async (response) => {
                const raw = await response.text();
                const cleaned = raw.replace(/^\uFEFF/, '').trim();

                if (!cleaned) {
                    return {};
                }

                try {
                    return JSON.parse(cleaned);
                } catch (error) {
                    const message = error && error.message ? error.message : 'invalid JSON';
                    throw new Error(`Niepoprawna odpowiedz JSON: ${message}`);
                }
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

                    const payload = await parseJsonResponse(response);
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

            const decodeScheduleSeed = (seed) => {
                if (!seed) {
                    return {};
                }

                try {
                    return JSON.parse(atob(seed));
                } catch (_) {
                    return {};
                }
            };

            const postAction = async (url, payload) => {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                    cache: 'no-store',
                });

                const json = await parseJsonResponse(response);

                if (!response.ok || json.ok === false) {
                    throw new Error(json.message || `HTTP ${response.status}`);
                }

                return json;
            };

            tableBody.addEventListener('click', async (event) => {
                const toggleButton = event.target.closest('[data-device-toggle]');
                if (toggleButton) {
                    if (actionInFlight) return;
                    actionInFlight = true;
                    toggleButton.disabled = true;

                    try {
                        await postAction(toggleButton.dataset.url, { state: toggleButton.dataset.state });
                        showError('');
                        await poll();
                    } catch (error) {
                        const message = error && error.message ? error.message : 'blad sterowania';
                        showError(`Sterowanie nieudane: ${message}`);
                    } finally {
                        toggleButton.disabled = false;
                        actionInFlight = false;
                    }
                    return;
                }

                const scheduleButton = event.target.closest('[data-device-schedule]');
                if (!scheduleButton) return;
                if (actionInFlight) return;

                const seed = decodeScheduleSeed(scheduleButton.dataset.schedule);
                const defaultValue = Object.keys(seed).length > 0
                    ? JSON.stringify(seed, null, 2)
                    : JSON.stringify({ timers: [] }, null, 2);
                const entered = window.prompt(
                    'Wklej JSON harmonogramu (timers/schedules/targets/workMode/workState):',
                    defaultValue
                );

                if (entered === null) return;

                actionInFlight = true;
                scheduleButton.disabled = true;

                try {
                    await postAction(scheduleButton.dataset.url, { schedule: entered });
                    showError('');
                    await poll();
                } catch (error) {
                    const message = error && error.message ? error.message : 'blad zapisu harmonogramu';
                    showError(`Zapis harmonogramu nieudany: ${message}`);
                } finally {
                    scheduleButton.disabled = false;
                    actionInFlight = false;
                }
            });

            setInterval(poll, intervalMs);
            poll();
        })();
    </script>
@endpush
