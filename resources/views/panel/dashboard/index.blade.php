@extends('layouts.panel')

@section('title', 'Dashboard')

@section('content')
    @php
        $management = $page->management;
        $litterStatuses = $page->litterStatuses;
        $financeSummary = $page->financeSummary;
        $feedingTables = $page->feedingTables;
    @endphp

    <section class="dashboard-hero glass-card p-3 mb-3">
        <div class="strike mb-3"><span>Zarządzanie hodowlą</span></div>
        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="dashboard-kpi h-100">
                    <div class="dashboard-kpi__value">
                        {{ $management['eggs_in_incubation'] }} / {{ $management['eggs_in_incubators_total'] }}
                    </div>
                    <div class="dashboard-kpi__label">Ilość jaj w inkubacji / ogółem w inkubatorach</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="dashboard-kpi h-100">
                    <div class="dashboard-kpi__value">{{ $management['for_sale_count'] }}</div>
                    <div class="dashboard-kpi__label">Ilość maluchów na sprzedaż</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="dashboard-kpi h-100">
                    <div class="dashboard-kpi__value">{{ $management['litter_count'] }}</div>
                    <div class="dashboard-kpi__label">Aktualna liczba miotów</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="dashboard-kpi h-100">
                    <div class="dashboard-kpi__value">{{ number_format((float) $management['planned_income'], 2, ',', ' ') }} zł</div>
                    <div class="dashboard-kpi__label">Planowany przychód</div>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="glass-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="strike mb-0"><span>Status miotów</span></div>
                </div>
                <div class="row g-2">
                    @foreach ($litterStatuses as $status)
                        <div class="col-12 col-lg-6">
                            <div class="dashboard-status-box h-100">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="{{ $status['text_class'] }} fw-semibold">{{ $status['name'] }}</span>
                                    <span class="badge {{ $status['badge_class'] }}">{{ $status['count'] }}</span>
                                </div>
                                @if (!empty($status['items']))
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($status['items'] as $item)
                                            <a href="{{ route('panel.litters.show', $item['id']) }}" class="litter-chip">
                                                {{ $item['code'] }} @if ($item['date']) ({{ $item['date'] }}) @endif
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="small text-secondary">Brak aktywnych miotów w tym statusie.</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <section class="glass-card p-3 mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="strike mb-0"><span>Podsumowanie finansowe</span></div>
            <form method="GET" action="{{ route('panel.home') }}" class="d-flex align-items-center gap-2">
                <label for="dashboardYear" class="small text-muted">Rok:</label>
                <select id="dashboardYear" name="year" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    @foreach ($page->financeYears as $year)
                        <option value="{{ $year }}" @selected($year === $page->financeSelectedYear)>{{ $year }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="row text-center mb-3 g-2">
            <div class="col-12 col-md-4">
                <div class="dashboard-finance-total">
                    <div class="small text-muted">Przychód ({{ $financeSummary['year'] }})</div>
                    <div class="fs-5 fw-semibold text-success">{{ $financeSummary['year_totals']['income_label'] }}</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="dashboard-finance-total">
                    <div class="small text-muted">Koszty ({{ $financeSummary['year'] }})</div>
                    <div class="fs-5 fw-semibold text-danger">{{ $financeSummary['year_totals']['costs_label'] }}</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="dashboard-finance-total">
                    <div class="small text-muted">Dochód ({{ $financeSummary['year'] }})</div>
                    <div class="fs-5 fw-semibold">{{ $financeSummary['year_totals']['profit_label'] }}</div>
                </div>
            </div>
        </div>

        <div class="glass-table-wrapper table-responsive mb-3">
            <table class="table glass-table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th></th>
                        <th class="text-center" colspan="3">Rok {{ $financeSummary['year'] }}</th>
                        <th class="text-center" colspan="2">Od początku</th>
                    </tr>
                    <tr class="small text-muted">
                        <th>Kategoria</th>
                        <th class="text-end">Przychód</th>
                        <th class="text-end">Koszty</th>
                        <th class="text-end">Dochód</th>
                        <th class="text-end">Przychód (łącznie)</th>
                        <th class="text-end">Koszty (łącznie)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($financeSummary['category_totals'] as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="text-end text-success">{{ $row['income_label'] }}</td>
                            <td class="text-end text-danger">{{ $row['cost_label'] }}</td>
                            <td class="text-end">{{ $row['profit_label'] }}</td>
                            <td class="text-end text-success">{{ $row['overall_income_label'] }}</td>
                            <td class="text-end text-danger">{{ $row['overall_cost_label'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary">Brak danych finansowych.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="row text-center g-2">
            <div class="col-12 col-md-4">
                <div class="small text-muted">Przychód (łącznie)</div>
                <div class="fw-semibold text-success">{{ $financeSummary['overall_totals']['income_label'] }}</div>
            </div>
            <div class="col-12 col-md-4">
                <div class="small text-muted">Koszty (łącznie)</div>
                <div class="fw-semibold text-danger">{{ $financeSummary['overall_totals']['costs_label'] }}</div>
            </div>
            <div class="col-12 col-md-4">
                <div class="small text-muted">Dochód (łącznie)</div>
                <div class="fw-semibold">{{ $financeSummary['overall_totals']['profit_label'] }}</div>
            </div>
        </div>
    </section>

    <div class="row g-3">
        @foreach (['breeding', 'litters'] as $sectionKey)
            @php
                $table = $feedingTables[$sectionKey];
                $printId = 'feedingPrint-' . $sectionKey;
            @endphp
            <div class="col-12 col-xl-6">
                <section class="glass-card p-3 h-100" id="{{ $printId }}">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                        <div class="strike flex-grow-1 mb-0"><span>{{ $table['title'] }}</span></div>
                        <button
                            type="button"
                            class="btn btn-link text-light dashboard-print-btn"
                            onclick="printFeedingSection('{{ $printId }}', '{{ $table['title'] }}')"
                            aria-label="Drukuj {{ $table['title'] }}"
                            title="Drukuj"
                        >
                            <i class="bi bi-printer"></i>
                        </button>
                    </div>
                    <div data-print-body>
                    <div class="glass-table-wrapper table-responsive mb-3">
                        <table class="table glass-table table-sm align-middle mb-0">
                            <thead>
                                <tr class="small text-muted">
                                    <th>Nazwa zwierzęcia</th>
                                    <th>Rodzaj karmy</th>
                                    <th>Data karmienia</th>
                                    <th class="text-center">Dni do karmienia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($table['rows'] as $row)
                                    <tr class="{{ $row['days_to_feed'] < 0 ? 'text-danger' : ($row['days_to_feed'] === 0 ? 'text-success' : '') }}">
                                        <td>
                                            {{ $row['id'] }}.
                                            <a href="{{ $row['profile_url'] }}" class="link-light text-decoration-none">{{ $row['name'] }}</a>
                                        </td>
                                        <td>{{ $row['feed_name'] }}</td>
                                        <td>{{ $row['feed_date'] ?: '—' }}</td>
                                        <td class="text-center">{{ $row['days_to_feed'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-secondary">Brak zwierząt do karmienia.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if (!empty($table['summary']) || !empty($table['summary_past']))
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <div class="strike mb-2"><span>Minione i bieżące</span></div>
                                <ul class="list-group list-group-flush">
                                    @forelse ($table['summary_past'] as $feed => $count)
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>{{ $feed }}</span>
                                            <span>{{ $count }} szt.</span>
                                        </li>
                                    @empty
                                        <li class="list-group-item text-secondary">Brak pozycji.</li>
                                    @endforelse
                                </ul>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="strike mb-2"><span>Wszystkie</span></div>
                                <ul class="list-group list-group-flush">
                                    @forelse ($table['summary'] as $feed => $count)
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>{{ $feed }}</span>
                                            <span>{{ $count }} szt.</span>
                                        </li>
                                    @empty
                                        <li class="list-group-item text-secondary">Brak pozycji.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    @endif
                    </div>
                </section>
            </div>
        @endforeach
    </div>
@endsection

@push('scripts')
    <script>
        function printFeedingSection(sectionId, title) {
            const section = document.getElementById(sectionId);
            if (!section) {
                return;
            }

            const body = section.querySelector('[data-print-body]');
            if (!body) {
                return;
            }

            const printContent = body.cloneNode(true);
            const summaryRow = printContent.querySelector('.row.g-2');
            if (summaryRow) {
                const summaryTitle = document.createElement('h2');
                summaryTitle.className = 'print-summary-title';
                summaryTitle.textContent = 'Podsumowanie';
                summaryRow.parentNode.insertBefore(summaryTitle, summaryRow);
            }

            const printWindow = window.open('', '_blank', 'width=1100,height=900');
            if (!printWindow) {
                return;
            }

            printWindow.document.write(`
                <html>
                    <head>
                        <title>${title}</title>
                        <style>
                            @page { margin: 10mm; }
                            body { font-family: Arial, sans-serif; color: #111; margin: 0; font-size: 12px; }
                            h1 { font-size: 14px; margin: 0 0 10px 0; text-align: center; font-weight: 600; }
                            h2.print-summary-title { font-size: 13px; margin: 12px 0 8px; text-align: center; font-weight: 600; }
                            table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
                            th, td { border: 0; padding: 3px 6px; text-align: left; vertical-align: top; }
                            thead th { font-weight: 700; border-bottom: 1px solid #ddd; }
                            tbody tr { border: 0; }
                            a { color: inherit; text-decoration: none; }
                            .text-danger { color: #b91c1c !important; }
                            .text-success { color: #15803d !important; }
                            .text-secondary, .text-muted { color: #555 !important; }
                            .strike { text-align: center; margin: 4px 0 6px; }
                            .strike::before, .strike::after { display: none !important; }
                            .strike span { font-size: 12px; text-transform: none; letter-spacing: 0; color: #111; }
                            ul { padding-left: 0; margin: 0; list-style: none; }
                            li { border: 0; padding: 2px 6px; margin-bottom: 2px; display: flex; justify-content: space-between; }
                            .row { display: flex; gap: 12px; }
                            .row > div { flex: 1; }
                        </style>
                    </head>
                    <body>
                        <h1>${title}</h1>
                        ${printContent.innerHTML}
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        }
    </script>
@endpush
