<div class="card cardopacity mb-3" id="weights">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <span>Wagi</span>
        <span class="text-muted small">{{ $profile->weightsCount }} wpisów</span>
    </div>

    <div class="card-body">
        <canvas
            id="weightsChart"
            height="220"
            class="mb-3"
            data-series='@json($profile->weightsSeries)'
            data-feed-index='@json($profile->weightChart->feedIndexValues ?? [])'
            data-feed-names='@json($profile->weightChart->feedNameByIndex ?? [])'
            data-has-feed="{{ !empty($profile->weightChart->feedIndexMeta['feed_id_to_index'] ?? []) ? '1' : '0' }}"
        ></canvas>

        <form method="POST" action="{{ route('panel.animals.weights.store', $profile->animal['id']) }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-6 col-md-4">
                <label class="form-label" for="weight_value">Waga</label>
                <input id="weight_value" name="value" type="number" step="0.01" class="form-control" required />
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label" for="weight_date">Data</label>
                <x-form.date-input id="weight_date" name="occurred_at" :default-today="true" />
            </div>
            <div class="col-12 col-md-4">
                <button class="btn btn-primary w-100" type="submit">Dodaj</button>
            </div>
        </form>
    </div>

    <div class="list-group list-group-flush feedings-list" id="weightsList" data-items='@json($profile->weights)'></div>

    <div class="card-body d-flex justify-content-between align-items-center">
        <button class="btn btn-outline-light btn-sm" type="button" id="weightsPrev">Poprzednie</button>
        <div class="text-muted small" id="weightsPageInfo"></div>
        <button class="btn btn-outline-light btn-sm" type="button" id="weightsNext">Następne</button>
    </div>
</div>

{{-- Edit modal --}}
<div class="modal fade" id="weightEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content photobg">
            <div class="modal-header">
                <h5 class="modal-title">Edytuj wagę</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <form method="POST" id="weightEditForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="editWeightDate">Data</label>
                        <x-form.date-input id="editWeightDate" name="occurred_at" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="editWeightValue">Waga</label>
                        <input id="editWeightValue" type="number" step="0.01" min="0" name="value" class="form-control" required />
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const perPage = 5;

            const buildList = (container, items, page, modalId) => {
                const start = (page - 1) * perPage;
                const pageItems = items.slice(start, start + perPage);
                container.innerHTML = pageItems
                    .map(
                        (item) => {
                            const valueLabel = item.value !== undefined ? `<span class="fw-semibold">${item.value} g</span>` : '';
                            return `
                        <div class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                            <div class="d-flex flex-column flex-md-row gap-2">
                                <span class="text-muted">${item.date_label}</span>
                                ${valueLabel}
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-link text-light p-0"
                                    data-edit="${modalId}"
                                    data-update-url="${item.edit_payload.update_url}"
                                    data-date="${item.edit_payload.date_iso}"
                                    data-value="${item.edit_payload.value ?? ''}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#${modalId}"
                                    aria-label="Edytuj"
                                >
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/></svg>
                                </button>
                                <form method="POST" action="${item.delete_url}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0" aria-label="Usuń">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>`;
                        }
                    )
                    .join('');
                return pageItems.length;
            };

            const paginate = (containerId, itemsAttr, prevId, nextId, infoId, modalId) => {
                const container = document.getElementById(containerId);
                if (!container) return;
                const items = JSON.parse(container.getAttribute(itemsAttr) || '[]');
                let page = 1;
                const info = document.getElementById(infoId);
                const prev = document.getElementById(prevId);
                const next = document.getElementById(nextId);
                const totalPages = Math.max(1, Math.ceil(items.length / perPage));

                const render = () => {
                    buildList(container, items, page, modalId);
                    if (info) info.textContent = `Strona ${page} / ${totalPages}`;
                    if (prev) prev.disabled = page === 1;
                    if (next) next.disabled = page === totalPages;
                };

                prev?.addEventListener('click', () => {
                    if (page > 1) {
                        page--;
                        render();
                    }
                });
                next?.addEventListener('click', () => {
                    if (page < totalPages) {
                        page++;
                        render();
                    }
                });

                render();
            };

            paginate('weightsList', 'data-items', 'weightsPrev', 'weightsNext', 'weightsPageInfo', 'weightEditModal');
            paginate('moltsList', 'data-items', 'moltsPrev', 'moltsNext', 'moltsPageInfo', 'moltEditModal');

            const hookModal = (modalId, dateInputId, valueInputId) => {
                const modal = document.getElementById(modalId);
                if (!modal) return;
                const form = modal.querySelector('form');
                const dateInput = modal.querySelector(`#${dateInputId}`);
                const valueInput = valueInputId ? modal.querySelector(`#${valueInputId}`) : null;
                modal.addEventListener('show.bs.modal', (event) => {
                    const trigger = event.relatedTarget;
                    if (!trigger) return;
                    form?.setAttribute('action', trigger.getAttribute('data-update-url') || '');
                    if (dateInput) dateInput.value = trigger.getAttribute('data-date') || '';
                    if (valueInput) valueInput.value = trigger.getAttribute('data-value') || '';
                });
            };

            hookModal('weightEditModal', 'editWeightDate', 'editWeightValue');
            hookModal('moltEditModal', 'editMoltDate', null);

            const chartCanvas = document.getElementById('weightsChart');
            if (chartCanvas) {
                const series = JSON.parse(chartCanvas.getAttribute('data-series') || '[]');
                const feedIndexValues = JSON.parse(chartCanvas.getAttribute('data-feed-index') || '[]');
                const feedNameByIndex = JSON.parse(chartCanvas.getAttribute('data-feed-names') || '[]');
                const hasFeed = chartCanvas.getAttribute('data-has-feed') === '1';
                const ctx = chartCanvas.getContext('2d');
                if (series.length) {
                    const labels = series.map((p) => p.date);
                    const data = series.map((p) => p.value);
                    const findFeedByIndex = (index) => feedNameByIndex[index] || 'brak danych';

                    const datasets = [
                        {
                            label: 'Waga',
                            data,
                            yAxisID: 'yWeight',
                            fill: true,
                            borderColor: '#6ea8fe',
                            backgroundColor: 'rgba(110, 168, 254, 0.25)',
                            tension: 0.35,
                            pointRadius: 3,
                            pointBackgroundColor: '#fff',
                        },
                    ];

                    if (hasFeed) {
                        datasets.push({
                            label: 'Karma',
                            data: feedIndexValues,
                            yAxisID: 'yFeed',
                            fill: false,
                            borderColor: 'rgba(245, 158, 11, 0.9)',
                            backgroundColor: 'rgba(245, 158, 11, 0.15)',
                            borderDash: [6, 4],
                            pointRadius: 0,
                            tension: 0,
                            stepped: true,
                            spanGaps: false,
                        });
                    }

                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels,
                            datasets,
                        },
                        options: {
                            plugins: {
                                legend: { labels: { color: '#fff' } },
                                tooltip: {
                                    callbacks: {
                                        label(context) {
                                            const index = context.dataIndex ?? 0;
                                            const feed = findFeedByIndex(index);
                                            if (context.dataset.label === 'Waga') {
                                                const label = `Waga: ${context.formattedValue} g`;
                                                return [label, `Karma w tym okresie: ${feed}`];
                                            }
                                            if (context.dataset.label === 'Karma') {
                                                return `Karma: ${feed}`;
                                            }
                                            return context.formattedValue;
                                        },
                                    },
                                },
                            },
                            scales: {
                                x: { ticks: { color: '#ccc' }, grid: { color: 'rgba(255,255,255,0.08)' } },
                                yWeight: { ticks: { color: '#ccc' }, grid: { color: 'rgba(255,255,255,0.08)' } },
                                yFeed: {
                                    position: 'right',
                                    ticks: { display: false },
                                    grid: { drawOnChartArea: false, color: 'rgba(255,255,255,0.08)' },
                                    border: { color: 'rgba(255,255,255,0.08)' },
                                },
                            },
                        },
                    });
                }
            }
        });
    </script>
@endpush
