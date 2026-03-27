@extends('layouts.panel')

@section('title', $report['title'] ?? 'Podglad raportu')

@section('content')
    <style>
        .admin-report-fixed-table {
            table-layout: fixed;
            width: 100%;
        }

        .admin-report-fixed-table col.col-id {
            width: 5%;
        }

        .admin-report-fixed-table col.col-name {
            width: 45%;
        }

        .admin-report-fixed-table col.col-feedings {
            width: 18%;
        }

        .admin-report-fixed-table col.col-weights {
            width: 16%;
        }

        .admin-report-fixed-table col.col-molts {
            width: 16%;
        }

        .admin-report-fixed-table td,
        .admin-report-fixed-table th {
            word-break: break-word;
        }
    </style>

    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $report['title'] ?? 'Raport' }}</h1>
            <p class="text-muted mb-0">
                @if (!empty($from_history))
                    Podglad zapisanej wersji raportu.
                @else
                    Podglad HTML przed zapisaniem PDF.
                @endif
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if (!empty($history['download_url']))
                <a href="{{ $history['download_url'] }}" class="btn btn-outline-light btn-sm">Pobierz PDF</a>
            @elseif (!empty($generate['url']) && !empty($generate['filters']['report_type']))
                <form method="POST" action="{{ $generate['url'] }}">
                    @csrf
                    <input type="hidden" name="report_type" value="{{ $generate['filters']['report_type'] }}">
                    @if (!empty($generate['filters']['date_from']))
                        <input type="hidden" name="date_from" value="{{ $generate['filters']['date_from'] }}">
                    @endif
                    @if (!empty($generate['filters']['date_to']))
                        <input type="hidden" name="date_to" value="{{ $generate['filters']['date_to'] }}">
                    @endif
                    @if (!empty($generate['filters']['report_date']))
                        <input type="hidden" name="report_date" value="{{ $generate['filters']['report_date'] }}">
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">Generuj PDF</button>
                </form>
            @endif
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-light btn-sm">Wroc</a>
        </div>
    </div>

    <div class="glass-card mb-3">
        <div class="card-body d-flex flex-wrap gap-3">
            <div>
                <div class="small text-muted">Wygenerowano</div>
                <div>{{ $report['meta']['generated_at'] ?? ($history['generated_at'] ?? '-') }}</div>
            </div>
            @if (($report['type'] ?? null) === 'sales')
                <div>
                    <div class="small text-muted">Zakres</div>
                    <div>{{ $report['meta']['range_label'] ?? '-' }}</div>
                </div>
                <div>
                    <div class="small text-muted">Suma</div>
                    <div>{{ $report['meta']['total_amount_label'] ?? '-' }}</div>
                </div>
            @elseif (($report['type'] ?? null) === 'qr_scanner_session')
                <div>
                    <div class="small text-muted">Sesja</div>
                    <div>{{ $report['meta']['session_label'] ?? '-' }}</div>
                </div>
                <div>
                    <div class="small text-muted">Karmienia / Wazenia / Wylinki</div>
                    <div>
                        {{ $report['meta']['feedings_count'] ?? 0 }} /
                        {{ $report['meta']['weights_count'] ?? 0 }} /
                        {{ $report['meta']['molts_count'] ?? 0 }}
                    </div>
                </div>
            @else
                <div>
                    <div class="small text-muted">Data</div>
                    <div>{{ $report['meta']['report_date_label'] ?? '-' }}</div>
                </div>
                <div>
                    <div class="small text-muted">Karmienia / Wazenia / Wylinki</div>
                    <div>
                        {{ $report['meta']['feedings_count'] ?? 0 }} /
                        {{ $report['meta']['weights_count'] ?? 0 }} /
                        {{ $report['meta']['molts_count'] ?? 0 }}
                    </div>
                </div>
            @endif
            <div>
                <div class="small text-muted">Pozycji</div>
                <div>{{ $report['meta']['item_count'] ?? 0 }}</div>
            </div>
        </div>
    </div>

    @if (($report['type'] ?? null) === 'sales')
        <div class="glass-card">
            <div class="table-responsive">
                <table class="table glass-table table-sm align-middle mb-0">
                    <thead>
                        <tr class="text-muted small">
                            <th>ID</th>
                            <th>Nazwa</th>
                            <th>Public tag</th>
                            <th>Data sprzedazy</th>
                            <th>Cena</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (($report['rows'] ?? []) as $row)
                            <tr>
                                <td>{{ $row['animal_id'] }}</td>
                                <td>{!! $row['animal_name'] !!}</td>
                                <td>{{ $row['public_tag'] ?? '-' }}</td>
                                <td>{{ $row['sale_date'] }}</td>
                                <td>{{ $row['sale_price_label'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Suma</th>
                            <th>{{ $report['meta']['total_amount_label'] ?? '-' }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @elseif (($report['type'] ?? null) === 'daily_entered_data')
        <div class="glass-card">
            <div class="card-body d-flex flex-column gap-3">
                @forelse (($report['groups'] ?? []) as $group)
                    <div>
                        <div class="fw-semibold mb-2">{{ $group['label'] }}</div>
                        <div class="d-flex flex-column gap-3">
                            @foreach (($group['types'] ?? []) as $type)
                                <div>
                                    <div class="small text-muted fw-semibold mb-2">{{ $type['label'] }}</div>
                                    <div class="table-responsive">
                                        <table class="table glass-table table-sm align-middle mb-0 admin-report-fixed-table">
                                            <colgroup>
                                                <col class="col-id">
                                                <col class="col-name">
                                                <col class="col-feedings">
                                                <col class="col-weights">
                                                <col class="col-molts">
                                            </colgroup>
                                            <thead>
                                                <tr class="text-muted small">
                                                    <th>ID</th>
                                                    <th>Nazwa</th>
                                                    <th>Karmienia</th>
                                                    <th>Wazenia</th>
                                                    <th>Wylinki</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach (($type['rows'] ?? []) as $row)
                                                    <tr>
                                                        <td>{{ $row['animal_id'] }}</td>
                                                        <td>{!! $row['animal_name'] !!}</td>
                                                        <td>
                                                            @forelse (($row['feedings'] ?? []) as $entry)
                                                                <div>{{ $entry['label'] }}</div>
                                                            @empty
                                                                <span class="text-muted">-</span>
                                                            @endforelse
                                                        </td>
                                                        <td>
                                                            @forelse (($row['weights'] ?? []) as $entry)
                                                                <div>{{ $entry['label'] }}</div>
                                                            @empty
                                                                <span class="text-muted">-</span>
                                                            @endforelse
                                                        </td>
                                                        <td>
                                                            @forelse (($row['molts'] ?? []) as $entry)
                                                                <div>{{ $entry['label'] }}</div>
                                                            @empty
                                                                <span class="text-muted">-</span>
                                                            @endforelse
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="text-muted">Brak danych w kategoriach 1-4.</div>
                @endforelse
            </div>
        </div>
    @else
        <div class="glass-card">
            <div class="table-responsive">
                <table class="table glass-table table-sm align-middle mb-0 admin-report-fixed-table">
                    <colgroup>
                        <col class="col-id">
                        <col class="col-name">
                        <col class="col-feedings">
                        <col class="col-weights">
                        <col class="col-molts">
                    </colgroup>
                    <thead>
                        <tr class="text-muted small">
                            <th>ID</th>
                            <th>Nazwa</th>
                            <th>Karmienia</th>
                            <th>Wazenia</th>
                            <th>Wylinki</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (($report['rows'] ?? []) as $row)
                            <tr>
                                <td>{{ $row['animal_id'] }}</td>
                                <td>{!! $row['animal_name'] !!}</td>
                                <td>
                                    @forelse (($row['feedings'] ?? []) as $entry)
                                        <div>{{ $entry['label'] }}</div>
                                    @empty
                                        <span class="text-muted">-</span>
                                    @endforelse
                                </td>
                                <td>
                                    @forelse (($row['weights'] ?? []) as $entry)
                                        <div>{{ $entry['label'] }}</div>
                                    @empty
                                        <span class="text-muted">-</span>
                                    @endforelse
                                </td>
                                <td>
                                    @forelse (($row['molts'] ?? []) as $entry)
                                        <div>{{ $entry['label'] }}</div>
                                    @empty
                                        <span class="text-muted">-</span>
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
