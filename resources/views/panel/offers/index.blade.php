@extends('layouts.panel')

@section('title', 'Oferty')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
        <h4 class="mb-0">Oferty</h4>
        <form id="bulkPassportForm" method="POST" action="{{ route('panel.offers.passports') }}" class="d-flex align-items-center gap-2 flex-wrap">
            @csrf
            <input type="hidden" name="animal_ids" id="bulkAnimalIds" />
            <button type="button" class="btn btn-outline-light btn-sm" id="bulkPassportBtn">Drukuj wybrane paszporty</button>
            <button type="button" class="btn btn-outline-light btn-sm ms-2" id="bulkLabelsBtn" data-export-url="{{ $offers->exportLabelsUrl }}">Eksportuj wybrane etykiety</button>
            <div class="form-check form-switch ms-2">
                <input class="form-check-input" type="checkbox" role="switch" id="editModeToggle">
                <label class="form-check-label" for="editModeToggle">Tryb edycji</label>
            </div>
            <div class="d-flex gap-2 ms-2 edit-mode-only d-none">
                <button type="button" class="btn btn-primary btn-sm" id="bulkSaveBtn" disabled>Zapisz zmiany</button>
                <button type="button" class="btn btn-secondary btn-sm" id="bulkCancelBtn">Anuluj</button>
            </div>
        </form>
    </div>

    @foreach ($offers->groups as $group)
        <div class="card cardopacity mb-3">
            <div class="card-header">
                {{ $group['type_name'] }}
            </div>
            <div class="card-body p-0">
                <div class="table-responsive glass-table-wrapper">
                    <table class="table table-sm align-middle glass-table mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th style="width:36px">
                                    <input type="checkbox" class="form-check-input select-all" />
                                </th>
                                <th>ID</th>
                                <th>Zwierzę</th>
                                <th>Płeć</th>
                                <th>Cena</th>
                                <th>Data</th>
                                <th>Rezerwujący</th>
                                <th>Data rezerwacji</th>
                                <th>Zaliczka</th>
                                <th>Publiczna</th>
                                <th class="text-end">Opcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($group['rows'] as $row)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input row-check" value="{{ $row['animal_id'] }}">
                                    </td>
                                    <td>{{ $row['animal_id'] }}</td>
                                    <td data-field="name">
                                        <div class="readonly-view">
                                            @if ($row['second_name'])
                                                <span class="text-muted">"{{ $row['second_name'] }}"</span>
                                            @endif
                                            <span class="animal-name-render">{!! $row['animal_name_html'] !!}</span>
                                        </div>
                                        <div class="edit-view d-none">
                                            <input type="text" class="form-control form-control-sm bg-dark text-light border-secondary name-input" value="{{ $row['animal_name_plain'] ?? strip_tags($row['animal_name_html']) }}" data-original="{{ $row['animal_name_plain'] ?? strip_tags($row['animal_name_html']) }}">
                                        </div>
                                    </td>
                                    <td data-field="sex">
                                        <div class="readonly-view">{{ $row['sex'] }}</div>
                                        <div class="edit-view d-none">
                                            <select class="form-select form-select-sm bg-dark text-light border-secondary sex-select" data-original="{{ $row['sex_value'] }}">
                                                @foreach ($offers->sexOptions as $opt)
                                                    <option value="{{ $opt['value'] }}" @selected($opt['value'] == $row['sex_value'])>{{ $opt['label'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </td>
                                    <td data-field="price">
                                        <div class="readonly-view">{{ $row['price'] }}</div>
                                        <div class="edit-view d-none">
                                            <input type="number" step="0.01" min="0" class="form-control form-control-sm bg-dark text-light border-secondary price-input" value="{{ $row['price_value'] }}" data-original="{{ $row['price_value'] }}">
                                        </div>
                                    </td>
                                    <td>{{ $row['date'] }}</td>
                                    <td>{{ $row['reserver'] }}</td>
                                    <td>{{ $row['reservation_date'] }}</td>
                                    <td>{{ $row['deposit'] }}</td>
                                    <td>
                                        <form method="POST" action="{{ $row['public_toggle_url'] }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $row['public_enabled'] ? 'btn-success' : 'btn-outline-secondary' }}">
                                                {{ $row['public_enabled'] ? 'Tak' : 'Nie' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        <a class="btn btn-link text-light" href="{{ $row['profile_url'] }}" title="Profil">
                                            👁
                                        </a>
                                        <button
                                            type="button"
                                            class="btn btn-link text-light edit-offer-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#offerEditModal"
                                            data-payload='@json($row['edit_payload'])'
                                            title="Edycja"
                                        >
                                            ✎
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="text-muted">
                                <td colspan="4"></td>
                                <td class="fw-semibold">Σ {{ $group['sum_price'] }}</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="fw-semibold">Σ {{ $group['sum_deposit'] }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach

    @if ($offers->grandPrice || $offers->grandDeposit)
        <div class="card cardopacity">
            <div class="card-header">Suma całkowita</div>
            <div class="card-body d-flex justify-content-end gap-4">
                <div class="fw-semibold">Cena: {{ number_format($offers->grandPrice, 2, '.', ' ') }} zł</div>
                <div class="fw-semibold">Zaliczka: {{ number_format($offers->grandDeposit, 2, '.', ' ') }} zł</div>
            </div>
        </div>
    @endif

    @include('panel.animals.partials.offer-edit-modal', ['profile' => (object) ['offerForm' => []]])
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const bulkBtn = document.getElementById('bulkPassportBtn');
            const bulkInput = document.getElementById('bulkAnimalIds');
            const form = document.getElementById('bulkPassportForm');
            const defaultAction = form.action;

            const gatherSelected = () => Array.from(document.querySelectorAll('.row-check:checked')).map(cb => cb.value);
            const toggleEditMode = (on) => {
                document.querySelectorAll('.edit-mode-only').forEach(el => el.classList.toggle('d-none', !on));
                document.querySelectorAll('.edit-view').forEach(el => el.classList.toggle('d-none', !on));
                document.querySelectorAll('.readonly-view').forEach(el => el.classList.toggle('d-none', on));
            };

            const resetEdits = () => {
                document.querySelectorAll('.name-input').forEach(inp => { inp.value = inp.dataset.original || ''; inp.classList.remove('is-invalid'); });
                document.querySelectorAll('.sex-select').forEach(sel => { sel.value = sel.dataset.original || ''; sel.classList.remove('is-invalid'); });
                document.querySelectorAll('.price-input').forEach(inp => { inp.value = inp.dataset.original || ''; inp.classList.remove('is-invalid'); });
                document.getElementById('bulkSaveBtn').disabled = true;
            };

            const markDirty = () => {
                const dirty = Array.from(document.querySelectorAll('.edit-view')).some((wrapper) => {
                    const input = wrapper.querySelector('input,select');
                    return input && input.value != (input.dataset.original || '');
                });
                document.getElementById('bulkSaveBtn').disabled = !dirty;
            };

            document.querySelectorAll('.select-all').forEach(selectAll => {
                selectAll.addEventListener('change', () => {
                    const table = selectAll.closest('table');
                    table.querySelectorAll('.row-check').forEach(cb => cb.checked = selectAll.checked);
                });
            });

            const showToast = (message, type = 'info') => {
                const container = document.getElementById('globalToastContainer');
                const wrapper = document.createElement('div');
                const cls = {
                    success: 'bg-success text-white',
                    danger: 'bg-danger text-white',
                    warning: 'bg-warning text-dark',
                    info: 'bg-info text-white',
                }[type] || 'bg-info text-white';
                wrapper.className = `toast align-items-center ${cls}`;
                wrapper.setAttribute('role', 'alert');
                wrapper.setAttribute('aria-live', 'assertive');
                wrapper.setAttribute('aria-atomic', 'true');
                wrapper.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
                container.appendChild(wrapper);
                const t = new bootstrap.Toast(wrapper, { delay: 5000, autohide: true });
                t.show();
            };

            bulkBtn.addEventListener('click', () => {
                const ids = gatherSelected();
                if (ids.length === 0) {
                    showToast('Zaznacz przynajmniej jedno zwierzę.', 'warning');
                    return;
                }
                form.action = defaultAction;
                bulkInput.name = 'animal_ids';
                bulkInput.value = ids.join(',');
                form.submit();
            });

            const labelsBtn = document.getElementById('bulkLabelsBtn');
            labelsBtn.addEventListener('click', () => {
                const ids = gatherSelected();
                if (ids.length === 0) {
                    showToast('Zaznacz przynajmniej jedno zwierzę.', 'warning');
                    return;
                }
                form.action = labelsBtn.dataset.exportUrl;
                bulkInput.name = 'animal_ids';
                bulkInput.value = ids.join(',');
                form.submit();
            });

            const modal = document.getElementById('offerEditModal');
            modal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                if (!button) return;
                const payload = JSON.parse(button.getAttribute('data-payload'));
                const form = modal.querySelector('form');
                form.action = payload.action ?? '#';
                form.querySelector('[name=\"price\"]').value = payload.price ?? '';
                form.querySelector('[name=\"sold_at\"]').value = payload.sold_at ?? '';
                form.querySelector('[name=\"public_profile\"]').checked = !!payload.public_profile_enabled;
                form.querySelector('[name=\"reserver_name\"]').value = payload.reserver_name ?? '';
                form.querySelector('[name=\"deposit_amount\"]').value = payload.deposit_amount ?? '';
                form.querySelector('[name=\"reservation_valid_until\"]').value = payload.reservation_valid_until ?? '';
                form.querySelector('[name=\"notes\"]').value = payload.notes ?? '';

                const delResBtn = form.querySelector('[data-role=\"delete-reservation\"]');
                const delOfferBtn = form.querySelector('[data-role=\"delete-offer\"]');
                const sellBtn = form.querySelector('[data-role=\"sell-offer\"]');

                if (delResBtn) {
                    delResBtn.hidden = !payload.delete_reservation_url;
                    delResBtn.setAttribute('formaction', payload.delete_reservation_url || '#');
                }
                if (delOfferBtn) {
                    delOfferBtn.hidden = !payload.delete_offer_url;
                    delOfferBtn.setAttribute('formaction', payload.delete_offer_url || '#');
                }
                if (sellBtn) {
                    sellBtn.hidden = !payload.sell_url;
                    sellBtn.setAttribute('formaction', payload.sell_url || '#');
                }
            });

            const editToggle = document.getElementById('editModeToggle');
            editToggle?.addEventListener('change', () => {
                const on = editToggle.checked;
                toggleEditMode(on);
                if (!on) resetEdits();
            });

            document.querySelectorAll('.name-input, .price-input, .sex-select').forEach(el => {
                el.addEventListener('input', markDirty);
                el.addEventListener('change', markDirty);
            });

            document.getElementById('bulkCancelBtn')?.addEventListener('click', () => {
                resetEdits();
            });

            document.getElementById('bulkSaveBtn')?.addEventListener('click', async () => {
                const items = [];
                document.querySelectorAll('tbody tr').forEach((tr) => {
                    const idCell = tr.querySelector('td:nth-child(2)');
                    if (!idCell) return;
                    const animalId = parseInt(idCell.textContent.trim(), 10);
                    if (!animalId) return;
                    const nameInput = tr.querySelector('.name-input');
                    const sexSelect = tr.querySelector('.sex-select');
                    const priceInput = tr.querySelector('.price-input');
                    items.push({
                        animal_id: animalId,
                        name: nameInput?.value ?? null,
                        sex: sexSelect?.value ?? null,
                        price: priceInput?.value ?? null,
                    });
                });

                const payload = { items };
                const resp = await fetch("{{ $offers->bulkEditUrl }}", {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                if (resp.ok) {
                    const data = await resp.json();
                    showToast(`Zapisano zmiany: ${data.updated}`, 'success');
                    // apply new originals and readonly text
                    document.querySelectorAll('tbody tr').forEach((tr) => {
                        const nameInput = tr.querySelector('.name-input');
                        const nameRender = tr.querySelector('.animal-name-render');
                        if (nameInput && nameRender) {
                            nameInput.dataset.original = nameInput.value;
                            nameRender.textContent = nameInput.value;
                        }
                        const sexSelect = tr.querySelector('.sex-select');
                        const sexReadonly = tr.querySelector('[data-field="sex"] .readonly-view');
                        if (sexSelect && sexReadonly) {
                            sexSelect.dataset.original = sexSelect.value;
                            const label = sexSelect.options[sexSelect.selectedIndex]?.text || sexSelect.value;
                            sexReadonly.textContent = label;
                        }
                        const priceInput = tr.querySelector('.price-input');
                        const priceReadonly = tr.querySelector('[data-field="price"] .readonly-view');
                        if (priceInput && priceReadonly) {
                            priceInput.dataset.original = priceInput.value;
                            const num = parseFloat(priceInput.value || '0').toFixed(2);
                            priceReadonly.textContent = `${num} zł`;
                        }
                    });
                    document.getElementById('bulkSaveBtn').disabled = true;
                } else {
                    let msg = 'Błąd zapisu';
                    try { const data = await resp.json(); msg = data.message || msg; } catch {}
                    showToast(msg, 'danger');
                }
            });
        });
    </script>
@endpush
