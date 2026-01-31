@extends('layouts.panel')

@section('title', 'Karma')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Karma</h1>
            <p class="text-muted mb-0">Zarządzaj typami karmy wykorzystywanej w panelu.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="glass-card glass-table-wrapper h-100">
                <div class="card-header">
                    <div class="strike"><span>Lista karm</span></div>
                </div>
                <div class="table-responsive">
                    <table class="table glass-table table-hover table-sm align-middle mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th style="width: 40px">ID</th>
                                <th>Nazwa</th>
                                <th>Interwał</th>
                                <th>Ilość</th>
                                <th>Cena</th>
                                <th class="text-center" style="width: 80px">Szczegóły</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($feeds as $feed)
                                <tr>
                                    <td>{{ $feed['id'] }}</td>
                                    <td>{{ $feed['name'] }}</td>
                                    <td>{{ $feed['feeding_interval'] }}</td>
                                    <td>{{ $feed['amount'] ?? '-' }}</td>
                                    <td>
                                        {{ $feed['last_price'] !== null ? number_format($feed['last_price'], 2, ',', ' ') . ' zł' : '-' }}
                                    </td>
                                    <td class="text-center">
                                        <button
                                            class="btn btn-outline-light btn-sm"
                                            type="button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#feedModal{{ $feed['id'] }}"
                                            aria-label="Pokaż szczegóły"
                                        >
                                            <i class="bi bi-info-circle"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Brak danych.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="glass-card h-100">
                <div class="card-header">
                    <div class="strike"><span>Informacja</span></div>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0 small">
                        Dodawanie i edycja pozycji karmy jest dostępne w panelu administracyjnym. Ten widok prezentuje
                        referencyjną listę dostępnych opcji używanych w panelu operacyjnym.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-12 col-xl-8">
            <div class="glass-card h-100">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="strike flex-grow-1"><span>Wykres zużycia</span></div>
                    <form method="GET" action="{{ route('panel.feeds.index') }}" class="d-flex align-items-center gap-2">
                        <label class="text-muted small mb-0" for="yearSelect">Rok</label>
                        <select id="yearSelect" name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach ($availableYears as $yearOption)
                                <option value="{{ $yearOption }}" @selected($selectedYear === $yearOption)>{{ $yearOption }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="card-body">
                    <canvas
                        id="feedConsumptionChart"
                        height="320"
                        data-chart='@json($chart)'
                    ></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4"></div>
    </div>

    @php
        $planningRows = $planning['rows'] ?? [];
        $planningLeadTime = $planning['lead_time_days'] ?? 0;
        $planningTotalLabel = $planning['total_cost_label'] ?? '—';
    @endphp
    <div class="row g-4 mt-2">
        <div class="col-12">
            <div
                class="glass-card glass-table-wrapper"
                id="feedPlanning"
                data-url="{{ route('panel.feeds.planning.recalculate', [], false) }}"
            >
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="strike"><span>Planowanie zapotrzebowania</span></div>
                    <div class="d-flex align-items-center gap-3 text-muted small">
                        <span>Dzisiejsza data: <span class="fw-semibold">{{ $planning['today'] ?? now()->format('Y-m-d') }}</span></span>
                        <span>Czas dostawy: <span class="fw-semibold">{{ $planningLeadTime }}</span> dni</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table glass-table table-sm align-middle mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th>Nazwa karmy</th>
                                <th style="width: 80px;">DK</th>
                                <th style="width: 120px;">DZ</th>
                                <th style="width: 140px;">Zamówienie</th>
                                <th style="width: 90px;">Nowa DK</th>
                                <th style="width: 130px;">Nowa DZ</th>
                                <th style="width: 120px;" class="text-end">Kwota</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($planningRows as $feedId => $row)
                                <tr data-feed-id="{{ $feedId }}">
                                    <td class="fw-semibold text-break">{{ $row['name'] }}</td>
                                    <td class="js-dk fw-semibold">{{ $row['dk_label'] }}</td>
                                    <td class="js-dz text-nowrap text-muted small">{{ $row['dz_label'] }}</td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input
                                                type="number"
                                                min="0"
                                                step="1"
                                                class="form-control form-control-sm js-order-qty"
                                                value="{{ $row['order_qty'] ?? 0 }}"
                                                aria-label="Zamówienie dla {{ $row['name'] }}"
                                            >
                                            <span class="input-group-text">szt</span>
                                        </div>
                                    </td>
                                    <td class="js-new-dk fw-semibold">{{ $row['new_dk_label'] }}</td>
                                    <td class="js-new-dz text-nowrap text-muted small">{{ $row['new_dz_label'] }}</td>
                                    <td class="js-row-cost text-end text-nowrap">{{ $row['row_cost_label'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Brak danych.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-body border-top border-opacity-10 border-light d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="text-danger small" data-planning-error></div>
                    <div class="d-flex align-items-center gap-4 ms-auto">
                        <div class="text-end">
                            <div class="text-muted small">Suma</div>
                            <div class="fs-6 fw-semibold" data-planning-total>{{ $planningTotalLabel }}</div>
                        </div>
                        <button
                            type="button"
                            class="btn btn-outline-light btn-sm"
                            data-action="planning-recalculate"
                        >
                            Przelicz
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach ($feeds as $feed)
        <div
            class="modal fade"
            id="feedModal{{ $feed['id'] }}"
            tabindex="-1"
            aria-labelledby="feedModalLabel{{ $feed['id'] }}"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-half">
                <div class="modal-content bg-dark text-light border-0">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="feedModalLabel{{ $feed['id'] }}">
                            Zwierzęta karmione: {{ $feed['name'] }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12 col-lg-6">
                                <h6 class="small text-muted mb-2">Zwierzęta (kategorie 1, 2, 4)</h6>
                                @if (!empty($feed['animals']))
                                    <ul class="list-unstyled mb-0 small">
                                        @foreach ($feed['animals'] as $animal)
                                            <li class="mb-1">
                                                <a href="{{ route('panel.animals.show', $animal['id']) }}" class="link-reset">
                                                    #{{ $animal['id'] }} — {{ $animal['name'] }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted mb-0 small">Brak zwierząt w kategoriach 1, 2 lub 4 dla tej karmy.</p>
                                @endif
                            </div>
                            <div class="col-12 col-lg-6">
                                <h6 class="small text-muted mb-2">Zakupy (najnowsze)</h6>
                                @if (!empty($feed['purchases']))
                                    <ul class="list-unstyled mb-0 small">
                                        @foreach ($feed['purchases'] as $purchase)
                                            <li class="mb-2">
                                                <div class="fw-semibold text-break">{{ $purchase['title'] }}</div>
                                                <div class="d-flex justify-content-between gap-2 text-muted">
                                                    <span>{{ $purchase['quantity'] }} szt · {{ $purchase['amount'] }}</span>
                                                    <span class="text-nowrap">{{ $purchase['date'] }}</span>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted mb-0 small">Brak zakupów dla tej karmy.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zamknij</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chartEl = document.getElementById('feedConsumptionChart');
            if (!chartEl) return;

            const payload = JSON.parse(chartEl.getAttribute('data-chart') || '{}');
            const labels = payload.labels || [];
            const datasets = (payload.datasets || []).map((d) => ({
                ...d,
                fill: d.fill ?? false,
                borderWidth: 2,
            }));

            const ctx = chartEl.getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: { labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: '#e5e7eb', boxWidth: 12 },
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        },
                    },
                    scales: {
                        x: {
                            ticks: { color: '#e5e7eb' },
                            grid: { color: 'rgba(255,255,255,0.06)' },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { color: '#e5e7eb' },
                            grid: { color: 'rgba(255,255,255,0.06)' },
                        },
                    },
                },
            });
        });
    </script>
@endpush
