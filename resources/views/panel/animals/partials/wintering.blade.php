@php
    $wintering = $profile->wintering ?? [];
    $editor = $wintering['editor'] ?? [];
    $editorHasCycle = (bool) ($editor['has_cycle'] ?? false);
    $schemes = is_array($editor['schemes'] ?? null) ? $editor['schemes'] : [];
    $rowsFromOld = old('rows');
    $rows = (is_array($rowsFromOld) && $rowsFromOld !== []) ? $rowsFromOld : ($editor['rows'] ?? []);
    $selectedScheme = (string) old('scheme', $editor['selected_scheme'] ?? '');
    $initialStartDate = (string) old('wintering_anchor_date', $editor['initial_start_date'] ?? now()->toDateString());
    $showTableStep = $editorHasCycle || (is_array($rows) && $rows !== []);
    $history = is_array($wintering['history'] ?? null) ? $wintering['history'] : [];
@endphp

<div class="card cardopacity mb-3" id="wintering">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span>Zimowanie</span>
        <div class="d-flex align-items-center gap-2">
            <button
                type="button"
                class="btn btn-outline-light btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#winteringHistoryModal"
                title="Historia zimowan"
                aria-label="Historia zimowan"
            >
                <i class="bi bi-clock-history"></i>
            </button>
            <button
                type="button"
                class="btn btn-outline-light btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#winteringEditModal"
                title="Edytuj zimowanie"
                aria-label="Edytuj zimowanie"
            >
                <i class="bi bi-pencil-square"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        @if (($wintering['exists'] ?? false))
            <dl class="row mb-0 small">
                <dt class="col-6 text-muted">Schemat</dt>
                <dd class="col-6">{{ $wintering['scheme'] ?? '-' }}</dd>
                <dt class="col-6 text-muted">Etap</dt>
                <dd class="col-6">{{ $wintering['stage'] ?? '-' }}</dd>
                <dt class="col-6 text-muted">Sezon</dt>
                <dd class="col-6">{{ $wintering['season'] ?? '-' }}</dd>
                <dt class="col-6 text-muted">Start</dt>
                <dd class="col-6 {{ ($wintering['start_is_real'] ?? false) ? 'text-success' : 'text-muted' }}">
                    {{ $wintering['start'] ?? '-' }}
                </dd>
                <dt class="col-6 text-muted">Koniec</dt>
                <dd class="col-6 {{ ($wintering['end_is_real'] ?? false) ? 'text-success' : 'text-muted' }}">
                    {{ $wintering['end'] ?? '-' }}
                </dd>
                <dt class="col-6 text-muted">Uwagi</dt>
                <dd class="col-6">{{ $wintering['notes'] ?? '-' }}</dd>
            </dl>
        @else
            <div class="text-muted small">Brak aktywnego zimowania.</div>
        @endif
    </div>
</div>

<div class="modal fade" id="winteringHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content photobg">
            <div class="modal-header">
                <h5 class="modal-title">Historia zimowan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <div class="modal-body">
                @if ($history === [])
                    <div class="text-muted small">Brak zapisanej historii zimowan.</div>
                @else
                    @foreach ($history as $cycle)
                        <div class="card glass-card mb-3">
                            <div class="card-body">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <span class="badge text-bg-secondary">Sezon {{ $cycle['season'] ?? '-' }}</span>
                                    <span class="badge text-bg-dark">{{ $cycle['scheme'] ?? '-' }}</span>
                                    @if (($cycle['is_current'] ?? false))
                                        <span class="badge text-bg-primary">Biezacy cykl</span>
                                    @endif
                                    @if (($cycle['is_active'] ?? false))
                                        <span class="badge text-bg-success">Aktywny</span>
                                    @endif
                                </div>
                                <div class="row g-2 small mb-2">
                                    <div class="col-md-4">
                                        <span class="text-muted">Etap: </span>
                                        <span>{{ $cycle['stage'] ?? '-' }}</span>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="text-muted">Start: </span>
                                        <span class="{{ ($cycle['start_is_real'] ?? false) ? 'text-success' : 'text-muted' }}">
                                            {{ $cycle['start'] ?? '-' }}
                                        </span>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="text-muted">Koniec: </span>
                                        <span class="{{ ($cycle['end_is_real'] ?? false) ? 'text-success' : 'text-muted' }}">
                                            {{ $cycle['end'] ?? '-' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table glass-table table-sm align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 45%;">Etap</th>
                                                <th style="width: 27%;">Start</th>
                                                <th style="width: 27%;">Koniec</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach (($cycle['rows'] ?? []) as $row)
                                                <tr>
                                                    <td>{{ $row['stage_order'] ?? 0 }}. {{ $row['stage_title'] ?? '-' }}</td>
                                                    <td class="{{ ($row['start_is_real'] ?? false) ? 'text-success' : 'text-muted' }}">
                                                        {{ $row['start'] ?? '-' }}
                                                    </td>
                                                    <td class="{{ ($row['end_is_real'] ?? false) ? 'text-success' : 'text-muted' }}">
                                                        {{ $row['end'] ?? '-' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zamknij</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="winteringEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content photobg">
            <div class="modal-header">
                <h5 class="modal-title">Edycja zimowania</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <form method="POST" action="{{ $editor['save_url'] ?? '#' }}" id="winteringPlanForm">
                @csrf
                <div class="modal-body">
                    @if (!$editorHasCycle)
                        <div id="winteringSchemeStep" class="@if($showTableStep) d-none @endif">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-6">
                                    <label class="form-label" for="winteringSchemeSelect">Schemat zimowania</label>
                                    <select id="winteringSchemeSelect" class="form-select">
                                        <option value="">Wybierz schemat</option>
                                        @foreach(array_keys($schemes) as $schemeName)
                                            <option value="{{ $schemeName }}" @selected($selectedScheme === $schemeName)>{{ $schemeName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="winteringAnchorDate">Data rozpoczecia</label>
                                    <input id="winteringAnchorDate" type="date" class="form-control" value="{{ $initialStartDate }}">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-primary w-100" id="winteringBuildStagesBtn">
                                        Dalej
                                    </button>
                                </div>
                            </div>
                            <div class="small text-muted mt-2">
                                Wybierz schemat i date startu, a system wyliczy daty etapow.
                            </div>
                        </div>
                    @endif

                    <div id="winteringTableStep" class="@if(!$showTableStep) d-none @endif">
                        <input type="hidden" name="scheme" id="winteringSchemeHidden" value="{{ $selectedScheme }}">
                        <div class="table-responsive">
                            <table class="table glass-table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 24%;">Etap</th>
                                        <th style="width: 12%;">Czas (dni)</th>
                                        <th style="width: 18%;">Plan start</th>
                                        <th style="width: 18%;">Plan koniec</th>
                                        <th style="width: 12%;">Start</th>
                                        <th style="width: 12%;">Koniec</th>
                                        <th class="text-end" style="width: 18%;">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody
                                    id="winteringRowsBody"
                                    data-schemes='@json($schemes)'
                                    data-has-cycle="{{ $editorHasCycle ? '1' : '0' }}"
                                >
                                    @foreach($rows as $index => $row)
                                        @php
                                            $rowMeta = $editor['rows'][$index] ?? [];
                                            $stageOrder = (int) ($row['stage_order'] ?? $rowMeta['stage_order'] ?? 0);
                                            $stageTitle = (string) ($row['stage_title'] ?? $rowMeta['stage_title'] ?? '');
                                            $defaultDuration = (int) ($row['default_duration'] ?? $rowMeta['default_duration'] ?? 0);
                                            $customDuration = $row['custom_duration'] ?? $rowMeta['custom_duration'] ?? null;
                                            $plannedStart = (string) ($row['planned_start_date'] ?? $rowMeta['planned_start_date'] ?? '');
                                            $plannedEnd = (string) ($row['planned_end_date'] ?? $rowMeta['planned_end_date'] ?? '');
                                            $realStart = (string) ($row['start_date'] ?? $rowMeta['start_date'] ?? '');
                                            $realEnd = (string) ($row['end_date'] ?? $rowMeta['end_date'] ?? '');
                                            $winteringId = (int) ($row['wintering_id'] ?? $rowMeta['wintering_id'] ?? 0);
                                            $stageId = (int) ($row['stage_id'] ?? $rowMeta['stage_id'] ?? 0);
                                        @endphp
                                        <tr data-wintering-row>
                                            <td>
                                                <div class="fw-semibold">{{ $stageOrder }}. {{ $stageTitle }}</div>
                                                <input type="hidden" name="rows[{{ $index }}][wintering_id]" value="{{ $winteringId ?: '' }}">
                                                <input type="hidden" name="rows[{{ $index }}][stage_id]" value="{{ $stageId }}">
                                                <input type="hidden" class="wintering-default-duration" value="{{ $defaultDuration }}">
                                            </td>
                                            <td>
                                                <input
                                                    type="number"
                                                    min="0"
                                                    class="form-control form-control-sm wintering-custom-duration"
                                                    name="rows[{{ $index }}][custom_duration]"
                                                    value="{{ $customDuration ?? '' }}"
                                                >
                                                <div class="small text-muted mt-1">Domyslnie: {{ $defaultDuration }}</div>
                                            </td>
                                            <td>
                                                <input
                                                    type="date"
                                                    class="form-control form-control-sm wintering-planned-start"
                                                    name="rows[{{ $index }}][planned_start_date]"
                                                    value="{{ $plannedStart }}"
                                                >
                                            </td>
                                            <td>
                                                <input
                                                    type="date"
                                                    class="form-control form-control-sm wintering-planned-end"
                                                    name="rows[{{ $index }}][planned_end_date]"
                                                    value="{{ $plannedEnd }}"
                                                >
                                            </td>
                                            <td>
                                                <input
                                                    type="date"
                                                    class="form-control form-control-sm wintering-start-date"
                                                    name="rows[{{ $index }}][start_date]"
                                                    value="{{ $realStart }}"
                                                >
                                            </td>
                                            <td>
                                                <input
                                                    type="date"
                                                    class="form-control form-control-sm wintering-end-date"
                                                    name="rows[{{ $index }}][end_date]"
                                                    value="{{ $realEnd }}"
                                                >
                                            </td>
                                            <td class="text-end">
                                                @if ($editorHasCycle && $winteringId > 0)
                                                    <div class="d-flex justify-content-end gap-1">
                                                        <button
                                                            type="submit"
                                                            class="btn btn-outline-success btn-sm"
                                                            form="winteringStageStartForm-{{ $winteringId }}"
                                                            title="Rozpocznij etap"
                                                        >
                                                            Rozpocznij etap
                                                        </button>
                                                        <button
                                                            type="submit"
                                                            class="btn btn-outline-danger btn-sm"
                                                            form="winteringStageEndForm-{{ $winteringId }}"
                                                            title="Zakoncz etap"
                                                        >
                                                            Zakoncz etap
                                                        </button>
                                                    </div>
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="small text-muted mt-2">
                            Po zmianie dat planowanych lub realnych (Start/Koniec), kolejne etapy sa przeliczane automatycznie.
                            Przycisk "Aktualizuj daty" przelicza harmonogram w tyl i w przod wzgledem ostatnio edytowanej daty.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Anuluj</button>
                    <button
                        type="button"
                        class="btn btn-outline-info"
                        id="winteringRecalculateBtn"
                        @if(!$showTableStep) disabled @endif
                    >
                        Aktualizuj daty
                    </button>
                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="winteringSaveBtn"
                        @if(!$showTableStep) disabled @endif
                    >
                        Zapisz zimowanie
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($editorHasCycle)
    @foreach (($editor['rows'] ?? []) as $row)
        @if (!empty($row['wintering_id']) && !empty($row['start_url']) && !empty($row['end_url']))
            <form id="winteringStageStartForm-{{ $row['wintering_id'] }}" method="POST" action="{{ $row['start_url'] }}">
                @csrf
            </form>
            <form id="winteringStageEndForm-{{ $row['wintering_id'] }}" method="POST" action="{{ $row['end_url'] }}">
                @csrf
            </form>
        @endif
    @endforeach
@endif

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('winteringEditModal');
            const rowsBody = document.getElementById('winteringRowsBody');
            if (!modal || !rowsBody) {
                return;
            }

            const schemeStep = document.getElementById('winteringSchemeStep');
            const tableStep = document.getElementById('winteringTableStep');
            const schemeSelect = document.getElementById('winteringSchemeSelect');
            const schemeHidden = document.getElementById('winteringSchemeHidden');
            const anchorDateInput = document.getElementById('winteringAnchorDate');
            const buildButton = document.getElementById('winteringBuildStagesBtn');
            const saveButton = document.getElementById('winteringSaveBtn');
            const recalculateButton = document.getElementById('winteringRecalculateBtn');
            const hasCycle = rowsBody.getAttribute('data-has-cycle') === '1';
            let lastEditedAnchor = null;

            let schemes = {};
            try {
                schemes = JSON.parse(rowsBody.getAttribute('data-schemes') || '{}');
            } catch (e) {
                schemes = {};
            }

            const parseDate = (value) => {
                if (!value || typeof value !== 'string') return null;
                const date = new Date(value + 'T00:00:00');
                return Number.isNaN(date.getTime()) ? null : date;
            };

            const formatDate = (date) => {
                if (!(date instanceof Date) || Number.isNaN(date.getTime())) return '';
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            };

            const addDays = (date, days) => {
                const copy = new Date(date.getTime());
                copy.setDate(copy.getDate() + days);
                return copy;
            };

            const escapeHtml = (value) => {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };

            const rowElements = () => Array.from(rowsBody.querySelectorAll('[data-wintering-row]'));

            const rowDuration = (rowEl) => {
                const customInput = rowEl.querySelector('.wintering-custom-duration');
                const defaultInput = rowEl.querySelector('.wintering-default-duration');
                const customValue = parseInt(customInput?.value || '', 10);
                if (!Number.isNaN(customValue) && customValue >= 0) {
                    return customValue;
                }

                const defaultValue = parseInt(defaultInput?.value || '0', 10);
                return Number.isNaN(defaultValue) ? 0 : Math.max(0, defaultValue);
            };

            const recalculateFrom = (startIndex, changedField) => {
                const rows = rowElements();
                if (!rows.length) {
                    return;
                }

                let anchorStart = null;
                const anchorRow = rows[startIndex];
                const anchorStartInput = anchorRow?.querySelector('.wintering-planned-start');
                const anchorEndInput = anchorRow?.querySelector('.wintering-planned-end');
                const anchorDuration = rowDuration(anchorRow);

                if (changedField === 'end') {
                    const endDate = parseDate(anchorEndInput?.value || '');
                    if (endDate) {
                        anchorStart = addDays(endDate, -anchorDuration);
                    }
                }

                if (!anchorStart) {
                    anchorStart = parseDate(anchorStartInput?.value || '');
                }

                if (!anchorStart && startIndex > 0) {
                    const previousEnd = rows[startIndex - 1].querySelector('.wintering-planned-end');
                    anchorStart = parseDate(previousEnd?.value || '');
                }

                if (!anchorStart) {
                    anchorStart = parseDate(anchorDateInput?.value || '') || new Date();
                }

                for (let i = startIndex; i < rows.length; i++) {
                    const row = rows[i];
                    const startInput = row.querySelector('.wintering-planned-start');
                    const endInput = row.querySelector('.wintering-planned-end');
                    const duration = rowDuration(row);

                    if (i > startIndex) {
                        const prevEndInput = rows[i - 1].querySelector('.wintering-planned-end');
                        const prevEndDate = parseDate(prevEndInput?.value || '');
                        if (prevEndDate) {
                            anchorStart = prevEndDate;
                        }
                    }

                    const endDate = addDays(anchorStart, duration);
                    if (startInput) startInput.value = formatDate(anchorStart);
                    if (endInput) endInput.value = formatDate(endDate);
                }
            };

            const resolveAnchorStart = (rows, anchorIndex, anchorField) => {
                const anchorRow = rows[anchorIndex];
                const anchorDuration = rowDuration(anchorRow);
                const plannedStartInput = anchorRow?.querySelector('.wintering-planned-start');
                const plannedEndInput = anchorRow?.querySelector('.wintering-planned-end');
                const realStartInput = anchorRow?.querySelector('.wintering-start-date');
                const realEndInput = anchorRow?.querySelector('.wintering-end-date');

                const readStart = () => {
                    return parseDate(plannedStartInput?.value || '') || parseDate(realStartInput?.value || '');
                };
                const readEnd = () => {
                    return parseDate(plannedEndInput?.value || '') || parseDate(realEndInput?.value || '');
                };

                if (anchorField === 'planned-end' || anchorField === 'end') {
                    const endDate = readEnd();
                    if (endDate) {
                        return addDays(endDate, -anchorDuration);
                    }
                }

                const startDate = readStart();
                if (startDate) {
                    return startDate;
                }

                const endDate = readEnd();
                if (endDate) {
                    return addDays(endDate, -anchorDuration);
                }

                if (anchorIndex > 0) {
                    const previousEnd = rows[anchorIndex - 1]?.querySelector('.wintering-planned-end');
                    const previousEndDate = parseDate(previousEnd?.value || '');
                    if (previousEndDate) {
                        return previousEndDate;
                    }
                }

                return parseDate(anchorDateInput?.value || '') || new Date();
            };

            const recalculateAroundAnchor = (anchorIndex, anchorField) => {
                const rows = rowElements();
                if (!rows.length) {
                    return;
                }

                let safeAnchorIndex = parseInt(String(anchorIndex), 10);
                if (Number.isNaN(safeAnchorIndex) || safeAnchorIndex < 0 || safeAnchorIndex >= rows.length) {
                    safeAnchorIndex = 0;
                }

                const anchorRow = rows[safeAnchorIndex];
                if (anchorField === 'start' || anchorField === 'planned-start') {
                    const realStartInput = anchorRow?.querySelector('.wintering-start-date');
                    const plannedStartInput = anchorRow?.querySelector('.wintering-planned-start');
                    const value = realStartInput?.value || plannedStartInput?.value || '';
                    if (plannedStartInput && value) {
                        plannedStartInput.value = value;
                    }
                }

                if (anchorField === 'end' || anchorField === 'planned-end') {
                    const realEndInput = anchorRow?.querySelector('.wintering-end-date');
                    const plannedEndInput = anchorRow?.querySelector('.wintering-planned-end');
                    const value = realEndInput?.value || plannedEndInput?.value || '';
                    if (plannedEndInput && value) {
                        plannedEndInput.value = value;
                    }
                }

                let anchorStart = resolveAnchorStart(rows, safeAnchorIndex, anchorField);
                const anchorDuration = rowDuration(anchorRow);
                const anchorEnd = addDays(anchorStart, anchorDuration);
                const anchorStartInput = anchorRow?.querySelector('.wintering-planned-start');
                const anchorEndInput = anchorRow?.querySelector('.wintering-planned-end');
                if (anchorStartInput) {
                    anchorStartInput.value = formatDate(anchorStart);
                }
                if (anchorEndInput) {
                    anchorEndInput.value = formatDate(anchorEnd);
                }

                for (let i = safeAnchorIndex + 1; i < rows.length; i++) {
                    const prevEndInput = rows[i - 1]?.querySelector('.wintering-planned-end');
                    const prevEndDate = parseDate(prevEndInput?.value || '');
                    if (!prevEndDate) {
                        continue;
                    }

                    const row = rows[i];
                    const duration = rowDuration(row);
                    const startInput = row.querySelector('.wintering-planned-start');
                    const endInput = row.querySelector('.wintering-planned-end');
                    const endDate = addDays(prevEndDate, duration);
                    if (startInput) {
                        startInput.value = formatDate(prevEndDate);
                    }
                    if (endInput) {
                        endInput.value = formatDate(endDate);
                    }
                }

                for (let i = safeAnchorIndex - 1; i >= 0; i--) {
                    const nextStartInput = rows[i + 1]?.querySelector('.wintering-planned-start');
                    const nextStartDate = parseDate(nextStartInput?.value || '');
                    if (!nextStartDate) {
                        continue;
                    }

                    const row = rows[i];
                    const duration = rowDuration(row);
                    const startDate = addDays(nextStartDate, -duration);
                    const startInput = row.querySelector('.wintering-planned-start');
                    const endInput = row.querySelector('.wintering-planned-end');
                    if (startInput) {
                        startInput.value = formatDate(startDate);
                    }
                    if (endInput) {
                        endInput.value = formatDate(nextStartDate);
                    }
                }
            };

            const bindRecalcEvents = () => {
                rowElements().forEach((row, index) => {
                    row.querySelector('.wintering-planned-start')?.addEventListener('change', () => {
                        lastEditedAnchor = { index, field: 'planned-start' };
                        recalculateFrom(index, 'start');
                    });
                    row.querySelector('.wintering-planned-end')?.addEventListener('change', () => {
                        lastEditedAnchor = { index, field: 'planned-end' };
                        recalculateFrom(index, 'end');
                    });
                    row.querySelector('.wintering-custom-duration')?.addEventListener('change', () => {
                        lastEditedAnchor = { index, field: 'planned-start' };
                        recalculateFrom(index, 'duration');
                    });

                    row.querySelector('.wintering-start-date')?.addEventListener('change', (event) => {
                        const currentStartValue = event.target?.value || '';
                        if (index > 0 && currentStartValue !== '') {
                            const previousRow = rowElements()[index - 1];
                            const previousEndInput = previousRow?.querySelector('.wintering-end-date');
                            if (previousEndInput && previousEndInput.value === '') {
                                previousEndInput.value = currentStartValue;
                            }
                        }

                        const plannedInput = row.querySelector('.wintering-planned-start');
                        if (plannedInput) {
                            plannedInput.value = currentStartValue;
                        }

                        lastEditedAnchor = { index, field: 'start' };
                        recalculateFrom(index, 'start');
                    });

                    row.querySelector('.wintering-end-date')?.addEventListener('change', (event) => {
                        const plannedInput = row.querySelector('.wintering-planned-end');
                        if (plannedInput) {
                            plannedInput.value = event.target?.value || '';
                        }

                        lastEditedAnchor = { index, field: 'end' };
                        recalculateFrom(index, 'end');
                    });
                });
            };

            const buildRowsForScheme = (schemeName) => {
                const stages = Array.isArray(schemes[schemeName]) ? schemes[schemeName] : [];
                if (!stages.length) {
                    rowsBody.innerHTML = '';
                    return;
                }

                rowsBody.innerHTML = stages.map((stage, index) => {
                    const stageId = parseInt(stage.id, 10) || 0;
                    const stageOrder = parseInt(stage.order, 10) || 0;
                    const duration = parseInt(stage.duration, 10) || 0;
                    const stageTitle = escapeHtml(stage.title || '');

                    return `
                        <tr data-wintering-row>
                            <td>
                                <div class="fw-semibold">${stageOrder}. ${stageTitle}</div>
                                <input type="hidden" name="rows[${index}][wintering_id]" value="">
                                <input type="hidden" name="rows[${index}][stage_id]" value="${stageId}">
                                <input type="hidden" class="wintering-default-duration" value="${duration}">
                            </td>
                            <td>
                                <input type="number" min="0" class="form-control form-control-sm wintering-custom-duration" name="rows[${index}][custom_duration]" value="">
                                <div class="small text-muted mt-1">Domyslnie: ${duration}</div>
                            </td>
                            <td>
                                <input type="date" class="form-control form-control-sm wintering-planned-start" name="rows[${index}][planned_start_date]" value="">
                            </td>
                            <td>
                                <input type="date" class="form-control form-control-sm wintering-planned-end" name="rows[${index}][planned_end_date]" value="">
                            </td>
                            <td>
                                <input type="date" class="form-control form-control-sm wintering-start-date" name="rows[${index}][start_date]" value="">
                            </td>
                            <td>
                                <input type="date" class="form-control form-control-sm wintering-end-date" name="rows[${index}][end_date]" value="">
                            </td>
                            <td class="text-end"><span class="text-muted small">-</span></td>
                        </tr>
                    `;
                }).join('');

                bindRecalcEvents();
                recalculateFrom(0, 'start');
            };

            bindRecalcEvents();

            if (recalculateButton) {
                recalculateButton.addEventListener('click', () => {
                    const rows = rowElements();
                    if (!rows.length) {
                        return;
                    }

                    if (!lastEditedAnchor) {
                        const fallbackIndex = rows.findIndex((row) => {
                            const realStart = row.querySelector('.wintering-start-date')?.value || '';
                            const realEnd = row.querySelector('.wintering-end-date')?.value || '';
                            const plannedStart = row.querySelector('.wintering-planned-start')?.value || '';
                            const plannedEnd = row.querySelector('.wintering-planned-end')?.value || '';
                            return realStart !== '' || realEnd !== '' || plannedStart !== '' || plannedEnd !== '';
                        });

                        lastEditedAnchor = {
                            index: fallbackIndex >= 0 ? fallbackIndex : 0,
                            field: 'planned-start',
                        };
                    }

                    recalculateAroundAnchor(lastEditedAnchor.index, lastEditedAnchor.field);
                });
            }

            if (!hasCycle && buildButton && schemeSelect && tableStep && schemeHidden) {
                buildButton.addEventListener('click', () => {
                    const selected = schemeSelect.value || '';
                    if (!selected) {
                        schemeSelect.focus();
                        return;
                    }

                    schemeHidden.value = selected;
                    buildRowsForScheme(selected);
                    tableStep.classList.remove('d-none');
                    schemeStep?.classList.add('d-none');
                    if (saveButton) {
                        saveButton.disabled = false;
                    }
                    if (recalculateButton) {
                        recalculateButton.disabled = false;
                    }
                });
            }
        });
    </script>
@endpush
