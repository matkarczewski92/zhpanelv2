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
        <div class="small text-muted">
            Pozycji: <span id="winteringsCount">{{ count($rows) }}</span>
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
        });
    </script>
@endpush

