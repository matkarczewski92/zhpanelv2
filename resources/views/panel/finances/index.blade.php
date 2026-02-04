@extends('layouts.panel')

@section('title', 'Finanse')

@section('content')
    @php
        $transactions = $page->transactions;
        $filters = $page->filters;
        $summary = $page->summary;
        $charts = $page->charts;
    @endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Finanse</h1>
            <p class="text-muted mb-0">Historia transakcji, podsumowania i filtry operacyjne.</p>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="glass-card h-100 p-3">
                <div class="text-muted small">Dochody (calosc)</div>
                <div class="fs-5 fw-semibold text-success">{{ $summary['totals']['income_label'] }}</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="glass-card h-100 p-3">
                <div class="text-muted small">Koszty (calosc)</div>
                <div class="fs-5 fw-semibold text-danger">{{ $summary['totals']['cost_label'] }}</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="glass-card h-100 p-3">
                <div class="text-muted small">Bilans (calosc)</div>
                <div class="fs-5 fw-semibold {{ $summary['totals']['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $summary['totals']['balance_label'] }}
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="glass-card h-100 p-3">
                <div class="text-muted small">Wynik po filtrach</div>
                <div class="fw-semibold">{{ $summary['filtered']['count'] }} transakcji</div>
                <div class="small text-muted">Bilans: {{ $summary['filtered']['balance_label'] }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-4">
            <div class="glass-card h-100 p-3" class="text-center">
                <div class="strike mb-2"><span>Podsumowanie</span></div>
                <div class="finance-chart-wrap">
                    <canvas id="financeSummaryChart" data-chart='@json($charts['summary'])'></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="glass-card h-100 p-3">
                <div class="strike mb-2"><span>Dochody wg kategorii</span></div>
                <div class="finance-chart-wrap">
                    <canvas id="financeIncomeChart" data-chart='@json($charts['income'])'></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="glass-card h-100 p-3">
                <div class="strike mb-2"><span>Koszty wg kategorii</span></div>
                <div class="finance-chart-wrap">
                    <canvas id="financeCostChart" data-chart='@json($charts['cost'])'></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="glass-card glass-table-wrapper mb-3">
                <div class="card-header">
                    <div class="strike"><span>Filtry transakcji</span></div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('panel.finances.index') }}" class="row g-2">
                        <div class="col-12 col-md-3">
                            <select class="form-select form-select-sm" name="type">
                                <option value="">Rodzaj</option>
                                <option value="c" @selected(($filters['type'] ?? '') === 'c')>Koszt</option>
                                <option value="i" @selected(($filters['type'] ?? '') === 'i')>Dochod</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <select class="form-select form-select-sm" name="category_id">
                                <option value="">Kategoria</option>
                                @foreach ($page->categories as $category)
                                    <option value="{{ $category['id'] }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category['id'])>
                                        {{ $category['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <input type="text" class="form-control form-control-sm" name="title" placeholder="Tytul" value="{{ $filters['title'] ?? '' }}">
                        </div>
                        <div class="col-12 col-md-3">
                            <input type="text" class="form-control form-control-sm" name="amount_from" placeholder="Kwota od" value="{{ $filters['amount_from'] ?? '' }}">
                        </div>
                        <div class="col-12 col-md-3">
                            <input type="text" class="form-control form-control-sm" name="amount_to" placeholder="Kwota do" value="{{ $filters['amount_to'] ?? '' }}">
                        </div>
                        <div class="col-12 col-md-3">
                            <input type="date" class="form-control form-control-sm" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                        </div>
                        <div class="col-12 col-md-3">
                            <input type="date" class="form-control form-control-sm" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                        </div>
                        <div class="col-12 d-flex gap-2 justify-content-end">
                            <a href="{{ route('panel.finances.index') }}" class="btn btn-outline-light btn-sm">Wyczysc</a>
                            <button type="submit" class="btn btn-primary btn-sm">Filtruj</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="financeTransactionsPanel" class="glass-card glass-table-wrapper">
                <div class="card-header">
                    <div class="strike"><span>Ostatnie transakcje</span></div>
                </div>
                <div class="table-responsive">
                    <table class="table glass-table table-sm align-middle mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th>ID</th>
                                <th>Typ</th>
                                <th>Kategoria</th>
                                <th>Tytul</th>
                                <th>Kwota</th>
                                <th>Data</th>
                                <th>Karma</th>
                                <th>Zwierze</th>
                                <th class="text-end">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $row)
                                <tr>
                                    <td>{{ $row['id'] }}</td>
                                    <td class="{{ $row['type_class'] }}">{{ $row['type_label'] }}</td>
                                    <td>{{ $row['category_name'] }}</td>
                                    <td class="text-break">{{ $row['title'] }}</td>
                                    <td class="{{ $row['type_class'] }} text-nowrap">{{ $row['amount_label'] }}</td>
                                    <td>{{ $row['created_at'] }}</td>
                                    <td>
                                        @if ($row['feed_id'])
                                            <span class="small">#{{ $row['feed_id'] }} {{ $row['feed_name'] }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($row['animal_id'])
                                            <a href="{{ $row['animal_profile_url'] }}" class="link-light text-decoration-none small">
                                                #{{ $row['animal_id'] }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button
                                            type="button"
                                            class="btn btn-link text-light p-0 me-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#financeEditModal{{ $row['id'] }}"
                                            aria-label="Edytuj"
                                        >
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <form method="POST" action="{{ route('panel.finances.transactions.destroy', $row['id']) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link text-danger p-0" aria-label="Usun">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Brak transakcji.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-body border-top border-opacity-10 border-light" data-transactions-pagination>
                    {{ $transactions->links() }}
                </div>

                @foreach ($transactions as $row)
                    <div class="modal fade" id="financeEditModal{{ $row['id'] }}" tabindex="-1" aria-hidden="true" data-finance-edit-modal>
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content bg-dark text-light border-0">
                                <form method="POST" action="{{ route('panel.finances.transactions.update', $row['id']) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header border-0">
                                        <h5 class="modal-title">Edycja transakcji #{{ $row['id'] }}</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                                    </div>
                                    <div class="modal-body d-flex flex-column gap-2">
                                        <label class="small text-muted mb-0">Rodzaj transakcji</label>
                                        <select class="form-select form-select-sm" name="type" required>
                                            <option value="i" @selected($row['type'] === 'i')>Dochod</option>
                                            <option value="c" @selected($row['type'] === 'c')>Koszt</option>
                                        </select>
                                        <label class="small text-muted mb-0">Tytul</label>
                                        <input type="text" class="form-control form-control-sm" name="title" value="{{ $row['title'] }}" required>
                                        <label class="small text-muted mb-0">Kategoria</label>
                                        <select class="form-select form-select-sm" name="finances_category_id" required>
                                            @foreach ($page->categories as $category)
                                                <option value="{{ $category['id'] }}" @selected((int) $row['category_id'] === (int) $category['id'])>
                                                    {{ $category['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label class="small text-muted mb-0">Kwota</label>
                                        <input type="text" class="form-control form-control-sm" name="amount" value="{{ $row['amount'] }}" required>
                                        <label class="small text-muted mb-0">Data transakcji</label>
                                        <input type="date" class="form-control form-control-sm" name="transaction_date" value="{{ $row['created_at'] }}" required>
                                        <label class="small text-muted mb-0">Opcjonalnie: karma</label>
                                        <select class="form-select form-select-sm" name="feed_id">
                                            <option value="">Opcjonalnie: karma</option>
                                            @foreach ($page->feeds as $feed)
                                                <option value="{{ $feed['id'] }}" @selected((int) $row['feed_id'] === (int) $feed['id'])>{{ $feed['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <label class="small text-muted mb-0">Opcjonalnie: zwierze</label>
                                        <select class="form-select form-select-sm" name="animal_id">
                                            <option value="">Opcjonalnie: zwierze</option>
                                            @foreach ($page->animals as $animal)
                                                <option value="{{ $animal['id'] }}" @selected((int) $row['animal_id'] === (int) $animal['id'])>
                                                    #{{ $animal['id'] }} {{ $animal['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="modal-footer border-0">
                                        <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">Anuluj</button>
                                        <button type="submit" class="btn btn-primary btn-sm">Zapisz</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="glass-card mb-3">
                <div class="card-header">
                    <div class="strike"><span>Dodaj transakcje</span></div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('panel.finances.transactions.store') }}" class="d-flex flex-column gap-2">
                        @csrf
                        <select class="form-select form-select-sm @error('type', 'financeCreate') is-invalid @enderror" name="type" required>
                            <option value="">Rodzaj transakcji</option>
                            <option value="i" @selected(old('type') === 'i')>Dochod</option>
                            <option value="c" @selected(old('type') === 'c')>Koszt</option>
                        </select>
                        <input type="text" class="form-control form-control-sm @error('title', 'financeCreate') is-invalid @enderror" name="title" placeholder="Tytul" value="{{ old('title') }}" required>
                        <select class="form-select form-select-sm @error('finances_category_id', 'financeCreate') is-invalid @enderror" name="finances_category_id" required>
                            <option value="">Kategoria</option>
                            @foreach ($page->categories as $category)
                                <option value="{{ $category['id'] }}" @selected((string) old('finances_category_id') === (string) $category['id'])>{{ $category['name'] }}</option>
                            @endforeach
                        </select>
                        <input type="text" class="form-control form-control-sm @error('amount', 'financeCreate') is-invalid @enderror" name="amount" placeholder="Kwota" value="{{ old('amount') }}" required>
                        <input type="date" class="form-control form-control-sm @error('transaction_date', 'financeCreate') is-invalid @enderror" name="transaction_date" value="{{ old('transaction_date', now()->format('Y-m-d')) }}" required>
                        <select class="form-select form-select-sm @error('feed_id', 'financeCreate') is-invalid @enderror" name="feed_id">
                            <option value="">Opcjonalnie: karma</option>
                            @foreach ($page->feeds as $feed)
                                <option value="{{ $feed['id'] }}" @selected((string) old('feed_id') === (string) $feed['id'])>{{ $feed['name'] }}</option>
                            @endforeach
                        </select>
                        <select class="form-select form-select-sm @error('animal_id', 'financeCreate') is-invalid @enderror" name="animal_id">
                            <option value="">Opcjonalnie: zwierze</option>
                            @foreach ($page->animals as $animal)
                                <option value="{{ $animal['id'] }}" @selected((string) old('animal_id') === (string) $animal['id'])>#{{ $animal['id'] }} {{ $animal['name'] }}</option>
                            @endforeach
                        </select>

                        @if ($errors->getBag('financeCreate')->any())
                            <div class="small text-danger">
                                @foreach ($errors->getBag('financeCreate')->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary btn-sm">Dodaj</button>
                    </form>
                </div>
            </div>

            <div class="glass-card p-3">
                <div class="strike mb-2"><span>Kategorie finansowe</span></div>
                <p class="text-muted small mb-2">Zarzadzanie kategoriami zostalo przeniesione do Ustawien portalu.</p>
                <a href="{{ route('admin.settings.index', ['tab' => 'finance-categories']) }}" class="btn btn-outline-light btn-sm">
                    Otworz ustawienia kategorii
                </a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <style>
        .finance-chart-wrap {
            position: relative;
            height: 220px;
            max-height: 220px;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .finance-chart-wrap canvas {
            margin: 0 auto;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chartRegistry = window.__financeCharts || {};
            window.__financeCharts = chartRegistry;

            const renderChart = (id, type) => {
                const canvas = document.getElementById(id);
                if (!canvas) return;

                const payload = JSON.parse(canvas.getAttribute('data-chart') || '{}');
                const datasets = (payload.datasets || []).map((dataset) => ({
                    ...dataset,
                    borderColor: '#0f172a',
                    borderWidth: 1,
                }));

                if (chartRegistry[id]) {
                    chartRegistry[id].destroy();
                }

                chartRegistry[id] = new Chart(canvas.getContext('2d'), {
                    type,
                    data: {
                        labels: payload.labels || [],
                        datasets,
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                maxHeight: 48,
                                labels: { color: '#e5e7eb', boxWidth: 12 },
                            },
                        },
                    },
                });
            };

            renderChart('financeSummaryChart', 'pie');
            renderChart('financeIncomeChart', 'doughnut');
            renderChart('financeCostChart', 'doughnut');

            const initFinancePagination = () => {
                const panel = document.getElementById('financeTransactionsPanel');
                if (!panel) return;

                panel.querySelectorAll('.pagination a').forEach((link) => {
                    link.addEventListener('click', async (event) => {
                        event.preventDefault();
                        const href = link.getAttribute('href');
                        if (!href) return;

                        const response = await fetch(href, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        if (!response.ok) return;

                        const html = await response.text();
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const nextPanel = doc.getElementById('financeTransactionsPanel');
                        if (!nextPanel) return;

                        panel.replaceWith(nextPanel);
                        mountFinanceEditModals();
                        initFinancePagination();
                    });
                });
            };

            const mountFinanceEditModals = () => {
                document.querySelectorAll('body > .modal[data-finance-edit-modal]').forEach((modal) => modal.remove());

                document
                    .querySelectorAll('#financeTransactionsPanel .modal[data-finance-edit-modal]')
                    .forEach((modal) => document.body.appendChild(modal));
            };

            mountFinanceEditModals();
            initFinancePagination();
        });
    </script>
@endpush
