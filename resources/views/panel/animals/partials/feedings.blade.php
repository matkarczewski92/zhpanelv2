@php
    $currentYear = (int) now()->format('Y');
    $currentMonth = (int) now()->format('n');
@endphp

<div class="card cardopacity mb-3" id="feedings">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <span>Karmienia</span>
        <span class="text-muted small">{{ $profile->feedingCount }} wpisów</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('panel.animals.feedings.store', $profile->animal['id']) }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-12 col-md-5">
                <label class="form-label" for="feed_id">Karma</label>
                <select id="feed_id" name="feed_id" class="form-select" required>
                    @foreach ($profile->feeds as $feed)
                        <option value="{{ $feed['id'] }}" @selected((int) $feed['id'] === (int) $profile->feedingDefaults['feed_id'])>
                            {{ $feed['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="amount">Ilość</label>
                <input id="amount" name="amount" type="number" class="form-control" value="{{ $profile->feedingDefaults['quantity'] }}" required />
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="feeding_date">Data</label>
                <x-form.date-input id="feeding_date" name="occurred_at" :value="$profile->feedingDefaults['date_iso']" />
            </div>
            <div class="col-12 col-md-1">
                <button class="btn btn-primary w-100" type="submit">Dodaj</button>
            </div>
        </form>
    </div>

    <div class="accordion accordion-flush feedings-accordion" id="feedingsAccordion">
        @forelse ($profile->feedingTree as $yearGroup)
            @php $yearOpen = false; @endphp
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading-year-{{ $yearGroup['year'] }}">
                    <button
                        class="accordion-button collapsed"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapse-year-{{ $yearGroup['year'] }}"
                        aria-expanded="{{ $yearOpen ? 'true' : 'false' }}"
                    >
                        {{ $yearGroup['year'] }}
                    </button>
                </h2>
                <div
                    id="collapse-year-{{ $yearGroup['year'] }}"
                    class="accordion-collapse collapse"
                    data-bs-parent="#feedingsAccordion"
                >
                    <div class="accordion-body p-2">
                        <div class="accordion accordion-flush feedings-accordion" id="feedings-year-{{ $yearGroup['year'] }}">
                            @foreach ($yearGroup['months'] as $monthGroup)
                                @php $monthOpen = false; @endphp
                                <div class="accordion-item border-0">
                                    <h2 class="accordion-header" id="heading-month-{{ $yearGroup['year'] }}-{{ $monthGroup['month'] }}">
                                        <button
                                            class="accordion-button py-2 px-3 collapsed"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#collapse-month-{{ $yearGroup['year'] }}-{{ $monthGroup['month'] }}"
                                            aria-expanded="{{ $monthOpen ? 'true' : 'false' }}"
                                        >
                                            <span class="form-label mb-0 fs-6">
                                                {{ $monthGroup['month_label_full'] ?? ('Miesiąc ' . $monthGroup['month_label']) }}
                                            </span>
                                        </button>
                                    </h2>
                                    <div
                                        id="collapse-month-{{ $yearGroup['year'] }}-{{ $monthGroup['month'] }}"
                                        class="accordion-collapse collapse"
                                        data-bs-parent="#feedings-year-{{ $yearGroup['year'] }}"
                                    >
                                        <div class="accordion-body py-2 px-3">
                                            <ul class="list-group list-group-flush">
                                                @foreach ($monthGroup['entries'] as $entry)
                                                    <li class="list-group-item d-flex align-items-center justify-content-between px-0 py-2">
                                                        <div class="d-flex flex-column flex-md-row gap-2">
                                                            <span class="text-muted">{{ $entry['date_display'] }}</span>
                                                            <span class="fw-semibold">{{ $entry['feed_name'] }}</span>
                                                            <span class="text-muted">Ilość: {{ $entry['quantity'] }}</span>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-link text-light p-0"
                                                                data-feeding-edit="true"
                                                                data-update-url="{{ $entry['edit_payload']['update_url'] }}"
                                                                data-date="{{ $entry['edit_payload']['date_iso'] }}"
                                                                data-feed="{{ $entry['edit_payload']['feed_id'] }}"
                                                                data-quantity="{{ $entry['edit_payload']['quantity'] }}"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#feedingEditModal"
                                                                aria-label="Edytuj"
                                                            >
                                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/></svg>
                                                            </button>
                                                            <form method="POST" action="{{ $entry['delete_url'] }}" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-link text-danger p-0" aria-label="Usuń">
                                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card-body pt-0">
                <div class="text-muted small">Brak danych.</div>
            </div>
        @endforelse
    </div>
</div>

{{-- Edit modal --}}
<div class="modal fade" id="feedingEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content photobg">
            <div class="modal-header">
                <h5 class="modal-title">Edytuj karmienie</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <form method="POST" id="feedingEditForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="editFeedingDate">Data</label>
                        <x-form.date-input id="editFeedingDate" name="occurred_at" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="editFeedingFeed">Karma</label>
                        <select id="editFeedingFeed" name="feed_id" class="form-select" required>
                            @foreach ($profile->feeds as $feed)
                                <option value="{{ $feed['id'] }}">{{ $feed['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="editFeedingAmount">Ilość</label>
                        <input id="editFeedingAmount" type="number" min="1" name="amount" class="form-control" required />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editModal = document.getElementById('feedingEditModal');
            const editForm = document.getElementById('feedingEditForm');
            const dateInput = document.getElementById('editFeedingDate');
            const feedSelect = document.getElementById('editFeedingFeed');
            const amountInput = document.getElementById('editFeedingAmount');

            editModal.addEventListener('show.bs.modal', (event) => {
                const trigger = event.relatedTarget;
                if (!trigger) {
                    return;
                }

                const updateUrl = trigger.getAttribute('data-update-url');
                const dateIso = trigger.getAttribute('data-date');
                const feedId = trigger.getAttribute('data-feed');
                const quantity = trigger.getAttribute('data-quantity');

                if (editForm && updateUrl) {
                    editForm.setAttribute('action', updateUrl);
                }
                if (dateInput && dateIso) {
                    dateInput.value = dateIso;
                }
                if (feedSelect && feedId) {
                    feedSelect.value = feedId;
                }
                if (amountInput && quantity) {
                    amountInput.value = quantity;
                }
            });

            // ensure accordions can toggle both ways
            document.querySelectorAll('#feedingsAccordion .accordion-button').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const targetSelector = btn.getAttribute('data-bs-target');
                    if (!targetSelector) return;
                    const target = document.querySelector(targetSelector);
                    if (!target) return;
                    const instance = bootstrap.Collapse.getOrCreateInstance(target, { toggle: false });
                    if (target.classList.contains('show')) {
                        instance.hide();
                    } else {
                        instance.show();
                    }
                });
            });

            document.querySelectorAll('[id^="feedings-"] .accordion-button').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const targetSelector = btn.getAttribute('data-bs-target');
                    if (!targetSelector) return;
                    const target = document.querySelector(targetSelector);
                    if (!target) return;
                    const instance = bootstrap.Collapse.getOrCreateInstance(target, { toggle: false });
                    if (target.classList.contains('show')) {
                        instance.hide();
                    } else {
                        instance.show();
                    }
                });
            });
        });
    </script>
@endpush
