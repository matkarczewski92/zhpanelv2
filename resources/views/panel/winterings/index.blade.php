@extends('layouts.panel')

@section('title', 'Zimowanie')

@section('content')
    @php
        $rows = $page['rows'] ?? [];
    @endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Zimowanie</h1>
            <p class="text-muted mb-0">Lista aktywnych cykli zimowania i szybkie przejscie do kolejnego etapu.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button
                type="button"
                class="btn btn-outline-light btn-sm"
                id="winteringsPrintButton"
                aria-label="Drukuj tabele zimowania"
                title="Drukuj tabele"
            >
                <i class="bi bi-printer me-1"></i> Drukuj
            </button>
            <button
                type="button"
                class="btn btn-outline-info btn-sm"
                id="winteringsRecalculateAllButton"
                data-url="{{ route('panel.winterings.recalculate-dates') }}"
            >
                Aktualizuj wszystkie daty
            </button>
            <div class="small text-muted">
                Pozycji: <span id="winteringsCount">{{ count($rows) }}</span>
            </div>
        </div>
    </div>

    <div id="winteringsStatus" class="small mb-2 text-muted"></div>

    <div class="glass-card glass-table-wrapper" data-url="{{ route('panel.winterings.data') }}" id="winteringsBoard">
        <div class="table-responsive">
            <table class="table glass-table table-hover table-sm align-middle mb-0">
                <thead>
                    <tr class="text-muted small">
                        <th style="width: 60px;">ID</th>
                        <th>Waz</th>
                        <th style="width: 160px;">Schemat</th>
                        <th style="width: 90px;">Sezon</th>
                        <th style="width: 220px;">Biezacy etap</th>
                        <th style="width: 220px;">Nastepny etap</th>
                        <th style="width: 130px;">Start</th>
                        <th style="width: 130px;">Koniec</th>
                        <th class="text-end" style="width: 160px;">Akcja</th>
                    </tr>
                </thead>
                <tbody id="winteringsTableBody">
                    @include('panel.winterings._rows', ['rows' => $rows])
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const board = document.getElementById('winteringsBoard');
            const tableBody = document.getElementById('winteringsTableBody');
            const countEl = document.getElementById('winteringsCount');
            const statusEl = document.getElementById('winteringsStatus');
            const printButton = document.getElementById('winteringsPrintButton');
            const recalculateAllButton = document.getElementById('winteringsRecalculateAllButton');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const dataUrl = board?.getAttribute('data-url') || '';

            if (!board || !tableBody || !countEl || !statusEl || !dataUrl) {
                return;
            }

            const setStatus = (message, type = 'muted') => {
                statusEl.textContent = message || '';
                statusEl.classList.remove('text-muted', 'text-success', 'text-warning', 'text-danger');
                if (type === 'success') {
                    statusEl.classList.add('text-success');
                    return;
                }
                if (type === 'warning') {
                    statusEl.classList.add('text-warning');
                    return;
                }
                if (type === 'danger') {
                    statusEl.classList.add('text-danger');
                    return;
                }
                statusEl.classList.add('text-muted');
            };

            const refreshRows = async () => {
                const response = await fetch(dataUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(payload.message || 'Nie udalo sie odswiezyc danych zimowania.');
                }

                tableBody.innerHTML = payload.rows_html || '';
                countEl.textContent = String(payload.count ?? 0);
            };

            const postJson = async (url) => {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: '{}',
                });

                const payload = await response.json().catch(() => ({}));
                if (!response.ok || payload.ok === false) {
                    throw new Error(payload.message || 'Nie udalo sie wykonac operacji.');
                }

                return payload;
            };

            const normalizeLabel = (value) => {
                return (value || '')
                    .trim()
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '');
            };

            const printTable = () => {
                const sourceTable = board.querySelector('table');
                if (!sourceTable) {
                    return;
                }

                const table = sourceTable.cloneNode(true);
                const removableHeaders = new Set(['biezacy etap', 'akcja']);
                const originalHeaderCells = Array.from(table.querySelectorAll('thead th'));
                const columnIndexesToRemove = originalHeaderCells
                    .map((cell, index) => ({
                        index: index,
                        label: normalizeLabel(cell.textContent),
                    }))
                    .filter((item) => removableHeaders.has(item.label))
                    .map((item) => item.index)
                    .sort((a, b) => b - a);

                if (columnIndexesToRemove.length === 0) {
                    return;
                }

                const remainingColumnCount = Math.max(1, originalHeaderCells.length - columnIndexesToRemove.length);

                columnIndexesToRemove.forEach((columnIndex) => {
                    const headers = table.querySelectorAll('thead th');
                    if (headers[columnIndex]) {
                        headers[columnIndex].remove();
                    }
                });

                table.querySelectorAll('tbody tr').forEach((row) => {
                    const rowCells = row.querySelectorAll('td');
                    if (rowCells.length === 1 && rowCells[0].hasAttribute('colspan')) {
                        rowCells[0].setAttribute('colspan', String(remainingColumnCount));
                    } else {
                        columnIndexesToRemove.forEach((columnIndex) => {
                            const cells = row.querySelectorAll('td');
                            if (cells[columnIndex]) {
                                cells[columnIndex].remove();
                            }
                        });
                    }

                    row.querySelectorAll('a').forEach((link) => {
                        const text = document.createElement('span');
                        text.textContent = link.textContent.trim();
                        link.replaceWith(text);
                    });
                });

                const printWindow = window.open('', '_blank', 'width=1200,height=900');
                if (!printWindow) {
                    return;
                }

                const printedAt = new Date().toLocaleString('pl-PL');

                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Zimowanie</title>
                            <style>
                                @page { margin: 10mm; }
                                body { font-family: Arial, sans-serif; color: #111; margin: 0; font-size: 11px; }
                                h1 { font-size: 15px; margin: 0 0 8px; text-align: center; font-weight: 700; }
                                .meta { text-align: center; color: #555; font-size: 10px; margin-bottom: 10px; }
                                table { width: 100%; border-collapse: collapse; }
                                th, td { padding: 4px 6px; text-align: left; vertical-align: top; font-size: 10.5px; line-height: 1.25; border: 0; }
                                thead th { border-bottom: 1px solid #d6d6d6; font-weight: 700; }
                                tbody tr { border-bottom: 1px solid #f0f0f0; }
                                tbody tr:last-child { border-bottom: 0; }
                            </style>
                        </head>
                        <body>
                            <h1>Zimowanie</h1>
                            <div class="meta">Wydrukowano: ${printedAt}</div>
                            ${table.outerHTML}
                        </body>
                    </html>
                `);

                printWindow.document.close();
                printWindow.focus();
                printWindow.print();
                printWindow.close();
            };

            if (recalculateAllButton instanceof HTMLButtonElement) {
                recalculateAllButton.addEventListener('click', async () => {
                    const url = recalculateAllButton.getAttribute('data-url');
                    if (!url) {
                        return;
                    }

                    recalculateAllButton.disabled = true;
                    setStatus('Aktualizowanie dat zimowania...', 'muted');

                    try {
                        const payload = await postJson(url);
                        await refreshRows();
                        setStatus(payload.message || 'Zaktualizowano daty.', 'success');
                    } catch (error) {
                        setStatus(error instanceof Error ? error.message : 'Wystapil nieznany blad.', 'danger');
                    } finally {
                        recalculateAllButton.disabled = false;
                    }
                });
            }

            tableBody.addEventListener('click', async (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                const button = target.closest('.js-wintering-advance');
                if (!(button instanceof HTMLButtonElement)) {
                    return;
                }

                const url = button.getAttribute('data-url');
                if (!url) {
                    return;
                }

                button.disabled = true;
                setStatus('Zapisywanie etapu...', 'muted');

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: '{}',
                    });

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok || payload.ok === false) {
                        throw new Error(payload.message || 'Nie udalo sie rozpoczac etapu zimowania.');
                    }

                    await refreshRows();
                    setStatus(payload.message || 'Etap zimowania rozpoczety.', 'success');
                } catch (error) {
                    setStatus(error instanceof Error ? error.message : 'Wystapil nieznany blad.', 'danger');
                } finally {
                    button.disabled = false;
                }
            });

            if (printButton instanceof HTMLButtonElement) {
                printButton.addEventListener('click', printTable);
            }
        });
    </script>
@endpush
