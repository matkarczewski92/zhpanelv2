@extends('layouts.panel')

@section('title', 'Mioty')

@section('content')
    @php
        $filters = $page->filters;
    @endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Mioty</h1>
            <p class="text-muted mb-0">Lista aktualnych, planowanych i zakonczonych miotow.</p>
        </div>
        <a href="{{ route('panel.litters.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Dodaj nowy miot
        </a>
    </div>

    <div class="glass-card glass-table-wrapper mb-3">
        <div class="card-header">
            <div class="strike"><span>Filtry</span></div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('panel.litters.index') }}" class="row g-2">
                <div class="col-12 col-md-5">
                    <input type="text" class="form-control form-control-sm" name="q" placeholder="Kod miotu / rodzice" value="{{ $filters['q'] ?? '' }}">
                </div>
                <div class="col-12 col-md-3">
                    <input type="number" min="0" class="form-control form-control-sm" name="season" placeholder="Sezon" value="{{ $filters['season'] ?? '' }}">
                </div>
                <div class="col-12 col-md-4">
                    <select class="form-select form-select-sm" name="status">
                        <option value="">Status</option>
                        <option value="waiting_connection" @selected(($filters['status'] ?? '') === 'waiting_connection')>Oczekiwanie na laczenie</option>
                        <option value="waiting_laying" @selected(($filters['status'] ?? '') === 'waiting_laying')>Oczekiwanie na zniesienie</option>
                        <option value="incubation" @selected(($filters['status'] ?? '') === 'incubation')>W trakcie inkubacji</option>
                        <option value="feeding" @selected(($filters['status'] ?? '') === 'feeding')>W trakcie odchowu</option>
                        <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>Zakonczony</option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <a href="{{ route('panel.litters.index') }}" class="btn btn-outline-light btn-sm">Wyczysc</a>
                    <button type="submit" class="btn btn-primary btn-sm">Filtruj</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="glass-card glass-table-wrapper mb-3">
                <div class="card-header">
                    <div class="strike"><span>Aktualne mioty ({{ $page->counts['actual'] }})</span></div>
                </div>
                @include('panel.litters._list-table', ['rows' => $page->actualLitters, 'emptyMessage' => 'Brak aktualnych miotow.'])
            </div>

            <div class="glass-card glass-table-wrapper mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="strike flex-grow-1"><span>Planowane mioty ({{ $page->counts['planned'] }})</span></div>
                    @if (!empty($page->plannedSeasons))
                        <button type="button" class="btn btn-link text-danger p-0 ms-2" data-bs-toggle="modal" data-bs-target="#bulkDeletePlannedModal" aria-label="Usun sezon planowanych">
                            <i class="bi bi-trash"></i>
                        </button>
                    @endif
                </div>
                @include('panel.litters._list-table', ['rows' => $page->plannedLitters, 'emptyMessage' => 'Brak planowanych miotow.'])
            </div>

            <div class="glass-card glass-table-wrapper">
                <div class="card-header">
                    <div class="strike"><span>Zakonczone mioty ({{ $page->counts['closed'] }})</span></div>
                </div>
                @include('panel.litters._list-table', ['rows' => $page->closedLitters, 'emptyMessage' => 'Brak zakonczonych miotow.'])
            </div>
        </div>
    </div>

    @if (!empty($page->plannedSeasons))
        <div class="modal fade" id="bulkDeletePlannedModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-dark text-light border-0">
                    <div class="modal-header border-0">
                        <h5 class="modal-title">Usun planowane mioty</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                    </div>
                    <form method="POST" action="{{ route('panel.litters.bulk-destroy-planned') }}">
                        @csrf
                        @method('DELETE')
                        <div class="modal-body">
                            <label class="small text-muted mb-1">Sezon</label>
                            <select name="season" class="form-select form-select-sm" required>
                                <option value="">Wybierz sezon</option>
                                @foreach ($page->plannedSeasons as $season)
                                    <option value="{{ $season }}">{{ $season }}</option>
                                @endforeach
                            </select>
                            <p class="small text-muted mt-2 mb-0">
                                Usuwane sa tylko mioty bez daty laczenia i z terminem w przyszlosci.
                            </p>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">Anuluj</button>
                            <button type="submit" class="btn btn-danger btn-sm">Usun sezon</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
