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
@endphp

<div class="card cardopacity mb-3" id="wintering">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span>Zimowanie</span>
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
                                            <td class="{{ $realStart !== '' ? 'text-success' : 'text-muted' }}">
                                                {{ $realStart !== '' ? $realStart : '-' }}
                                            </td>
                                            <td class="{{ $realEnd !== '' ? 'text-success' : 'text-muted' }}">
                                                {{ $realEnd !== '' ? $realEnd : '-' }}
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
                            Po zmianie daty startu lub konca etapu, kolejne etapy sa przeliczane automatycznie.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Anuluj</button>
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
            const hasCycle = rowsBody.getAttribute('data-has-cycle') === '1';

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

            const bindRecalcEvents = () => {
                rowElements().forEach((row, index) => {
                    row.querySelector('.wintering-planned-start')?.addEventListener('change', () => recalculateFrom(index, 'start'));
                    row.querySelector('.wintering-planned-end')?.addEventListener('change', () => recalculateFrom(index, 'end'));
                    row.querySelector('.wintering-custom-duration')?.addEventListener('change', () => recalculateFrom(index, 'duration'));
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
                            <td class="text-muted">-</td>
                            <td class="text-muted">-</td>
                            <td class="text-end"><span class="text-muted small">-</span></td>
                        </tr>
                    `;
                }).join('');

                bindRecalcEvents();
                recalculateFrom(0, 'start');
            };

            bindRecalcEvents();

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
                });
            }
        });
    </script>
@endpush
