@extends('layouts.panel')

@section('title', 'Urzadzenia')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Urzadzenia</h1>
            <p class="text-muted mb-0">Podglad danych z eWeLink dla urzadzen skonfigurowanych w Ustawieniach portalu.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button
                type="button"
                class="btn btn-outline-danger btn-sm"
                id="toggleAllSwitchesOffButton"
                data-url="{{ route('panel.devices.toggle-all-switches') }}"
            >
                Wylacz oswietlenie (wszystkie)
            </button>
            <button
                type="button"
                class="btn btn-outline-success btn-sm"
                id="toggleAllSwitchesOnButton"
                data-url="{{ route('panel.devices.toggle-all-switches') }}"
            >
                Wlacz oswietlenie (wszystkie)
            </button>
            <button
                type="button"
                class="btn btn-outline-info btn-sm"
                id="scheduleAllDevicesButton"
                data-url="{{ route('panel.devices.schedule-all') }}"
            >
                Wprowadz harmonogram do wybranych
            </button>
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

    <div class="modal fade" id="scheduleEditorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title">Edycja harmonogramu</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                </div>
                <form id="scheduleEditorForm">
                    <div class="modal-body">
                        <p class="small text-muted mb-3">
                            Urzadzenie: <strong id="scheduleEditorDeviceName">-</strong>
                        </p>
                        <div id="switchScheduleSection">
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label for="scheduleOnTime" class="form-label small text-muted mb-1">Godzina wlaczenia</label>
                                    <input id="scheduleOnTime" type="time" class="form-control form-control-sm bg-dark text-light">
                                </div>
                                <div class="col-6">
                                    <label for="scheduleOffTime" class="form-label small text-muted mb-1">Godzina wylaczenia</label>
                                    <input id="scheduleOffTime" type="time" class="form-control form-control-sm bg-dark text-light">
                                </div>
                            </div>
                            <label class="form-label small text-muted mb-1">Dni tygodnia</label>
                            <div class="d-flex flex-wrap gap-2 small" id="scheduleDaysGroup">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="dayPn">
                                    <label class="form-check-label" for="dayPn">Pn</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="2" id="dayWt">
                                    <label class="form-check-label" for="dayWt">Wt</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="3" id="daySr">
                                    <label class="form-check-label" for="daySr">Sr</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="4" id="dayCz">
                                    <label class="form-check-label" for="dayCz">Cz</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="5" id="dayPt">
                                    <label class="form-check-label" for="dayPt">Pt</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="6" id="daySb">
                                    <label class="form-check-label" for="daySb">Sb</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="0" id="dayNd">
                                    <label class="form-check-label" for="dayNd">Nd</label>
                                </div>
                            </div>
                        </div>

                        <div id="thermostatScheduleSection" class="d-none">
                            <div class="small text-muted mb-2">
                                Auto #n: dni, zakres godzin, temperatura wlaczenia i wylaczenia.
                            </div>
                            <div id="thermostatRulesContainer" class="d-flex flex-column gap-2"></div>
                            <button type="button" class="btn btn-outline-info btn-sm mt-2" id="addThermostatRule">
                                Dodaj Auto
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Anuluj</button>
                        <button type="submit" class="btn btn-success btn-sm" id="scheduleEditorSubmit">Zapisz harmonogram</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bulkScheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title">Harmonogram dla wybranych urzadzen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                </div>
                <form id="bulkScheduleForm">
                    <div class="modal-body">
                        <div id="bulkScheduleStepType">
                            <div class="small text-muted mb-2">Krok 1/2: wybierz typ urzadzen</div>
                            <div class="d-flex flex-column gap-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="bulkScheduleDeviceType" id="bulkScheduleTypeSwitch" value="switch" checked>
                                    <label class="form-check-label" for="bulkScheduleTypeSwitch">Przelacznik</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="bulkScheduleDeviceType" id="bulkScheduleTypeThermostat" value="thermostat">
                                    <label class="form-check-label" for="bulkScheduleTypeThermostat">Termostat</label>
                                </div>
                            </div>
                        </div>

                        <div id="bulkScheduleStepForm" class="d-none">
                            <div class="small text-muted mb-2">Krok 2/2: uzupelnij harmonogram</div>
                            <p class="small text-muted mb-1">
                                Typ: <strong id="bulkScheduleSelectedType">-</strong>
                            </p>
                            <p class="small text-muted mb-3">
                                Dane startowe: <strong id="bulkScheduleSeedSource">pierwsze urzadzenie danego typu</strong>
                            </p>
                            <div class="mb-3">
                                <label class="form-label small text-muted mb-1">Wybierz urzadzenia</label>
                                <div id="bulkScheduleDevicesList" class="border border-secondary rounded p-2 d-flex flex-column gap-2"></div>
                            </div>

                            <div id="bulkSwitchScheduleSection">
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label for="bulkScheduleOnTime" class="form-label small text-muted mb-1">Godzina wlaczenia</label>
                                        <input id="bulkScheduleOnTime" type="time" class="form-control form-control-sm bg-dark text-light">
                                    </div>
                                    <div class="col-6">
                                        <label for="bulkScheduleOffTime" class="form-label small text-muted mb-1">Godzina wylaczenia</label>
                                        <input id="bulkScheduleOffTime" type="time" class="form-control form-control-sm bg-dark text-light">
                                    </div>
                                </div>
                                <label class="form-label small text-muted mb-1">Dni tygodnia</label>
                                <div class="d-flex flex-wrap gap-2 small" id="bulkScheduleDaysGroup">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="bulkDayPn">
                                        <label class="form-check-label" for="bulkDayPn">Pn</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="2" id="bulkDayWt">
                                        <label class="form-check-label" for="bulkDayWt">Wt</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="3" id="bulkDaySr">
                                        <label class="form-check-label" for="bulkDaySr">Sr</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="4" id="bulkDayCz">
                                        <label class="form-check-label" for="bulkDayCz">Cz</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="5" id="bulkDayPt">
                                        <label class="form-check-label" for="bulkDayPt">Pt</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="6" id="bulkDaySb">
                                        <label class="form-check-label" for="bulkDaySb">Sb</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="0" id="bulkDayNd">
                                        <label class="form-check-label" for="bulkDayNd">Nd</label>
                                    </div>
                                </div>
                            </div>

                            <div id="bulkThermostatScheduleSection" class="d-none">
                                <div class="small text-muted mb-2">
                                    Auto #n: dni, zakres godzin, temperatura wlaczenia i wylaczenia.
                                </div>
                                <div id="bulkThermostatRulesContainer" class="d-flex flex-column gap-2"></div>
                                <button type="button" class="btn btn-outline-info btn-sm mt-2" id="addBulkThermostatRule">
                                    Dodaj Auto
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <div>
                            <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="bulkScheduleBackButton">Wstecz</button>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Anuluj</button>
                            <button type="button" class="btn btn-info btn-sm" id="bulkScheduleNextButton">Dalej</button>
                            <button type="submit" class="btn btn-success btn-sm d-none" id="bulkScheduleSubmitButton">Zapisz harmonogram</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const pollUrl = @json(route('panel.devices.data'));
            const tableBody = document.getElementById('devicesTableBody');
            const authState = document.getElementById('devicesAuthState');
            const region = document.getElementById('devicesRegion');
            const refreshTime = document.getElementById('devicesAutoRefreshTime');
            const refreshError = document.getElementById('devicesAutoRefreshError');
            const toastContainer = document.getElementById('globalToastContainer');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const scheduleModalEl = document.getElementById('scheduleEditorModal');
            const scheduleForm = document.getElementById('scheduleEditorForm');
            const scheduleOnTimeInput = document.getElementById('scheduleOnTime');
            const scheduleOffTimeInput = document.getElementById('scheduleOffTime');
            const scheduleDeviceName = document.getElementById('scheduleEditorDeviceName');
            const scheduleDaysGroup = document.getElementById('scheduleDaysGroup');
            const switchScheduleSection = document.getElementById('switchScheduleSection');
            const thermostatScheduleSection = document.getElementById('thermostatScheduleSection');
            const thermostatRulesContainer = document.getElementById('thermostatRulesContainer');
            const addThermostatRuleButton = document.getElementById('addThermostatRule');
            const scheduleSubmitButton = document.getElementById('scheduleEditorSubmit');
            const toggleAllSwitchesOffButton = document.getElementById('toggleAllSwitchesOffButton');
            const toggleAllSwitchesOnButton = document.getElementById('toggleAllSwitchesOnButton');
            const scheduleAllDevicesButton = document.getElementById('scheduleAllDevicesButton');
            const bulkScheduleUrl = scheduleAllDevicesButton?.dataset.url || '';
            const bulkScheduleModalEl = document.getElementById('bulkScheduleModal');
            const bulkScheduleForm = document.getElementById('bulkScheduleForm');
            const bulkScheduleStepType = document.getElementById('bulkScheduleStepType');
            const bulkScheduleStepForm = document.getElementById('bulkScheduleStepForm');
            const bulkScheduleTypeSwitch = document.getElementById('bulkScheduleTypeSwitch');
            const bulkScheduleTypeThermostat = document.getElementById('bulkScheduleTypeThermostat');
            const bulkScheduleSelectedType = document.getElementById('bulkScheduleSelectedType');
            const bulkScheduleSeedSource = document.getElementById('bulkScheduleSeedSource');
            const bulkScheduleDevicesList = document.getElementById('bulkScheduleDevicesList');
            const bulkSwitchScheduleSection = document.getElementById('bulkSwitchScheduleSection');
            const bulkThermostatScheduleSection = document.getElementById('bulkThermostatScheduleSection');
            const bulkScheduleOnTimeInput = document.getElementById('bulkScheduleOnTime');
            const bulkScheduleOffTimeInput = document.getElementById('bulkScheduleOffTime');
            const bulkScheduleDaysGroup = document.getElementById('bulkScheduleDaysGroup');
            const bulkThermostatRulesContainer = document.getElementById('bulkThermostatRulesContainer');
            const addBulkThermostatRuleButton = document.getElementById('addBulkThermostatRule');
            const bulkScheduleBackButton = document.getElementById('bulkScheduleBackButton');
            const bulkScheduleNextButton = document.getElementById('bulkScheduleNextButton');
            const bulkScheduleSubmitButton = document.getElementById('bulkScheduleSubmitButton');
            const intervalMs = 10000;

            if (!tableBody) return;

            let inFlight = false;
            let actionInFlight = false;
            let currentScheduleUrl = '';
            let currentScheduleKind = 'switch_window';
            let bulkScheduleType = 'switch';
            const scheduleModal = scheduleModalEl && window.bootstrap
                ? new window.bootstrap.Modal(scheduleModalEl)
                : null;
            const bulkScheduleModal = bulkScheduleModalEl && window.bootstrap
                ? new window.bootstrap.Modal(bulkScheduleModalEl)
                : null;

            const toastClass = (type) => {
                switch (type) {
                    case 'success':
                        return 'bg-success text-white';
                    case 'danger':
                    case 'error':
                        return 'bg-danger text-white';
                    case 'warning':
                        return 'bg-warning text-dark';
                    default:
                        return 'bg-info text-white';
                }
            };

            const showToast = (message, type = 'info') => {
                if (!message || !toastContainer) {
                    return;
                }

                const wrapper = document.createElement('div');
                wrapper.className = `toast align-items-center ${toastClass(type)}`;
                wrapper.setAttribute('role', 'alert');
                wrapper.setAttribute('aria-live', 'assertive');
                wrapper.setAttribute('aria-atomic', 'true');

                const row = document.createElement('div');
                row.className = 'd-flex';

                const body = document.createElement('div');
                body.className = 'toast-body';
                body.textContent = message;

                const close = document.createElement('button');
                close.type = 'button';
                close.className = 'btn-close btn-close-white me-2 m-auto';
                close.setAttribute('data-bs-dismiss', 'toast');
                close.setAttribute('aria-label', 'Close');

                row.appendChild(body);
                row.appendChild(close);
                wrapper.appendChild(row);
                toastContainer.appendChild(wrapper);

                if (window.bootstrap && window.bootstrap.Toast) {
                    const bsToast = new window.bootstrap.Toast(wrapper, { delay: 5000, autohide: true });
                    bsToast.show();
                    wrapper.addEventListener('hidden.bs.toast', () => wrapper.remove());
                    return;
                }

                setTimeout(() => wrapper.remove(), 5000);
            };

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

            const toDaySet = (days) => {
                if (!Array.isArray(days)) {
                    return new Set([0, 1, 2, 3, 4, 5, 6]);
                }

                const parsed = days
                    .map((value) => Number.parseInt(value, 10))
                    .filter((value) => Number.isInteger(value) && value >= 0 && value <= 6);

                if (!parsed.length) {
                    return new Set([0, 1, 2, 3, 4, 5, 6]);
                }

                return new Set(parsed);
            };

            const applyDaysToCheckboxGroup = (group, days) => {
                if (!group) return;
                const daySet = toDaySet(days);

                group.querySelectorAll('input[type="checkbox"]').forEach((input) => {
                    const value = Number.parseInt(input.value, 10);
                    input.checked = daySet.has(value);
                });
            };

            const getSelectedDaysFromGroup = (group) => {
                if (!group) {
                    return [0, 1, 2, 3, 4, 5, 6];
                }

                const days = Array.from(group.querySelectorAll('input[type="checkbox"]:checked'))
                    .map((input) => Number.parseInt(input.value, 10))
                    .filter((value) => Number.isInteger(value) && value >= 0 && value <= 6)
                    .sort((a, b) => a - b);

                if (!days.length) {
                    throw new Error('Wybierz przynajmniej jeden dzien tygodnia.');
                }

                return Array.from(new Set(days));
            };

            const applyDaysToForm = (days) => applyDaysToCheckboxGroup(scheduleDaysGroup, days);
            const applyDaysToBulkForm = (days) => applyDaysToCheckboxGroup(bulkScheduleDaysGroup, days);
            const getSelectedDays = () => getSelectedDaysFromGroup(scheduleDaysGroup);
            const getSelectedBulkDays = () => getSelectedDaysFromGroup(bulkScheduleDaysGroup);

            const isThermostatType = (deviceType) => {
                return ['thermostat', 'thermostat_hygrostat'].includes((deviceType || '').toLowerCase());
            };

            const getDefaultThermostatRule = () => ({
                days: [0, 1, 2, 3, 4, 5, 6],
                from: '09:00',
                to: '21:00',
                on_temp: '25.0',
                off_temp: '25.5',
            });

            const dayLabels = [
                { value: 1, label: 'Pn' },
                { value: 2, label: 'Wt' },
                { value: 3, label: 'Sr' },
                { value: 4, label: 'Cz' },
                { value: 5, label: 'Pt' },
                { value: 6, label: 'Sb' },
                { value: 0, label: 'Nd' },
            ];

            const renderThermostatRulesInContainer = (container, rules, idPrefix) => {
                if (!container) {
                    return;
                }

                const normalizedRules = Array.isArray(rules) && rules.length
                    ? rules
                    : [getDefaultThermostatRule()];

                container.innerHTML = '';

                normalizedRules.forEach((rule, index) => {
                    const card = document.createElement('div');
                    card.className = 'border border-secondary rounded p-2 thermostat-rule-item';

                    const header = document.createElement('div');
                    header.className = 'd-flex justify-content-between align-items-center mb-2';

                    const title = document.createElement('strong');
                    title.className = 'small';
                    title.textContent = `Auto #${index + 1}`;

                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = 'btn btn-outline-danger btn-sm';
                    removeButton.textContent = 'Usun';
                    removeButton.addEventListener('click', () => {
                        const all = Array.from(container.querySelectorAll('.thermostat-rule-item'));
                        if (all.length <= 1) {
                            showToast('Musi zostac przynajmniej jedna regula Auto.', 'warning');
                            return;
                        }

                        card.remove();
                        const updated = Array.from(container.querySelectorAll('.thermostat-rule-item'));
                        updated.forEach((item, idx) => {
                            const heading = item.querySelector('.thermostat-rule-title');
                            if (heading) {
                                heading.textContent = `Auto #${idx + 1}`;
                            }
                        });
                    });

                    title.classList.add('thermostat-rule-title');
                    header.appendChild(title);
                    header.appendChild(removeButton);
                    card.appendChild(header);

                    const row = document.createElement('div');
                    row.className = 'row g-2 mb-2';

                    const fromCol = document.createElement('div');
                    fromCol.className = 'col-6';
                    fromCol.innerHTML = '<label class="form-label small text-muted mb-1">Od</label>';
                    const fromInput = document.createElement('input');
                    fromInput.type = 'time';
                    fromInput.className = 'form-control form-control-sm bg-dark text-light thermostat-rule-from';
                    fromInput.value = typeof rule.from === 'string' ? rule.from : '09:00';
                    fromCol.appendChild(fromInput);

                    const toCol = document.createElement('div');
                    toCol.className = 'col-6';
                    toCol.innerHTML = '<label class="form-label small text-muted mb-1">Do</label>';
                    const toInput = document.createElement('input');
                    toInput.type = 'time';
                    toInput.className = 'form-control form-control-sm bg-dark text-light thermostat-rule-to';
                    toInput.value = typeof rule.to === 'string' ? rule.to : '21:00';
                    toCol.appendChild(toInput);

                    const onTempCol = document.createElement('div');
                    onTempCol.className = 'col-6';
                    onTempCol.innerHTML = '<label class="form-label small text-muted mb-1">Temp. wlaczenia (ON)</label>';
                    const onTempInput = document.createElement('input');
                    onTempInput.type = 'number';
                    onTempInput.step = '0.1';
                    onTempInput.className = 'form-control form-control-sm bg-dark text-light thermostat-rule-on-temp';
                    onTempInput.value = String(rule.on_temp ?? '25.0').replace(',', '.');
                    onTempCol.appendChild(onTempInput);

                    const offTempCol = document.createElement('div');
                    offTempCol.className = 'col-6';
                    offTempCol.innerHTML = '<label class="form-label small text-muted mb-1">Temp. wylaczenia (OFF)</label>';
                    const offTempInput = document.createElement('input');
                    offTempInput.type = 'number';
                    offTempInput.step = '0.1';
                    offTempInput.className = 'form-control form-control-sm bg-dark text-light thermostat-rule-off-temp';
                    offTempInput.value = String(rule.off_temp ?? '25.5').replace(',', '.');
                    offTempCol.appendChild(offTempInput);

                    row.appendChild(fromCol);
                    row.appendChild(toCol);
                    row.appendChild(onTempCol);
                    row.appendChild(offTempCol);
                    card.appendChild(row);

                    const daysWrap = document.createElement('div');
                    daysWrap.className = 'd-flex flex-wrap gap-2 small';
                    const daySet = toDaySet(rule.days);

                    dayLabels.forEach((dayDef, dayIndex) => {
                        const checkWrap = document.createElement('div');
                        checkWrap.className = 'form-check';

                        const check = document.createElement('input');
                        check.type = 'checkbox';
                        check.className = 'form-check-input thermostat-rule-day';
                        check.value = String(dayDef.value);
                        check.id = `${idPrefix}-day-${index}-${dayIndex}`;
                        check.checked = daySet.has(dayDef.value);

                        const label = document.createElement('label');
                        label.className = 'form-check-label';
                        label.setAttribute('for', check.id);
                        label.textContent = dayDef.label;

                        checkWrap.appendChild(check);
                        checkWrap.appendChild(label);
                        daysWrap.appendChild(checkWrap);
                    });

                    card.appendChild(daysWrap);
                    container.appendChild(card);
                });
            };

            const collectThermostatRulesFromContainer = (container) => {
                if (!container) {
                    return [];
                }

                const items = Array.from(container.querySelectorAll('.thermostat-rule-item'));
                if (!items.length) {
                    throw new Error('Dodaj przynajmniej jedna regule Auto.');
                }

                return items.map((item, index) => {
                    const from = item.querySelector('.thermostat-rule-from')?.value || '';
                    const to = item.querySelector('.thermostat-rule-to')?.value || '';
                    const onTemp = item.querySelector('.thermostat-rule-on-temp')?.value || '';
                    const offTemp = item.querySelector('.thermostat-rule-off-temp')?.value || '';
                    const days = Array.from(item.querySelectorAll('.thermostat-rule-day:checked'))
                        .map((input) => Number.parseInt(input.value, 10))
                        .filter((value) => Number.isInteger(value) && value >= 0 && value <= 6)
                        .sort((a, b) => a - b);

                    if (!from || !to) {
                        throw new Error(`Podaj zakres godzin dla Auto #${index + 1}.`);
                    }

                    if (!onTemp || Number.isNaN(Number.parseFloat(onTemp))) {
                        throw new Error(`Podaj temperature wlaczenia dla Auto #${index + 1}.`);
                    }

                    if (!offTemp || Number.isNaN(Number.parseFloat(offTemp))) {
                        throw new Error(`Podaj temperature wylaczenia dla Auto #${index + 1}.`);
                    }

                    if (!days.length) {
                        throw new Error(`Wybierz przynajmniej jeden dzien dla Auto #${index + 1}.`);
                    }

                    return {
                        from,
                        to,
                        days: Array.from(new Set(days)),
                        on_temp: String(onTemp).replace(',', '.'),
                        off_temp: String(offTemp).replace(',', '.'),
                    };
                });
            };

            const renderThermostatRules = (rules) => {
                renderThermostatRulesInContainer(thermostatRulesContainer, rules, 'thermo');
            };

            const renderBulkThermostatRules = (rules) => {
                renderThermostatRulesInContainer(bulkThermostatRulesContainer, rules, 'bulk-thermo');
            };

            const collectThermostatRules = () => collectThermostatRulesFromContainer(thermostatRulesContainer);
            const collectBulkThermostatRules = () => collectThermostatRulesFromContainer(bulkThermostatRulesContainer);

            const collectRawThermostatRulesFromContainer = (container) => {
                const existing = [];
                if (!container) {
                    return existing;
                }

                container.querySelectorAll('.thermostat-rule-item').forEach((item) => {
                    const from = item.querySelector('.thermostat-rule-from')?.value || '09:00';
                    const to = item.querySelector('.thermostat-rule-to')?.value || '21:00';
                    const onTemp = item.querySelector('.thermostat-rule-on-temp')?.value || '25.0';
                    const offTemp = item.querySelector('.thermostat-rule-off-temp')?.value || '25.5';
                    const days = Array.from(item.querySelectorAll('.thermostat-rule-day:checked'))
                        .map((input) => Number.parseInt(input.value, 10))
                        .filter((value) => Number.isInteger(value) && value >= 0 && value <= 6);

                    existing.push({
                        from,
                        to,
                        on_temp: String(onTemp).replace(',', '.'),
                        off_temp: String(offTemp).replace(',', '.'),
                        days: days.length ? Array.from(new Set(days)) : [0, 1, 2, 3, 4, 5, 6],
                    });
                });

                return existing;
            };

            if (addThermostatRuleButton) {
                addThermostatRuleButton.addEventListener('click', () => {
                    const existing = collectRawThermostatRulesFromContainer(thermostatRulesContainer);
                    existing.push(getDefaultThermostatRule());
                    renderThermostatRules(existing);
                });
            }

            if (addBulkThermostatRuleButton) {
                addBulkThermostatRuleButton.addEventListener('click', () => {
                    const existing = collectRawThermostatRulesFromContainer(bulkThermostatRulesContainer);
                    existing.push(getDefaultThermostatRule());
                    renderBulkThermostatRules(existing);
                });
            }

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

            const resolveBulkScheduleType = () => (bulkScheduleTypeThermostat?.checked ? 'thermostat' : 'switch');

            const collectDevicesByType = (requestedType) => {
                const buttons = Array.from(tableBody.querySelectorAll('[data-device-schedule]'));
                const seen = new Set();
                const devices = [];

                buttons.forEach((button) => {
                    const type = (button.dataset.deviceType || '').toLowerCase();
                    const matchesType = requestedType === 'switch' ? type === 'switch' : isThermostatType(type);
                    if (!matchesType) {
                        return;
                    }

                    const id = Number.parseInt(button.dataset.deviceId || '', 10);
                    if (!Number.isInteger(id) || id <= 0 || seen.has(id)) {
                        return;
                    }

                    seen.add(id);
                    devices.push({
                        id,
                        name: button.dataset.deviceName || `Urzadzenie #${id}`,
                        seed: decodeScheduleSeed(button.dataset.schedule),
                    });
                });

                return devices;
            };

            const renderBulkDevicesList = (devices) => {
                if (!bulkScheduleDevicesList) {
                    return;
                }

                bulkScheduleDevicesList.innerHTML = '';

                if (!devices.length) {
                    const empty = document.createElement('div');
                    empty.className = 'small text-muted';
                    empty.textContent = 'Brak urzadzen wybranego typu.';
                    bulkScheduleDevicesList.appendChild(empty);
                    return;
                }

                devices.forEach((device, index) => {
                    const wrap = document.createElement('div');
                    wrap.className = 'form-check';

                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.className = 'form-check-input bulk-device-checkbox';
                    checkbox.id = `bulk-device-${device.id}-${index}`;
                    checkbox.value = String(device.id);
                    checkbox.checked = true;

                    const label = document.createElement('label');
                    label.className = 'form-check-label';
                    label.setAttribute('for', checkbox.id);
                    label.textContent = device.name;

                    wrap.appendChild(checkbox);
                    wrap.appendChild(label);
                    bulkScheduleDevicesList.appendChild(wrap);
                });
            };

            const getSelectedBulkDeviceIds = () => {
                if (!bulkScheduleDevicesList) {
                    return [];
                }

                const selected = Array.from(bulkScheduleDevicesList.querySelectorAll('.bulk-device-checkbox:checked'))
                    .map((input) => Number.parseInt(input.value, 10))
                    .filter((value) => Number.isInteger(value) && value > 0);

                if (!selected.length) {
                    throw new Error('Wybierz przynajmniej jedno urzadzenie.');
                }

                return Array.from(new Set(selected));
            };

            const setBulkModalStep = (step) => {
                const showTypeStep = step === 'type';
                if (bulkScheduleStepType) {
                    bulkScheduleStepType.classList.toggle('d-none', !showTypeStep);
                }
                if (bulkScheduleStepForm) {
                    bulkScheduleStepForm.classList.toggle('d-none', showTypeStep);
                }
                if (bulkScheduleBackButton) {
                    bulkScheduleBackButton.classList.toggle('d-none', showTypeStep);
                }
                if (bulkScheduleNextButton) {
                    bulkScheduleNextButton.classList.toggle('d-none', !showTypeStep);
                }
                if (bulkScheduleSubmitButton) {
                    bulkScheduleSubmitButton.classList.toggle('d-none', showTypeStep);
                }
            };

            const fillBulkScheduleForm = (requestedType) => {
                const devices = collectDevicesByType(requestedType);
                const seed = devices[0]?.seed || {};
                renderBulkDevicesList(devices);

                if (bulkScheduleSelectedType) {
                    bulkScheduleSelectedType.textContent = requestedType === 'switch' ? 'Przelacznik' : 'Termostat';
                }
                if (bulkScheduleSeedSource) {
                    bulkScheduleSeedSource.textContent = devices[0]
                        ? devices[0].name
                        : 'Brak urzadzenia tego typu (wartosci domyslne)';
                }
                if (bulkScheduleSubmitButton) {
                    bulkScheduleSubmitButton.disabled = devices.length === 0;
                }

                if (requestedType === 'thermostat') {
                    if (bulkSwitchScheduleSection) {
                        bulkSwitchScheduleSection.classList.add('d-none');
                    }
                    if (bulkThermostatScheduleSection) {
                        bulkThermostatScheduleSection.classList.remove('d-none');
                    }

                    const rules = Array.isArray(seed.rules) ? seed.rules : [];
                    renderBulkThermostatRules(rules);
                    return;
                }

                if (bulkThermostatScheduleSection) {
                    bulkThermostatScheduleSection.classList.add('d-none');
                }
                if (bulkSwitchScheduleSection) {
                    bulkSwitchScheduleSection.classList.remove('d-none');
                }

                if (bulkScheduleOnTimeInput) {
                    bulkScheduleOnTimeInput.value = typeof seed.on_time === 'string' ? seed.on_time : '09:00';
                }
                if (bulkScheduleOffTimeInput) {
                    bulkScheduleOffTimeInput.value = typeof seed.off_time === 'string' ? seed.off_time : '21:00';
                }
                applyDaysToBulkForm(seed.days);
            };

            const resetBulkScheduleModal = () => {
                bulkScheduleType = 'switch';
                if (bulkScheduleTypeSwitch) {
                    bulkScheduleTypeSwitch.checked = true;
                }
                if (bulkScheduleTypeThermostat) {
                    bulkScheduleTypeThermostat.checked = false;
                }
                if (bulkScheduleDevicesList) {
                    bulkScheduleDevicesList.innerHTML = '';
                }
                if (bulkScheduleSubmitButton) {
                    bulkScheduleSubmitButton.disabled = false;
                }
                setBulkModalStep('type');
            };

            const runBulkSwitchToggle = async (state, triggerButton) => {
                if (actionInFlight) return;
                if (!triggerButton?.dataset.url) {
                    showToast('Brak adresu akcji masowej.', 'danger');
                    return;
                }

                actionInFlight = true;
                if (toggleAllSwitchesOnButton) {
                    toggleAllSwitchesOnButton.disabled = true;
                }
                if (toggleAllSwitchesOffButton) {
                    toggleAllSwitchesOffButton.disabled = true;
                }

                try {
                    const result = await postAction(triggerButton.dataset.url, { state });
                    showError('');
                    showToast(result.message || 'Zmieniono stan urzadzen.', result.partial ? 'warning' : 'success');
                    await poll();
                } catch (error) {
                    const message = error && error.message ? error.message : 'blad sterowania';
                    showToast(`Sterowanie nieudane: ${message}`, 'danger');
                } finally {
                    if (toggleAllSwitchesOnButton) {
                        toggleAllSwitchesOnButton.disabled = false;
                    }
                    if (toggleAllSwitchesOffButton) {
                        toggleAllSwitchesOffButton.disabled = false;
                    }
                    actionInFlight = false;
                }
            };

            if (toggleAllSwitchesOnButton) {
                toggleAllSwitchesOnButton.addEventListener('click', () => runBulkSwitchToggle('on', toggleAllSwitchesOnButton));
            }

            if (toggleAllSwitchesOffButton) {
                toggleAllSwitchesOffButton.addEventListener('click', () => runBulkSwitchToggle('off', toggleAllSwitchesOffButton));
            }

            if (scheduleAllDevicesButton) {
                scheduleAllDevicesButton.addEventListener('click', () => {
                    if (actionInFlight) return;
                    if (!bulkScheduleModal) {
                        showToast('Brak modalu Bootstrap. Otworz strone ponownie.', 'danger');
                        return;
                    }

                    resetBulkScheduleModal();
                    bulkScheduleModal.show();
                });
            }

            if (bulkScheduleNextButton) {
                bulkScheduleNextButton.addEventListener('click', () => {
                    bulkScheduleType = resolveBulkScheduleType();
                    fillBulkScheduleForm(bulkScheduleType);
                    setBulkModalStep('form');
                });
            }

            if (bulkScheduleBackButton) {
                bulkScheduleBackButton.addEventListener('click', () => {
                    setBulkModalStep('type');
                });
            }

            if (bulkScheduleModalEl) {
                bulkScheduleModalEl.addEventListener('hidden.bs.modal', resetBulkScheduleModal);
            }

            tableBody.addEventListener('click', async (event) => {
                const toggleButton = event.target.closest('[data-device-toggle]');
                if (toggleButton) {
                    if (actionInFlight) return;
                    actionInFlight = true;
                    toggleButton.disabled = true;

                    try {
                        await postAction(toggleButton.dataset.url, { state: toggleButton.dataset.state });
                        showError('');
                        showToast('Zmieniono stan urzadzenia.', 'success');
                        await poll();
                    } catch (error) {
                        const message = error && error.message ? error.message : 'blad sterowania';
                        showToast(`Sterowanie nieudane: ${message}`, 'danger');
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
                const onTime = typeof seed.on_time === 'string' ? seed.on_time : '09:00';
                const offTime = typeof seed.off_time === 'string' ? seed.off_time : '21:00';
                const deviceType = (scheduleButton.dataset.deviceType || '').toLowerCase();
                const isThermostat = isThermostatType(deviceType);
                const seedKind = typeof seed.kind === 'string' ? seed.kind : '';
                currentScheduleKind = isThermostat ? 'thermostat_auto' : 'switch_window';
                if (seedKind === 'thermostat_auto' || (seedKind === 'switch_window' && !isThermostat)) {
                    currentScheduleKind = seedKind;
                }

                currentScheduleUrl = scheduleButton.dataset.url || '';
                if (scheduleDeviceName) {
                    scheduleDeviceName.textContent = scheduleButton.dataset.deviceName || '-';
                }

                if (currentScheduleKind === 'thermostat_auto') {
                    if (switchScheduleSection) {
                        switchScheduleSection.classList.add('d-none');
                    }
                    if (thermostatScheduleSection) {
                        thermostatScheduleSection.classList.remove('d-none');
                    }

                    const rules = Array.isArray(seed.rules) ? seed.rules : [];
                    renderThermostatRules(rules);
                } else {
                    if (thermostatScheduleSection) {
                        thermostatScheduleSection.classList.add('d-none');
                    }
                    if (switchScheduleSection) {
                        switchScheduleSection.classList.remove('d-none');
                    }

                    if (scheduleOnTimeInput) {
                        scheduleOnTimeInput.value = onTime;
                    }
                    if (scheduleOffTimeInput) {
                        scheduleOffTimeInput.value = offTime;
                    }
                    applyDaysToForm(seed.days);
                }

                if (scheduleModal) {
                    scheduleModal.show();
                    return;
                }

                showToast('Brak modalu Bootstrap. Otworz strone ponownie.', 'danger');
            });

            if (scheduleForm) {
                scheduleForm.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    if (actionInFlight) return;
                    if (!currentScheduleUrl) {
                        showToast('Brak adresu zapisu harmonogramu.', 'danger');
                        return;
                    }
                    let payload = {};

                    if (currentScheduleKind === 'thermostat_auto') {
                        try {
                            const rules = collectThermostatRules();
                            payload = {
                                human_schedule: {
                                    kind: 'thermostat_auto',
                                    rules,
                                },
                            };
                        } catch (error) {
                            const message = error && error.message ? error.message : 'Niepoprawne reguly Auto.';
                            showToast(message, 'warning');
                            return;
                        }
                    } else {
                        const onTime = scheduleOnTimeInput ? scheduleOnTimeInput.value : '';
                        const offTime = scheduleOffTimeInput ? scheduleOffTimeInput.value : '';
                        if (!onTime || !offTime) {
                            showToast('Podaj godzine wlaczenia i wylaczenia.', 'warning');
                            return;
                        }

                        let selectedDays = [];
                        try {
                            selectedDays = getSelectedDays();
                        } catch (error) {
                            const message = error && error.message ? error.message : 'Niepoprawne dni tygodnia.';
                            showToast(message, 'warning');
                            return;
                        }

                        payload = {
                            human_schedule: {
                                kind: 'switch_window',
                                on_time: onTime,
                                off_time: offTime,
                                days: selectedDays,
                            },
                        };
                    }

                    actionInFlight = true;
                    if (scheduleSubmitButton) {
                        scheduleSubmitButton.disabled = true;
                    }

                    try {
                        await postAction(currentScheduleUrl, payload);
                        showError('');
                        showToast('Harmonogram zapisany.', 'success');
                        if (scheduleModal) {
                            scheduleModal.hide();
                        }
                        await poll();
                    } catch (error) {
                        const message = error && error.message ? error.message : 'blad zapisu harmonogramu';
                        showToast(`Zapis harmonogramu nieudany: ${message}`, 'danger');
                    } finally {
                        if (scheduleSubmitButton) {
                            scheduleSubmitButton.disabled = false;
                        }
                        actionInFlight = false;
                    }
                });
            }

            if (bulkScheduleForm) {
                bulkScheduleForm.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    if (actionInFlight) return;
                    if (!bulkScheduleUrl) {
                        showToast('Brak adresu zapisu harmonogramu masowego.', 'danger');
                        return;
                    }

                    let payload = {
                        device_type: bulkScheduleType,
                        device_ids: [],
                        human_schedule: {},
                    };

                    try {
                        payload.device_ids = getSelectedBulkDeviceIds();
                    } catch (error) {
                        const message = error && error.message ? error.message : 'Wybierz urzadzenia.';
                        showToast(message, 'warning');
                        return;
                    }

                    if (bulkScheduleType === 'thermostat') {
                        try {
                            const rules = collectBulkThermostatRules();
                            payload.human_schedule = {
                                kind: 'thermostat_auto',
                                rules,
                            };
                        } catch (error) {
                            const message = error && error.message ? error.message : 'Niepoprawne reguly Auto.';
                            showToast(message, 'warning');
                            return;
                        }
                    } else {
                        const onTime = bulkScheduleOnTimeInput ? bulkScheduleOnTimeInput.value : '';
                        const offTime = bulkScheduleOffTimeInput ? bulkScheduleOffTimeInput.value : '';
                        if (!onTime || !offTime) {
                            showToast('Podaj godzine wlaczenia i wylaczenia.', 'warning');
                            return;
                        }

                        let selectedDays = [];
                        try {
                            selectedDays = getSelectedBulkDays();
                        } catch (error) {
                            const message = error && error.message ? error.message : 'Niepoprawne dni tygodnia.';
                            showToast(message, 'warning');
                            return;
                        }

                        payload.human_schedule = {
                            kind: 'switch_window',
                            on_time: onTime,
                            off_time: offTime,
                            days: selectedDays,
                        };
                    }

                    actionInFlight = true;
                    if (bulkScheduleSubmitButton) {
                        bulkScheduleSubmitButton.disabled = true;
                    }

                    try {
                        const result = await postAction(bulkScheduleUrl, payload);
                        showError('');
                        showToast(result.message || 'Harmonogram zapisany.', result.partial ? 'warning' : 'success');
                        if (bulkScheduleModal) {
                            bulkScheduleModal.hide();
                        }
                        await poll();
                    } catch (error) {
                        const message = error && error.message ? error.message : 'blad zapisu harmonogramu';
                        showToast(`Zapis harmonogramu nieudany: ${message}`, 'danger');
                    } finally {
                        if (bulkScheduleSubmitButton) {
                            bulkScheduleSubmitButton.disabled = false;
                        }
                        actionInFlight = false;
                    }
                });
            }

            setInterval(poll, intervalMs);
            poll();
        });
    </script>
@endpush
