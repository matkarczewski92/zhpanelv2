@extends('layouts.panel')

@section('title', 'Inkubacja')

@section('content')
    @php
        $incubator = $page['incubator'] ?? [];
        $rows = $page['rows'] ?? [];
    @endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Inkubacja</h1>
            <p class="text-muted mb-0">Aktywna inkubacja z szybkim podgladem etapow dla wszystkich miotow.</p>
        </div>
        <div class="small text-muted">Pozycji: {{ count($rows) }}</div>
    </div>

    <div class="glass-card mb-3 incubation-status-bar">
        <div class="card-body d-flex flex-wrap align-items-center gap-4">
            <div class="d-flex flex-column">
                <div class="small text-muted text-uppercase">Inkubator</div>
                @if (!empty($incubator['found']))
                    <div class="fw-semibold">{{ $incubator['device_name'] }}</div>
                    <div class="small text-muted">Ostatnia synchronizacja: {{ $incubator['last_synced_at'] }}</div>
                @else
                    <div class="fw-semibold">{{ $incubator['message'] ?? 'Brak danych inkubatora.' }}</div>
                @endif
            </div>

            @if (!empty($incubator['found']))
                <div class="d-flex flex-wrap align-items-center gap-4 ms-auto">
                    <div class="incubation-status-metric">
                        <i class="bi {{ $incubator['status_icon'] ?? 'bi-question-circle' }} incubation-status-icon {{ $incubator['status_class'] ?? 'text-muted' }}"></i>
                        <div>
                            <div class="small text-muted">Status</div>
                            <div class="fw-semibold {{ $incubator['status_class'] ?? 'text-muted' }}">{{ $incubator['status_label'] ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="incubation-status-metric">
                        <i class="bi bi-thermometer-half incubation-status-icon text-warning"></i>
                        <div>
                            <div class="small text-muted">Temperatura</div>
                            <div class="fw-semibold">{{ $incubator['temperature'] ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="incubation-status-metric">
                        <i class="bi bi-droplet-half incubation-status-icon text-info"></i>
                        <div>
                            <div class="small text-muted">Wilgotnosc</div>
                            <div class="fw-semibold">{{ $incubator['humidity'] ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @forelse ($rows as $row)
        @include('panel.litters.partials.incubation-timeline-items', [
            'timeline' => $row['timeline'],
        ])
    @empty
        <div class="card cardopacity">
            <div class="card-body text-center text-muted">Brak aktywnej inkubacji.</div>
        </div>
    @endforelse
@endsection

@push('styles')
    <style>
        .incubation-status-bar .card-body {
            background: transparent;
        }

        .incubation-status-metric {
            display: inline-flex;
            align-items: center;
            gap: 0.9rem;
            min-width: 180px;
        }

        .incubation-status-icon {
            font-size: 1.8rem;
            line-height: 1;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
                bootstrap.Tooltip.getOrCreateInstance(el, {
                    container: 'body',
                    trigger: 'hover focus',
                });
            });
        });
    </script>
@endpush
