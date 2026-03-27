@extends('layouts.panel')

@section('title', 'Raporty')

@section('content')
    @php
        $generator = $page['generator'] ?? [];
        $reports = $page['reports'] ?? null;
        $defaults = $generator['defaults'] ?? [];
    @endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Raporty</h1>
            <p class="text-muted mb-0">Generowanie raportow sprzedazy i dziennych wpisow z podgladem HTML oraz historia PDF.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-6">
            <div class="glass-card h-100">
                <div class="card-header">
                    <div class="fw-semibold">Raport sprzedazy</div>
                    <div class="small text-muted">Sprzedane zwierzeta i suma przychodu z wybranego zakresu dat.</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ $generator['generate_url'] ?? '#' }}" class="row g-2 align-items-end" data-role="admin-report-form">
                        @csrf
                        <input type="hidden" name="report_type" value="sales">
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1">Od</label>
                            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ old('date_from', $defaults['sales_from'] ?? '') }}">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1">Do</label>
                            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ old('date_to', $defaults['sales_to'] ?? '') }}">
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="btn btn-outline-light btn-sm"
                                data-role="admin-report-preview"
                                data-preview-url="{{ $generator['preview_url'] ?? '#' }}"
                            >
                                Podglad HTML
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm">Generuj PDF</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="glass-card h-100">
                <div class="card-header">
                    <div class="fw-semibold">Raport wprowadzonych danych</div>
                    <div class="small text-muted">Karmienia, wazenia i wylinki wpisane w jednym dniu.</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ $generator['generate_url'] ?? '#' }}" class="row g-2 align-items-end" data-role="admin-report-form">
                        @csrf
                        <input type="hidden" name="report_type" value="daily_entered_data">
                        <div class="col-12 col-md-8">
                            <label class="form-label small text-muted mb-1">Data raportu</label>
                            <input type="date" name="report_date" class="form-control form-control-sm" value="{{ old('report_date', $defaults['daily_date'] ?? '') }}">
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="btn btn-outline-light btn-sm"
                                data-role="admin-report-preview"
                                data-preview-url="{{ $generator['preview_url'] ?? '#' }}"
                            >
                                Podglad HTML
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm">Generuj PDF</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <div class="fw-semibold">Wygenerowane raporty</div>
                <div class="small text-muted">Najpierw najnowsze. PDF przechowywany lokalnie na serwerze.</div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table glass-table table-sm align-middle mb-0">
                <thead>
                    <tr class="text-muted small">
                        <th>Typ</th>
                        <th>Wygenerowano</th>
                        <th>Zakres / data</th>
                        <th>Pozycji</th>
                        <th>Plik</th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr>
                            <td>{{ $report['type_label'] }}</td>
                            <td>{{ $report['generated_at'] }}</td>
                            <td>{{ $report['selection_label'] }}</td>
                            <td>{{ $report['item_count'] ?? '-' }}</td>
                            <td>
                                <div>{{ $report['report_name'] }}</div>
                                <div class="small text-muted">{{ $report['file_name'] }}</div>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                    <a href="{{ $report['preview_url'] }}" class="btn btn-outline-light btn-sm" target="_blank">Preview</a>
                                    <a href="{{ $report['download_url'] }}" class="btn btn-outline-light btn-sm">PDF</a>
                                    <form method="POST" action="{{ $report['delete_url'] }}" onsubmit="return confirm('Usunac raport i zapisany plik PDF?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Usun</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Brak wygenerowanych raportow.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($reports && method_exists($reports, 'links'))
            <div class="card-body">
                {{ $reports->links() }}
            </div>
        @endif
    </div>
@endsection
